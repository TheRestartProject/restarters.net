<?php

namespace Tests\Feature\Users;

use App\Group;
use App\Role;
use App\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2OnboardingAndGroupsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    // --- POST /api/v2/users/me/onboarding-complete ---

    public function testOnboardingCompleteRequiresAuthentication(): void
    {
        // Json variant here specifically - without Accept:application/json, an unauthenticated
        // auth:sanctum,api request redirects (302) rather than returning JSON 401.
        $response = $this->postJson('/api/v2/users/me/onboarding-complete');

        $response->assertStatus(401);
    }

    public function testOnboardingCompleteBumpsNumberOfLoginsFromOneToTwo(): void
    {
        $user = User::factory()->restarter()->create([
            'api_token' => 'onboard-tok-1',
            'number_of_logins' => 1,
        ]);
        $this->actingAs($user);

        $response = $this->post('/api/v2/users/me/onboarding-complete?api_token=onboard-tok-1');

        $response->assertSuccessful();
        $this->assertEquals(['onboarding' => false], $response->json('data'));
        $this->assertEquals(2, $user->fresh()->number_of_logins);
    }

    public function testOnboardingCompleteDoesNotKeepIncrementingPastTwo(): void
    {
        $user = User::factory()->restarter()->create([
            'api_token' => 'onboard-tok-2',
            'number_of_logins' => 5,
        ]);
        $this->actingAs($user);

        $response = $this->post('/api/v2/users/me/onboarding-complete?api_token=onboard-tok-2');

        $response->assertSuccessful();
        $this->assertEquals(5, $user->fresh()->number_of_logins);
    }

    public function testOnboardingFlagOnSessionStaysFalseRegardlessOfCompletion(): void
    {
        // Parity with develop: the onboarding modal is dead code there (its gating
        // $onboarding view variable is never assigned), so the session flag is always
        // false - completing onboarding doesn't change it. The endpoint itself stays
        // functional (see testOnboardingCompleteBumpsNumberOfLoginsFromOneToTwo above),
        // it just no longer drives any client-visible flag.
        $user = User::factory()->restarter()->create([
            'api_token' => 'onboard-tok-3',
            'number_of_logins' => 1,
        ]);
        $this->actingAs($user);

        $before = $this->get('/api/v2/session?api_token=onboard-tok-3');
        $this->assertFalse($before->json('data.flags.onboarding'));

        $this->post('/api/v2/users/me/onboarding-complete?api_token=onboard-tok-3')->assertSuccessful();

        $after = $this->get('/api/v2/session?api_token=onboard-tok-3');
        $this->assertFalse($after->json('data.flags.onboarding'));
    }

    // --- GET /api/v2/users/me/groups ---

    public function testMyGroupsRequiresAuthentication(): void
    {
        $response = $this->getJson('/api/v2/users/me/groups');

        $response->assertStatus(401);
    }

    public function testMyGroupsReturnsAllMembershipsUncapped(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'mygroups-tok-1']);
        $this->actingAs($host);

        // Dashboard's your_groups caps at 5 - this endpoint must not.
        $ids = [];
        for ($i = 0; $i < 6; $i++) {
            $ids[] = $this->createGroup('My Groups Test '.Str::random(6));
        }

        $response = $this->get('/api/v2/users/me/groups?api_token=mygroups-tok-1');

        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(6, $data);

        $returnedIds = array_column($data, 'id');
        foreach ($ids as $id) {
            $this->assertContains($id, $returnedIds);
        }

        $entry = collect($data)->firstWhere('id', $ids[0]);
        $this->assertEquals(Role::HOST, $entry['role']);
        $this->assertFalse($entry['archived']);
        $this->assertArrayHasKey('image_url', $entry);
        $this->assertArrayHasKey('name', $entry);
    }

    public function testMyGroupsReflectsArchivedFlag(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'mygroups-tok-2']);
        $this->actingAs($host);

        $idgroups = $this->createGroup('Archived Membership Group '.Str::random(6));
        Group::find($idgroups)->update(['archived_at' => now()]);

        $response = $this->get('/api/v2/users/me/groups?api_token=mygroups-tok-2');

        $response->assertSuccessful();
        $entry = collect($response->json('data'))->firstWhere('id', $idgroups);
        $this->assertTrue($entry['archived']);
    }

    public function testMyGroupsIsEmptyForUserWithNoMemberships(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'mygroups-tok-3']);
        $this->actingAs($user);

        $response = $this->get('/api/v2/users/me/groups?api_token=mygroups-tok-3');

        $response->assertSuccessful();
        $this->assertEquals([], $response->json('data'));
    }
}
