<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RateLimitTest extends WebTestCase
{
    public function testApiRateLimit(): void
    {
        $client = static::createClient();

        // Make first request
        $client->request('GET', '/api/books');
        $response = $client->getResponse();

        // Should either be rate limited (429) or unauthorized (401)
        $this->assertContains($response->getStatusCode(), [
            Response::HTTP_UNAUTHORIZED, 
            Response::HTTP_TOO_MANY_REQUESTS
        ]);

        // If it's a rate limit response, check the structure
        if ($response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
            $data = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('error', $data);
            $this->assertArrayHasKey('message', $data);
            $this->assertEquals('Too Many Requests', $data['error']);
        }

        // Check that rate limit headers are present
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
    }

    public function testRateLimitExceeded(): void
    {
        $client = static::createClient();

        // Make many requests to exceed rate limit
        $hitRateLimit = false;
        for ($i = 0; $i < 110; $i++) { // More than the 100 limit
            $client->request('GET', '/api/books');
            $response = $client->getResponse();
            
            if ($response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
                $hitRateLimit = true;
                
                // Check rate limit response structure
                $data = json_decode($response->getContent(), true);
                $this->assertArrayHasKey('error', $data);
                $this->assertArrayHasKey('message', $data);
                $this->assertEquals('Too Many Requests', $data['error']);
                
                // Check headers
                $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
                $this->assertEquals('0', $response->headers->get('X-RateLimit-Remaining'));
                $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
                
                break;
            }
            
            // Use same client to maintain session/IP
        }

        $this->assertTrue($hitRateLimit, 'Rate limit should have been exceeded');
    }
}
