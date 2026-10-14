<?php

namespace Tests\Feature\Auth;

use App\User;
use DB;
use Tests\TestCase;

/**
 * The API accepts two credential types side by side (auth:sanctum,api):
 * - Sanctum personal access tokens (Bearer header) — used by the Nuxt SPA.
 * - Legacy users.api_token values (query param or Bearer header) — used by
 *   external consumers (Zapier, TRP.org, RepairTogether) and grandfathered
 *   clients. These must keep working unchanged.
 */
class DualGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        DB::statement('SET foreign_key_checks=0');
        DB::table('personal_access_tokens')->truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    public function testLegacyTokenViaQueryParam(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'legacy-query-token']);

        $response = $this->getJson('/api/users/me?api_token=legacy-query-token');

        $response->assertOk();
        $this->assertEquals($user->id, $response->json('id'));
    }

    public function testLegacyTokenViaBearerHeader(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'legacy-bearer-token']);

        $response = $this->getJson('/api/users/me', [
            'Authorization' => 'Bearer legacy-bearer-token',
        ]);

        $response->assertOk();
        $this->assertEquals($user->id, $response->json('id'));
    }

    public function testSanctumTokenViaBearerHeader(): void
    {
        $user = User::factory()->restarter()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $response = $this->getJson('/api/users/me', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
        $this->assertEquals($user->id, $response->json('id'));
    }

    public function testSanctumTokenOnV2Route(): void
    {
        $user = User::factory()->restarter()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $response = $this->getJson('/api/v2/users/me/language', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
    }

    public function testLegacyTokenOnV2Route(): void
    {
        User::factory()->restarter()->create(['api_token' => 'legacy-v2-token']);

        $response = $this->getJson('/api/v2/users/me/language?api_token=legacy-v2-token');

        $response->assertOk();
    }

    public function testMissingTokenGives401Json(): void
    {
        $response = $this->getJson('/api/users/me');

        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }

    public function testInvalidSanctumTokenFallsThroughAndFails(): void
    {
        $response = $this->getJson('/api/users/me', [
            'Authorization' => 'Bearer 1|definitely-not-a-real-token',
        ]);

        $response->assertStatus(401);
    }

    public function testSanctumTokenWorksOnOptionalAuthEndpoint(): void
    {
        // getGroupv2 is publicly readable but computes per-user permission
        // flags via the session→sanctum→api guard fallback chain. Create the
        // group directly (not via the API helper) so no web session exists and
        // the Bearer token is the only credential in play.
        $admin = User::factory()->administrator()->create();
        $token = $admin->createToken('spa')->plainTextToken;
        $group = \App\Group::factory()->create();

        $response = $this->getJson('/api/v2/groups/'.$group->idgroups, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('data.permissions.can_edit'));
    }
}
