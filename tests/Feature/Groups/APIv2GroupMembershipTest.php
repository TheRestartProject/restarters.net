<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Network;
use App\Notifications\NewGroupMember;
use App\Role;
use App\User;
use App\UserGroups;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2GroupMembershipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createGroupAsHost(User $host): int
    {
        $this->actingAs($host);

        return $this->createGroup(
            'Membership Test Group '.Str::random(8),
            'https://therestartproject.org',
            'London'
        );
    }

    // --- join ---

    public function testJoinRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->postJson("/api/v2/groups/$idgroups/members/me");

        $response->assertStatus(401);
    }

    public function testJoinUnknownGroupReturns404(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'join-tok-404']);
        $this->actingAs($user);

        $response = $this->post('/api/v2/groups/999999/members/me?api_token=join-tok-404');

        $response->assertStatus(404);
    }

    public function testJoinCreatesConfirmedRestarterMembershipAndNotifiesHosts(): void
    {
        Notification::fake();

        $host = User::factory()->host()->create(['api_token' => 'join-tok-host']);
        $idgroups = $this->createGroupAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'join-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->post("/api/v2/groups/$idgroups/members/me?api_token=join-tok-1");

        $response->assertSuccessful();
        $this->assertEquals([
            'joined' => true,
            'already_member' => false,
        ], $response->json('data'));

        $member = UserGroups::where('group', $idgroups)->where('user', $restarter->id)->first();
        $this->assertNotNull($member);
        $this->assertEquals(1, $member->status);
        $this->assertEquals(Role::RESTARTER, $member->role);

        Notification::assertSentTo($host, NewGroupMember::class);
    }

    public function testJoiningTwiceReportsAlreadyMember(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'join-tok-host2']);
        $idgroups = $this->createGroupAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'join-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $first = $this->post("/api/v2/groups/$idgroups/members/me?api_token=join-tok-2");
        $first->assertSuccessful();
        $this->assertFalse($first->json('data.already_member'));

        $second = $this->post("/api/v2/groups/$idgroups/members/me?api_token=join-tok-2");
        $second->assertSuccessful();
        $this->assertTrue($second->json('data.already_member'));

        $this->assertEquals(1, UserGroups::where('group', $idgroups)->where('user', $restarter->id)->count());
    }

    // --- leave ---

    public function testLeaveRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->deleteJson("/api/v2/groups/$idgroups/members/me");

        $response->assertStatus(401);
    }

    public function testLeaveRemovesConfirmedMembership(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'leave-tok-host']);
        $idgroups = $this->createGroupAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'leave-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);
        $this->post("/api/v2/groups/$idgroups/members/me?api_token=leave-tok-1")->assertSuccessful();

        $response = $this->delete("/api/v2/groups/$idgroups/members/me?api_token=leave-tok-1");

        $response->assertSuccessful();
        $this->assertEquals(['left' => true], $response->json('data'));

        $this->assertNull(
            UserGroups::where('group', $idgroups)->where('user', $restarter->id)->where('status', 1)->first()
        );
    }

    public function testLeavingWhenNotAMemberIsIdempotent(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'leave-tok-host2']);
        $idgroups = $this->createGroupAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'leave-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->delete("/api/v2/groups/$idgroups/members/me?api_token=leave-tok-2");

        $response->assertSuccessful();
        $this->assertEquals(['left' => true], $response->json('data'));
    }

    // --- nearby ---

    public function testNearbyRequiresAuthentication(): void
    {
        $response = $this->getJson('/api/v2/groups/nearby');

        $response->assertStatus(401);
    }

    public function testNearbyReturnsEmptyArrayWithoutLocation(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'nearby-tok-1']);
        $this->actingAs($user);

        $response = $this->get('/api/v2/groups/nearby?api_token=nearby-tok-1');

        $response->assertSuccessful();
        $this->assertEquals([], $response->json('data'));
    }

    public function testNearbyReturnsGroupsWithinRange(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'nearby-tok-host']);
        $idgroups = $this->createGroupAsHost($host); // Geocoded to London via the mock.

        $user = User::factory()->restarter()->create([
            'api_token' => 'nearby-tok-2',
            'latitude' => 51.5073509,
            'longitude' => -0.1277583,
        ]);
        $this->app['auth']->forgetGuards();
        $this->actingAs($user);

        $response = $this->get('/api/v2/groups/nearby?api_token=nearby-tok-2');

        $response->assertSuccessful();
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($idgroups, $ids);
    }

    // --- archive (DELETE /groups/{id}) ---

    public function testArchiveRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->deleteJson("/api/v2/groups/$idgroups");

        $response->assertStatus(401);
    }

    public function testArchiveDeniedForHostWithoutCoordinatorOrAdminRole(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'archive-tok-1']);
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->delete("/api/v2/groups/$idgroups?api_token=archive-tok-1");

        $response->assertStatus(403);
        $this->assertNull(Group::find($idgroups)->archived_at);
    }

    public function testAdministratorCanArchiveGroup(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $admin = User::factory()->administrator()->create(['api_token' => 'archive-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($admin);

        $response = $this->delete("/api/v2/groups/$idgroups?api_token=archive-tok-2");

        $response->assertSuccessful();
        $this->assertEquals(['archived' => true], $response->json('data'));
        $this->assertNotNull(Group::find($idgroups)->archived_at);
    }

    public function testNetworkCoordinatorForGroupCanArchiveGroup(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $network = Network::factory()->create(['shortname' => 'archivenetwork'.Str::random(6)]);
        $network->addGroup(Group::find($idgroups));

        $coordinator = User::factory()->networkCoordinator()->create(['api_token' => 'archive-tok-3']);
        $network->addCoordinator($coordinator);

        $this->app['auth']->forgetGuards();
        $this->actingAs($coordinator);

        $response = $this->delete("/api/v2/groups/$idgroups?api_token=archive-tok-3");

        $response->assertSuccessful();
        $this->assertNotNull(Group::find($idgroups)->archived_at);
    }

    public function testArchiveUnknownGroupReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'archive-tok-404']);
        $this->actingAs($admin);

        $response = $this->delete('/api/v2/groups/999999?api_token=archive-tok-404');

        $response->assertStatus(404);
    }
}
