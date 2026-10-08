<?php

namespace Tests\Feature\Skills;

use App\Skills;
use App\User;
use App\UsersSkills;
use Tests\TestCase;

class APIv2SkillsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testListSkillsPublic(): void
    {
        Skills::factory()->create(['skill_name' => 'Soldering', 'category' => 2, 'description' => 'Hot iron']);
        Skills::factory()->create(['skill_name' => 'Hospitality', 'category' => 1, 'description' => 'Welcoming people']);

        $response = $this->get('/api/v2/skills');
        $response->assertSuccessful();

        $data = $response->json('data');
        $this->assertIsArray($data);
        $names = array_column($data, 'skill_name');
        $this->assertContains('Soldering', $names);
        $this->assertContains('Hospitality', $names);
    }

    public function testListSkillsOrderedAlphabetically(): void
    {
        Skills::factory()->create(['skill_name' => 'Zucchini growing']);
        Skills::factory()->create(['skill_name' => 'Astronomy']);
        Skills::factory()->create(['skill_name' => 'Mead brewing']);

        $response = $this->get('/api/v2/skills');
        $response->assertSuccessful();
        $names = array_column($response->json('data'), 'skill_name');
        $filtered = array_values(array_filter($names, fn ($n) => in_array($n, ['Zucchini growing', 'Astronomy', 'Mead brewing'])));
        $this->assertEquals(['Astronomy', 'Mead brewing', 'Zucchini growing'], $filtered);
    }

    public function testGetSingleSkill(): void
    {
        $skill = Skills::factory()->create([
            'skill_name' => 'Mediation',
            'category' => 1,
            'description' => 'Conflict resolution',
        ]);

        $response = $this->getJson("/api/v2/skills/{$skill->id}");
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertEquals('Mediation', $data['skill_name']);
        $this->assertEquals(1, $data['category']);
        $this->assertEquals('Conflict resolution', $data['description']);
    }

    public function testGetMissingSkillReturns404(): void
    {
        $response = $this->getJson('/api/v2/skills/99999999');
        $response->assertStatus(404);
    }

    public function testCreateSkillAsAdmin(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/skills?api_token=admin1', [
            'skill_name' => 'CAD modelling',
            'category' => 2,
            'description' => 'Designing replacement parts',
        ]);
        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertEquals('CAD modelling', $data['skill_name']);
        $this->assertEquals(2, $data['category']);
        $this->assertDatabaseHas('skills', ['skill_name' => 'CAD modelling']);
    }

    public function testCreateSkillRequiresAuth(): void
    {
        $response = $this->postJson('/api/v2/skills', ['skill_name' => 'NoAuth', 'category' => 1]);
        $response->assertStatus(401);
    }

    public function testCreateSkillForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->postJson('/api/v2/skills?api_token=r1', [
            'skill_name' => 'Forbidden',
            'category' => 1,
        ]);
        $response->assertStatus(403);
    }

    public function testCreateSkillValidationFailures(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $this->postJson('/api/v2/skills?api_token=admin1', [])
            ->assertStatus(422);

        $this->postJson('/api/v2/skills?api_token=admin1', [
            'skill_name' => '',
            'category' => 1,
        ])->assertStatus(422);

        // Category must be in the allowed set
        $this->postJson('/api/v2/skills?api_token=admin1', [
            'skill_name' => 'Bad cat',
            'category' => 99,
        ])->assertStatus(422);
    }

    public function testCreateSkillRejectsDuplicate(): void
    {
        Skills::factory()->create(['skill_name' => 'Existing']);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v2/skills?api_token=admin1', [
            'skill_name' => 'Existing',
            'category' => 1,
        ]);
        $response->assertStatus(422);
    }

    public function testUpdateSkillAsAdmin(): void
    {
        $skill = Skills::factory()->create([
            'skill_name' => 'OldName',
            'category' => 1,
            'description' => 'Old',
        ]);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson("/api/v2/skills/{$skill->id}?api_token=admin1", [
            'skill_name' => 'NewName',
            'category' => 2,
            'description' => 'Renamed',
        ]);
        $response->assertSuccessful();
        $this->assertDatabaseHas('skills', [
            'id' => $skill->id,
            'skill_name' => 'NewName',
            'category' => 2,
            'description' => 'Renamed',
        ]);
    }

    public function testUpdateAllowsSameNameAsItself(): void
    {
        $skill = Skills::factory()->create(['skill_name' => 'KeepMyName', 'category' => 1]);
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson("/api/v2/skills/{$skill->id}?api_token=admin1", [
            'skill_name' => 'KeepMyName',
            'category' => 1,
        ]);
        $response->assertSuccessful();
    }

    public function testUpdateSkillRequiresAuth(): void
    {
        $skill = Skills::factory()->create();
        $response = $this->putJson("/api/v2/skills/{$skill->id}", ['skill_name' => 'X', 'category' => 1]);
        $response->assertStatus(401);
    }

    public function testUpdateSkillForbiddenForNonAdmin(): void
    {
        $skill = Skills::factory()->create();
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->putJson("/api/v2/skills/{$skill->id}?api_token=r1", [
            'skill_name' => 'NoTouch',
            'category' => 1,
        ]);
        $response->assertStatus(403);
    }

    public function testUpdateMissingSkillReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);
        $response = $this->putJson('/api/v2/skills/99999999?api_token=admin1', [
            'skill_name' => 'Ghost',
            'category' => 1,
        ]);
        $response->assertStatus(404);
    }

    public function testDeleteSkillAsAdmin(): void
    {
        $skill = Skills::factory()->create();
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->delete("/api/v2/skills/{$skill->id}?api_token=admin1");
        $response->assertNoContent();
        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
    }

    public function testDeleteSkillAlsoRemovesUserSkillsPivot(): void
    {
        $skill = Skills::factory()->create();
        $user = User::factory()->restarter()->create();
        UsersSkills::create(['user' => $user->id, 'skill' => $skill->id]);

        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $this->delete("/api/v2/skills/{$skill->id}?api_token=admin1")->assertNoContent();

        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
        $this->assertDatabaseMissing('users_skills', ['user' => $user->id, 'skill' => $skill->id]);
    }

    public function testDeleteSkillRequiresAuth(): void
    {
        $skill = Skills::factory()->create();
        $response = $this->deleteJson("/api/v2/skills/{$skill->id}");
        $response->assertStatus(401);
    }

    public function testDeleteSkillForbiddenForNonAdmin(): void
    {
        $skill = Skills::factory()->create();
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->delete("/api/v2/skills/{$skill->id}?api_token=r1");
        $response->assertStatus(403);
    }
}
