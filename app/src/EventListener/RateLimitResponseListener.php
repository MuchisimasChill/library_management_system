<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -10)]
class RateLimitResponseListener
{
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Add rate limit headers if available
        if ($request->attributes->has('rate_limit_info')) {
            $limit = $request->attributes->get('rate_limit_info');
            
            $response->headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
            $response->headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());
            
            if ($limit->getRetryAfter()) {
                $response->headers->set('X-RateLimit-Reset', (string) $limit->getRetryAfter()->getTimestamp());
            }
        }
    }
}
