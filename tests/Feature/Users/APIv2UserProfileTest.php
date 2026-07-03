<?php

namespace Tests\Feature\Users;

use App\Role;
use App\Skills;
use App\User;
use App\UsersSkills;
use Tests\TestCase;

class APIv2UserProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testGetRequiresAuth(): void
    {
        $response = $this->getJson('/api/v2/users/me/profile');
        $response->assertStatus(401);
    }

    public function testGetReturnsCurrentProfileData(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'name' => 'Jo Restarter',
            'email' => 'jo@restarters.dev',
            'country_code' => 'GB',
            'location' => 'London',
            'age' => '1980',
            'gender' => 'Non-binary',
            'biography' => 'I like fixing toasters.',
        ]);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users/me/profile?api_token=tok1');
        $response->assertSuccessful();
        $this->assertEquals('Jo Restarter', $response->json('data.name'));
        $this->assertEquals('jo@restarters.dev', $response->json('data.email'));
        $this->assertEquals('GB', $response->json('data.country_code'));
        $this->assertEquals('London', $response->json('data.location'));
        $this->assertEquals('1980', $response->json('data.age'));
        $this->assertEquals('Non-binary', $response->json('data.gender'));
        $this->assertEquals('I like fixing toasters.', $response->json('data.biography'));
    }

    public function testGetResponseHidesPii(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users/me/profile?api_token=tok1');
        $response->assertSuccessful();

        $json = $response->getContent();
        $this->assertStringNotContainsString('api_token', $json);
        $this->assertStringNotContainsString('calendar_hash', $json);
        $this->assertStringNotContainsString('latitude', $json);
        $this->assertStringNotContainsString('longitude', $json);
    }

    public function testUpdateRequiresAuth(): void
    {
        $response = $this->patchJson('/api/v2/users/me/profile', ['name' => 'Foo']);
        $response->assertStatus(401);
    }

    public function testUpdateValidatesRequiredFields(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/profile?api_token=tok1', []);
        $response->assertStatus(422);
    }

    public function testUpdateValidatesEmailFormat(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/profile?api_token=tok1', [
            'name' => 'Jo Restarter',
            'email' => 'not-an-email',
            'age' => '1980',
            'country' => 'GB',
        ]);
        $response->assertStatus(422);
    }

    public function testUpdatePersistsFieldsAndGeocodesLocation(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/profile?api_token=tok1', [
            'name' => 'Jo Restarter',
            'email' => 'jo@restarters.dev',
            'age' => '1980',
            'country' => 'GB',
            'townCity' => 'London',
            'gender' => 'Non-binary',
            'biography' => 'I like fixing toasters.',
        ]);
        $response->assertSuccessful();

        $this->assertEquals('Jo Restarter', $response->json('data.name'));
        $this->assertEquals('jo@restarters.dev', $response->json('data.email'));
        $this->assertEquals('GB', $response->json('data.country_code'));
        $this->assertEquals('London', $response->json('data.location'));

        $user = $user->fresh();
        $this->assertEquals('Jo Restarter', $user->name);
        $this->assertEquals('jo@restarters.dev', $user->email);
        $this->assertEquals('GB', $user->country_code);
        $this->assertEquals('London', $user->location);
        $this->assertEquals('1980', $user->age);
        $this->assertEquals('Non-binary', $user->gender);
        $this->assertEquals('I like fixing toasters.', $user->biography);
        $this->assertEquals(51.507, round($user->latitude, 3));
        $this->assertEquals(-0.128, round($user->longitude, 3));
    }

    public function testUpdateClearsLatLongWhenLocationEmpty(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'location' => 'London',
            'latitude' => 51.5,
            'longitude' => -0.12,
        ]);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/profile?api_token=tok1', [
            'name' => $user->name,
            'email' => $user->email,
            'age' => $user->age,
            'country' => 'GB',
            'townCity' => '',
        ]);
        $response->assertSuccessful();

        $user = $user->fresh();
        $this->assertNull($user->latitude);
        $this->assertNull($user->longitude);
    }

    public function testUpdateClearsLatLongOnGeocodeFailure(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/profile?api_token=tok1', [
            'name' => $user->name,
            'email' => $user->email,
            'age' => $user->age,
            'country' => 'GB',
            'townCity' => 'ZZZZ',
        ]);
        $response->assertSuccessful();

        $user = $user->fresh();
        $this->assertNull($user->latitude);
        $this->assertNull($user->longitude);
    }

    public function testUpdateResponseHidesPii(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/profile?api_token=tok1', [
            'name' => $user->name,
            'email' => $user->email,
            'age' => $user->age,
            'country' => 'GB',
        ]);
        $response->assertSuccessful();

        $json = $response->getContent();
        $this->assertStringNotContainsString('api_token', $json);
        $this->assertStringNotContainsString('calendar_hash', $json);
        $this->assertStringNotContainsString('latitude', $json);
        $this->assertStringNotContainsString('longitude', $json);
    }

    public function testSkillsGetRequiresAuth(): void
    {
        $response = $this->getJson('/api/v2/users/me/skills');
        $response->assertStatus(401);
    }

    public function testSkillsGetReturnsCategoriesAndSelected(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $skill1 = Skills::create([
            'skill_name' => 'UT1',
            'description' => 'Planning',
            'category' => 1,
        ]);
        $skill2 = Skills::create([
            'skill_name' => 'UT2',
            'description' => 'Unit Testing',
            'category' => 2,
        ]);
        UsersSkills::create(['user' => $user->id, 'skill' => $skill1->id]);

        $response = $this->getJson('/api/v2/users/me/skills?api_token=tok1');
        $response->assertSuccessful();

        $categories = $response->json('data.categories');
        $this->assertIsArray($categories);
        $this->assertCount(2, $categories);

        $cat1 = collect($categories)->firstWhere('id', 1);
        $cat2 = collect($categories)->firstWhere('id', 2);
        $this->assertNotNull($cat1);
        $this->assertNotNull($cat2);
        $this->assertContains('UT1', array_column($cat1['skills'], 'name'));
        $this->assertContains('UT2', array_column($cat2['skills'], 'name'));

        $this->assertEquals([$skill1->id], $response->json('data.selected'));
    }

    public function testSkillsRequiresAuth(): void
    {
        $response = $this->patchJson('/api/v2/users/me/skills', ['tags' => []]);
        $response->assertStatus(401);
    }

    public function testSkillsPersistsAndPromotesToHost(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $skill1 = Skills::create([
            'skill_name' => 'UT1',
            'description' => 'Planning',
            'category' => 1,
        ]);
        $skill2 = Skills::create([
            'skill_name' => 'UT2',
            'description' => 'Unit Testing',
            'category' => 2,
        ]);

        $response = $this->patchJson('/api/v2/users/me/skills?api_token=tok1', [
            'tags' => [$skill1->id, $skill2->id],
        ]);
        $response->assertSuccessful();

        $persisted = $response->json('data.tags');
        $this->assertIsArray($persisted);
        $this->assertContains($skill1->id, $persisted);
        $this->assertContains($skill2->id, $persisted);

        $user = $user->fresh();
        $userSkillIds = UsersSkills::where('user', $user->id)->pluck('skill')->toArray();
        $this->assertContains($skill1->id, $userSkillIds);
        $this->assertContains($skill2->id, $userSkillIds);

        // Category 1 skill should have promoted a Restarter to Host.
        $this->assertEquals(Role::HOST, $user->role);
    }

    public function testSkillsReplacesExistingSelection(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $this->actingAs($user);

        $skill1 = Skills::create([
            'skill_name' => 'UT1',
            'description' => 'Planning',
            'category' => 2,
        ]);
        $skill2 = Skills::create([
            'skill_name' => 'UT2',
            'description' => 'Unit Testing',
            'category' => 2,
        ]);

        $this->patchJson('/api/v2/users/me/skills?api_token=tok1', [
            'tags' => [$skill1->id],
        ])->assertSuccessful();

        $response = $this->patchJson('/api/v2/users/me/skills?api_token=tok1', [
            'tags' => [$skill2->id],
        ]);
        $response->assertSuccessful();

        $user = $user->fresh();
        $userSkillIds = UsersSkills::where('user', $user->id)->pluck('skill')->toArray();
        $this->assertEquals([$skill2->id], $userSkillIds);
    }
}
