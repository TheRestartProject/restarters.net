<?php

namespace Tests\Feature\Auth;

use App\User;
use DB;
use Tests\TestCase;

/**
 * VerifyUserConsentApi: authenticated users without the required data consents
 * can read but not mutate via /api/v2, mirroring the Blade app's
 * VerifyUserConsent page gate. The auth family stays open so the consent flow
 * itself works.
 */
class ConsentGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        DB::statement('SET foreign_key_checks=0');
        DB::table('personal_access_tokens')->truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    private function unconsentedUser(): User
    {
        return User::factory()->restarter()->create([
            'consent_gdpr' => null,
            'consent_past_data' => null,
            'consent_future_data' => null,
        ]);
    }

    public function testUnconsentedUserCannotMutate(): void
    {
        $user = $this->unconsentedUser();
        $token = $user->createToken('spa')->plainTextToken;

        $response = $this->patch('/api/v2/users/me/language', ['language' => 'fr'], [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('consent_required', $response->json('reason'));
    }

    public function testUnconsentedUserCanStillRead(): void
    {
        $user = $this->unconsentedUser();
        $token = $user->createToken('spa')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];

        $session = $this->get('/api/v2/session', $headers);
        $session->assertOk();
        $this->assertFalse($session->json('data.user.consent.given'));

        $this->get('/api/v2/groups/names', $headers)->assertOk();
    }

    public function testConsentEndpointLiftsTheGate(): void
    {
        $user = $this->unconsentedUser();
        $token = $user->createToken('spa')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];

        $consent = $this->post('/api/v2/auth/consent', [
            'age' => '1985',
            'country' => 'GBR',
            'city' => 'London',
            'consent_gdpr' => true,
            'consent_past_data' => true,
            'consent_future_data' => true,
        ], $headers);

        $consent->assertOk();
        $this->assertTrue($consent->json('data.user.consent.given'));

        // The auth manager caches resolved guards between in-test requests.
        app('auth')->forgetGuards();

        $mutate = $this->patch('/api/v2/users/me/language', ['language' => 'fr'], $headers);
        $mutate->assertOk();
    }

    public function testGuestMutationsUnaffectedByGate(): void
    {
        // Unauthenticated mutation attempts should fail on auth (401), not
        // consent (403) — the gate only applies to identified users.
        $response = $this->patch('/api/v2/users/me/language', ['language' => 'fr'], ['Accept' => 'application/json']);
        $response->assertStatus(401);
    }
}
