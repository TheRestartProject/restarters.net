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

    /**
     * Logging in is limited per real client and email address: behind Fly every request has the same
     * $request->ip(), so one limit keyed on it was shared by everyone, and volunteers at a venue share its wifi.
     */
    public function testLoginAttemptsAreLimitedPerClientAndEmail(): void
    {
        config(['restarters.auth_rate_limit' => 3]);
        $this->withExceptionHandling();

        $login = fn (string $ip, string $email) => $this->call('POST', '/api/v2/auth/login',
            ['email' => $email, 'password' => 'wrong'], [], [], ['HTTP_FLY_CLIENT_IP' => $ip, 'HTTP_ACCEPT' => 'application/json']);

        for ($i = 0; $i < 3; $i++) {
            $this->assertNotEquals(429, $login('10.0.1.1', 'someone@example.com')->getStatusCode());
        }

        $this->assertEquals(429, $login('10.0.1.1', 'someone@example.com')->getStatusCode());
        $this->assertNotEquals(429, $login('10.0.1.2', 'someone@example.com')->getStatusCode(), 'another client');
        $this->assertNotEquals(429, $login('10.0.1.1', 'someone.else@example.com')->getStatusCode(), 'another person at the same venue');
    }

    /**
     * One client trying one password across many accounts is limited too, by a looser limit per client.
     */
    public function testLoginAttemptsAcrossManyEmailsAreLimitedPerClient(): void
    {
        config(['restarters.auth_rate_limit' => 3]);
        $this->withExceptionHandling();

        $statuses = [];
        for ($i = 0; $i < 10; $i++) {
            $statuses[] = $this->call('POST', '/api/v2/auth/login', ['email' => "person$i@example.com", 'password' => 'wrong'],
                [], [], ['HTTP_FLY_CLIENT_IP' => '10.0.2.1', 'HTTP_ACCEPT' => 'application/json'])->getStatusCode();
        }

        $this->assertNotContains(429, array_slice($statuses, 0, 9));
        $this->assertEquals(429, $statuses[9]);
    }

    public function testAnEmailThatIsNotAStringIsNotAServerError(): void
    {
        $this->withExceptionHandling();

        $response = $this->call('POST', '/api/v2/auth/login', ['email' => ['a@example.com'], 'password' => 'wrong'],
            [], [], ['HTTP_FLY_CLIENT_IP' => '10.0.3.1', 'HTTP_ACCEPT' => 'application/json']);

        $this->assertLessThan(500, $response->getStatusCode());
    }
}
