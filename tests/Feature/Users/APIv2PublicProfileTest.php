<?php

namespace Tests\Feature\Users;

use App\Group;
use App\Role;
use App\Skills;
use App\User;
use Tests\TestCase;

/**
 * GET /api/v2/users/{id} — the PII-safe public profile behind
 * pages/profile/[id].vue and /profile (own). The legacy
 * resources/views/user/profile-new.blade.php (UserController::index($id)) is
 * the functional spec: any logged-in user may view any profile, showing name,
 * avatar, role, location, the user's groups and skills, and their biography.
 *
 * This endpoint was missing after the Nuxt cutover, so /profile and
 * /profile/{id} always rendered "That profile couldn't be found." (the
 * client's 404 state) — this test pins the restored behaviour.
 */
class APIv2PublicProfileTest extends TestCase
{
    public function testRequiresAuthentication(): void
    {
        $target = User::factory()->restarter()->create();

        $response = $this->withExceptionHandling()->getJson('/api/v2/users/'.$target->getKey());
        $response->assertStatus(401);
    }

    public function testReturnsThePublicProfile(): void
    {
        $viewer = User::factory()->restarter()->create(['api_token' => 'prof-tok']);
        $this->actingAs($viewer);

        $target = User::factory()->create([
            'role' => Role::RESTARTER,
            'location' => 'Bristol',
            'biography' => 'I fix kettles.',
        ]);

        $group = Group::factory()->create();
        $group->addVolunteer($target);

        $skill = Skills::factory()->create(['skill_name' => 'Soldering']);
        $target->skills()->attach($skill->getKey());

        // ->get() (not ->getJson()) validates the response against the
        // OpenAPI spec, so this also pins the documented shape.
        $response = $this->get('/api/v2/users/'.$target->getKey().'?api_token=prof-tok');

        $response->assertSuccessful();
        $response->assertJsonPath('data.id', (int) $target->getKey());
        $response->assertJsonPath('data.name', $target->name);
        $response->assertJsonPath('data.role_name', 'Restarter');
        $response->assertJsonPath('data.location', 'Bristol');
        $response->assertJsonPath('data.biography', 'I fix kettles.');

        $this->assertEqualsCanonicalizing(
            [['id' => (int) $group->getKey(), 'name' => $group->name]],
            $response->json('data.groups')
        );
        $this->assertEqualsCanonicalizing(
            [['id' => (int) $skill->getKey(), 'name' => 'Soldering']],
            $response->json('data.skills')
        );
    }

    public function testEmptyGroupsAndSkillsAreArrays(): void
    {
        $viewer = User::factory()->restarter()->create(['api_token' => 'prof-tok']);
        $this->actingAs($viewer);

        $target = User::factory()->restarter()->create(['biography' => null]);

        $response = $this->get('/api/v2/users/'.$target->getKey().'?api_token=prof-tok');

        $response->assertSuccessful();
        $this->assertSame([], $response->json('data.groups'));
        $this->assertSame([], $response->json('data.skills'));
        // A user with no biography stores '' in the DB (legacy column
        // default), so the field comes back empty rather than strictly null.
        $this->assertEmpty($response->json('data.biography'));
    }

    public function testAnyLoggedInUserCanViewAnotherUsersProfile(): void
    {
        $viewer = User::factory()->restarter()->create(['api_token' => 'prof-tok']);
        $this->actingAs($viewer);

        $target = User::factory()->administrator()->create();

        $response = $this->get('/api/v2/users/'.$target->getKey().'?api_token=prof-tok');

        $response->assertSuccessful();
        $response->assertJsonPath('data.id', (int) $target->getKey());
    }

    public function testReturns404ForUnknownUser(): void
    {
        $viewer = User::factory()->restarter()->create(['api_token' => 'prof-tok']);
        $this->actingAs($viewer);

        $response = $this->getJson('/api/v2/users/99999999?api_token=prof-tok');

        $response->assertStatus(404);
    }
}
