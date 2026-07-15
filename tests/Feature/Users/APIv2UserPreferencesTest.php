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
}
