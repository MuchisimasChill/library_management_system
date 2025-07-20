<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundle;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class RateLimitListener
{
    public function __construct(
        private RateLimiterFactory $apiGeneralLimiter,
        private RateLimiterFactory $loginAttemptsLimiter,
        private RateLimiterFactory $bookCreationLimiter,
        private RateLimiterFactory $loanCreationLimiter,
        private ?SecurityBundle $security = null
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Skip rate limiting for certain routes
        if (in_array($route, ['api_login_doc', 'api_test', 'dev_user_create', 'app_security_login']) ||
            $request->getPathInfo() === '/api/auth/login') {
            return;
        }

        // Get client identifier (IP + User ID if authenticated)
        $clientId = $this->getClientIdentifier($request);

        // Apply general API rate limit for all API routes
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            $limiter = $this->apiGeneralLimiter->create($clientId);
            $limit = $limiter->consume(1);

            if (!$limit->isAccepted()) {
                $response = new JsonResponse([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $limit->getRetryAfter()->getTimestamp()
                ], Response::HTTP_TOO_MANY_REQUESTS);
                
                $response->headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
                $response->headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());
                $response->headers->set('X-RateLimit-Reset', (string) $limit->getRetryAfter()->getTimestamp());
                
                $event->setResponse($response);
                return;
            }

            // Add rate limit headers to all API responses
            $request->attributes->set('rate_limit_info', $limit);
        }

        // Apply specific rate limits based on route
        $specificLimiter = null;
        $specificClientId = $clientId;

        match ($route) {
            'app_security_login' => $specificLimiter = $this->loginAttemptsLimiter->create($this->getIpAddress($request)),
            'app_book_create' => $specificLimiter = $this->bookCreationLimiter->create($clientId),
            'app_loan_create' => $specificLimiter = $this->loanCreationLimiter->create($clientId),
            default => null
        };

        if ($specificLimiter) {
            $limit = $specificLimiter->consume(1);
            
            if (!$limit->isAccepted()) {
                $response = new JsonResponse([
                    'error' => 'Rate Limit Exceeded',
                    'message' => 'You have exceeded the rate limit for this action.',
                    'retry_after' => $limit->getRetryAfter()->getTimestamp()
                ], Response::HTTP_TOO_MANY_REQUESTS);
                
                $response->headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
                $response->headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());
                $response->headers->set('X-RateLimit-Reset', (string) $limit->getRetryAfter()->getTimestamp());
                
                $event->setResponse($response);
                return;
            }
        }
    }

    private function getClientIdentifier($request): string
    {
        $ip = $this->getIpAddress($request);
        
        // If user is authenticated, include user ID in identifier
        if ($this->security && $this->security->getUser()) {
            return $ip . '_' . $this->security->getUser()->getUserIdentifier();
        }
        
        return $ip;
    }

    private function getIpAddress($request): string
    {
        // Check for IP from various headers (for reverse proxy setups)
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle multiple IPs (take the first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->getClientIp() ?? '127.0.0.1';
    }
}
