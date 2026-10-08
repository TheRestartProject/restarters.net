<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccountCreationTest extends TestCase
{
    public function testRegisterInvalidAddress(): void
    {
        // POST /user/register/ is dead (Nuxt cutover); the live equivalent is
        // POST /api/v2/auth/register. An unresolvable city must leave lat/long null
        // while the user is still created (registerv2 only sets them when the
        // geocoder returns a result - see AuthController::registerv2 and
        // Tests\Support\GeocoderMock, which returns null for unrecognised places).
        $response = $this->post('/api/v2/auth/register', [
            'name' => 'Bad Address User',
            'email' => 'badaddress@restarters.test',
            'password' => 'longenough',
            'password_confirmation' => 'longenough',
            'age' => '1980',
            'country' => 'GB',
            'city' => 'zzzzzzz',
            'consent_gdpr' => true,
            'consent_future_data' => true,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'badaddress@restarters.test',
            'latitude' => null,
            'longitude' => null,
        ]);
    }
}
