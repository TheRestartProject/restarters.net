<?php

namespace Tests\Feature\Auth;

use App\Network;
use App\User;
use DB;
use Tests\TestCase;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        DB::statement('SET foreign_key_checks=0');
        DB::table('personal_access_tokens')->truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    public function testGuestSession(): void
    {
        $response = $this->get('/api/v2/session', ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertNull($response->json('data.user'));
        $this->assertNotEmpty($response->json('data.config.frontend_url'));
        $this->assertFalse($response->json('data.flags.onboarding'));
    }

    public function testAuthenticatedSessionShape(): void
    {
        $network = Network::factory()->create(['name' => 'Session Test Network']);
        $user = User::factory()->administrator()->create(['language' => 'fr']);
        $user->networks()->attach($network->id);
        $token = $user->createToken('spa')->plainTextToken;

        $response = $this->get('/api/v2/session', [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertEquals($user->id, $response->json('data.user.id'));
        $this->assertEquals('Administrator', $response->json('data.user.role_name'));
        $this->assertEquals('fr', $response->json('data.user.language'));
        $this->assertTrue($response->json('data.user.consent.given'));
        $this->assertEquals('Session Test Network', $response->json('data.user.networks.0.name'));
        // Factory users have number_of_logins=1, so onboarding is on.
        $this->assertTrue($response->json('data.flags.onboarding'));
    }

    public function testPatchLocalePersists(): void
    {
        $user = User::factory()->restarter()->create(['language' => 'en']);
        $token = $user->createToken('spa')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];

        $response = $this->patch('/api/v2/session', ['locale' => 'fr-BE'], $headers);

        $response->assertOk();
        $this->assertEquals('fr-BE', $response->json('data.user.language'));
        $this->assertEquals('fr-BE', $user->fresh()->language);

        $bad = $this->patch('/api/v2/session', ['locale' => 'xx-XX'], $headers);
        $bad->assertStatus(422);

        // The auth manager caches resolved guards between in-test requests.
        app('auth')->forgetGuards();

        $guest = $this->patch('/api/v2/session', ['locale' => 'fr'], ['Accept' => 'application/json']);
        $guest->assertStatus(401);
    }
}
