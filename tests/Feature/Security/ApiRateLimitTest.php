<?php

namespace Tests\Feature\Security;

use App\User;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    /**
     * Volunteers at a repair event usually share one IP (the venue wifi). One of them using up the API rate limit
     * must not lock the others out, so authenticated requests are limited per user rather than per IP.
     */
    public function testAuthenticatedUsersOnSameIpHaveSeparateLimits(): void
    {
        $busy = User::factory()->restarter()->create(['api_token' => '1234']);
        $other = User::factory()->restarter()->create(['api_token' => '5678']);

        $this->withExceptionHandling();
        $response = null;

        for ($i = 0; $i < 301; $i++) {
            $response = $this->call('GET', '/api/v2/items?api_token=1234');
        }

        $response->assertStatus(429);

        $this->call('GET', '/api/v2/items?api_token=5678')->assertSuccessful();
    }

    /**
     * The Nuxt client signs in with Sanctum tokens rather than api_token, and its users share venue wifi too.
     */
    public function testSanctumUsersOnSameIpHaveSeparateLimits(): void
    {
        $busy = User::factory()->restarter()->create()->createToken('spa')->plainTextToken;
        $other = User::factory()->restarter()->create()->createToken('spa')->plainTextToken;

        $this->withExceptionHandling();
        $response = null;

        for ($i = 0; $i < 301; $i++) {
            $response = $this->call('GET', '/api/v2/items', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$busy]);
        }

        $response->assertStatus(429);

        $this->call('GET', '/api/v2/items', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$other])->assertSuccessful();
    }

    /**
     * Behind Fly every request has the same $request->ip(), so anonymous requests are limited per Fly-Client-IP.
     */
    public function testAnonymousClientsAreLimitedByFlyClientIp(): void
    {
        $this->withExceptionHandling();
        $response = null;

        for ($i = 0; $i < 301; $i++) {
            // call() rather than get() to skip the OpenAPI validation, which doesn't document 429s.
            $response = $this->call('GET', '/api/v2/groups/names', [], [], [], ['HTTP_FLY_CLIENT_IP' => '10.0.0.1']);
        }

        $response->assertStatus(429);

        $this->call('GET', '/api/v2/groups/names', [], [], [], ['HTTP_FLY_CLIENT_IP' => '10.0.0.2'])->assertSuccessful();
    }
}
