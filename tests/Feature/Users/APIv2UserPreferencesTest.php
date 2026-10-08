<?php

namespace Tests\Feature\Users;

use App\User;
use Tests\TestCase;

class APIv2UserPreferencesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testGetPreferencesRequiresAuth(): void
    {
        $response = $this->getJson('/api/v2/users/me/preferences');
        $response->assertStatus(401);
    }

    public function testGetPreferencesReturnsCurrentInvitesFlag(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'invites' => 1,
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users/me/preferences?api_token=tok1');
        $response->assertSuccessful();
        $this->assertTrue($response->json('data.invites'));
    }

    public function testGetPreferencesReturnsFalseWhenUnset(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'invites' => 0,
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users/me/preferences?api_token=tok1');
        $response->assertSuccessful();
        $this->assertFalse($response->json('data.invites'));
    }

    public function testUpdatePreferencesRequiresAuth(): void
    {
        $response = $this->patchJson('/api/v2/users/me/preferences', ['invites' => true]);
        $response->assertStatus(401);
    }

    public function testUpdatePreferencesValidatesInvitesPresent(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/preferences?api_token=tok1', []);
        $response->assertStatus(422);
    }

    public function testUpdatePreferencesPersistsTrue(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'invites' => 0,
        ]);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/preferences?api_token=tok1', [
            'invites' => true,
        ]);
        $response->assertSuccessful();
        $this->assertTrue($response->json('data.invites'));
        $this->assertEquals(1, $user->fresh()->invites);
    }

    public function testUpdatePreferencesPersistsFalse(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'invites' => 1,
        ]);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/preferences?api_token=tok1', [
            'invites' => false,
        ]);
        $response->assertSuccessful();
        $this->assertFalse($response->json('data.invites'));
        $this->assertEquals(0, $user->fresh()->invites);
    }

    public function testAdminCanUpdateAnotherUsersPreferences(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create(['invites' => 0]);
        $this->actingAs($admin);

        $response = $this->patchJson("/api/v2/users/{$target->id}/preferences?api_token=tok1", [
            'invites' => true,
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $target->fresh()->invites);
    }

    public function testNonAdminCannotUpdateAnotherUsersPreferences(): void
    {
        $attacker = User::factory()->host()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create(['invites' => 0]);
        $this->actingAs($attacker);

        $response = $this->patchJson("/api/v2/users/{$target->id}/preferences?api_token=tok1", [
            'invites' => true,
        ]);

        $response->assertStatus(403);
        $this->assertEquals(0, $target->fresh()->invites);
    }

    public function testSelfCanUpdateOwnPreferencesViaIdRoute(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1', 'invites' => 0]);
        $this->actingAs($user);

        $response = $this->patchJson("/api/v2/users/{$user->id}/preferences?api_token=tok1", [
            'invites' => true,
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $user->fresh()->invites);
    }

    public function testUpdatePreferencesUnknownIdReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);

        $response = $this->patchJson('/api/v2/users/999999999/preferences?api_token=tok1', [
            'invites' => true,
        ]);

        $response->assertStatus(404);
    }

    public function testGetAnotherUsersPreferencesRequiresAdmin(): void
    {
        $attacker = User::factory()->host()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create(['invites' => 1]);
        $this->actingAs($attacker);

        $response = $this->getJson("/api/v2/users/{$target->id}/preferences?api_token=tok1");

        $response->assertStatus(403);
    }

    public function testAdminCanGetAnotherUsersPreferences(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create(['invites' => 1]);
        $this->actingAs($admin);

        $response = $this->getJson("/api/v2/users/{$target->id}/preferences?api_token=tok1");

        $response->assertSuccessful();
        $this->assertTrue($response->json('data.invites'));
    }

    public function testSelfCanGetOwnPreferencesViaIdRoute(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1', 'invites' => 0]);
        $this->actingAs($user);

        $response = $this->getJson("/api/v2/users/{$user->id}/preferences?api_token=tok1");

        $response->assertSuccessful();
        $this->assertFalse($response->json('data.invites'));
    }

    public function testGetPreferencesUnknownIdReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/users/999999999/preferences?api_token=tok1');

        $response->assertStatus(404);
    }
}
