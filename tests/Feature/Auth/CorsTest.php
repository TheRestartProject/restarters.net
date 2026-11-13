<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * CORS is handled by Laravel's HandleCors + config/cors.php (the hand-rolled
 * AddCorsHeaders middleware is gone). Wildcard origin + no credentials: the
 * SPA authenticates with bearer headers, partner sites keep read access.
 */
class CorsTest extends TestCase
{
    public function testPreflightAllowsSpaMethodsAndHeaders(): void
    {
        $response = $this->call('OPTIONS', '/api/v2/session', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'PATCH',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization, content-type',
        ]);

        $this->assertContains($response->getStatusCode(), [200, 204]);
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $allowMethods = $response->headers->get('Access-Control-Allow-Methods');
        $this->assertTrue($allowMethods === '*' || str_contains($allowMethods, 'PATCH'));
        $allowHeaders = strtolower($response->headers->get('Access-Control-Allow-Headers'));
        $this->assertTrue($allowHeaders === '*' || str_contains($allowHeaders, 'authorization'));
    }

    public function testSimpleCrossOriginRequestGetsCorsHeaders(): void
    {
        $response = $this->get('/api/v2/session', [
            'Origin' => 'https://therestartproject.org',
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testNoCredentialsHeaderEverSent(): void
    {
        $response = $this->call('OPTIONS', '/api/v2/session', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        $this->assertNull($response->headers->get('Access-Control-Allow-Credentials'));
    }
}
