<?php

namespace Tests\Feature\Users;

use App\User;
use Tests\TestCase;

class APIv2UserLanguageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testGetRequiresAuth(): void
    {
        $response = $this->getJson('/api/v2/users/me/language');
        $response->assertStatus(401);
    }

    public function testGetReturnsCurrentLanguageAndSupported(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'language' => 'en',
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users/me/language?api_token=tok1');
        $response->assertSuccessful();
        $this->assertEquals('en', $response->json('data.language'));
        $supported = $response->json('data.supported');
        $this->assertIsArray($supported);
        $this->assertNotEmpty($supported);
        $this->assertArrayHasKey('code', $supported[0]);
        $this->assertArrayHasKey('native', $supported[0]);
    }

    public function testUpdateRequiresAuth(): void
    {
        $response = $this->patchJson('/api/v2/users/me/language', ['language' => 'en']);
        $response->assertStatus(401);
    }

    public function testUpdateValidatesAgainstSupportedLocales(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/language?api_token=tok1', [
            'language' => 'xx',
        ]);
        $response->assertStatus(422);
    }

    public function testUpdatePersistsLanguage(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'language' => 'en',
        ]);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/language?api_token=tok1', [
            'language' => 'fr',
        ]);
        $response->assertSuccessful();
        $this->assertEquals('fr', $response->json('data.language'));
        $this->assertEquals('fr', $user->fresh()->language);
    }
}
