<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\Network;
use App\Role;
use App\User;
use Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2GroupPermissionsTest extends TestCase
{
    /**
     * Create a group as an administrator (so we control ownership independently of the users under test).
     */
    private function createGroupAsAdmin(): int
    {
        $admin = User::factory()->administrator()->create([
            'api_token' => 'admintok-' . Str::random(20),
        ]);
        $this->actingAs($admin);

        return $this->createGroup(
            'Permissions Test Group ' . Str::random(8),
            'https://therestartproject.org',
            'London',
            'Some text.',
            true,
            true,
            'info@test.com'
        );
    }

    private function assertFlags($idgroups, $token, $expected): void
    {
        // Laravel's TokenGuard caches the resolved user on the guard instance for the lifetime of the test
        // method (HTTP calls in tests don't reboot the container). Since several tests here issue requests as
        // different users identified purely by api_token, we must forget guards before each such request so
        // the token is actually re-resolved rather than returning a previous request's cached user.
        $this->app['auth']->forgetGuards();

        $response = $token
            ? $this->get("/api/v2/groups/$idgroups?api_token=$token")
            : $this->get("/api/v2/groups/$idgroups");

        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('permissions', $json['data'], 'Response should contain a permissions object: ' . $response->getContent());

        $permissions = $json['data']['permissions'];

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $permissions[$key], "Flag '$key' mismatch. Full permissions: " . json_encode($permissions));
        }
    }

    public function testAnonymousUserSeesNoPermissions(): void
    {
        $idgroups = $this->createGroupAsAdmin();

        // createGroupAsAdmin() left us logged in via actingAs(). Log out so the request is genuinely anonymous.
        Auth::logout();

        $this->assertFlags($idgroups, null, [
            'can_edit' => false,
            'can_demote' => false,
            'can_see_delete' => false,
            'can_perform_delete' => false,
            'can_perform_archive' => false,
        ]);
    }

    public function testNonMemberRestarterSeesNoPermissions(): void
    {
        $idgroups = $this->createGroupAsAdmin();
        Auth::logout();

        $restarter = User::factory()->restarter()->create([
            'api_token' => 'restarter-tok-' . Str::random(20),
        ]);

        $this->assertFlags($idgroups, $restarter->api_token, [
            'can_edit' => false,
            'can_demote' => false,
            'can_see_delete' => false,
            'can_perform_delete' => false,
            'can_perform_archive' => false,
        ]);
    }

    public function testHostOfGroupCanEditButNotDeleteOrArchive(): void
    {
        // createGroup() makes the acting user a host of the group automatically.
        $host = User::factory()->host()->create([
            'api_token' => 'host-tok-' . Str::random(20),
        ]);
        $this->actingAs($host);

        $idgroups = $this->createGroup(
            'Host Owned Group ' . Str::random(8),
            'https://therestartproject.org',
            'London',
            'Some text.',
            true,
            true,
            'info@test.com'
        );

        $this->assertFlags($idgroups, $host->api_token, [
            'can_edit' => true,
            'can_demote' => false,
            'can_see_delete' => false,
            'can_perform_delete' => false,
            'can_perform_archive' => false,
        ]);
    }

    public function testNetworkCoordinatorForGroupCanEditDemoteAndArchive(): void
    {
        $idgroups = $this->createGroupAsAdmin();
        $group = Group::find($idgroups);
        Auth::logout();

        $network = Network::factory()->create([
            'shortname' => 'permtestnetwork' . Str::random(6),
        ]);
        $network->addGroup($group);

        $coordinator = User::factory()->networkCoordinator()->create([
            'api_token' => 'coord-tok-' . Str::random(20),
        ]);
        $network->addCoordinator($coordinator);

        $this->assertFlags($idgroups, $coordinator->api_token, [
            'can_edit' => true,
            'can_demote' => true,
            'can_see_delete' => false,
            'can_perform_delete' => false,
            'can_perform_archive' => true,
        ]);
    }

    public function testCoordinatorForUnrelatedNetworkSeesNoPermissions(): void
    {
        $idgroups = $this->createGroupAsAdmin();
        Auth::logout();

        // A coordinator of some OTHER network (not attached to this group) should see no elevated permissions.
        $otherNetwork = Network::factory()->create([
            'shortname' => 'othernetwork' . Str::random(6),
        ]);
        $coordinator = User::factory()->networkCoordinator()->create([
            'api_token' => 'othercoord-tok-' . Str::random(20),
        ]);
        $otherNetwork->addCoordinator($coordinator);

        $this->assertFlags($idgroups, $coordinator->api_token, [
            'can_edit' => false,
            'can_demote' => false,
            'can_see_delete' => false,
            'can_perform_delete' => false,
            'can_perform_archive' => false,
        ]);
    }

    public function testAdministratorSeesAllPermissionsAndCanDeleteFollowsGroupCanDelete(): void
    {
        $idgroups = $this->createGroupAsAdmin();
        Auth::logout();

        $admin = User::factory()->administrator()->create([
            'api_token' => 'admin2-tok-' . Str::random(20),
        ]);

        // Fresh group with no events/devices - canDelete() should be true.
        $this->assertFlags($idgroups, $admin->api_token, [
            'can_edit' => true,
            'can_demote' => true,
            'can_see_delete' => true,
            'can_perform_delete' => true,
            'can_perform_archive' => true,
        ]);
    }

    public function testAdministratorCannotDeleteGroupWithDeviceEvent(): void
    {
        $idgroups = $this->createGroupAsAdmin();

        $admin = User::factory()->administrator()->create([
            'api_token' => 'admin3-tok-' . Str::random(20),
        ]);
        $this->actingAs($admin);

        $event = \App\Party::factory()->moderated()->create([
            'event_start_utc' => '2000-01-01T10:15:05+05:00',
            'event_end_utc' => '2000-01-01T13:45:05+05:00',
            'group' => $idgroups,
        ]);

        $this->createDevice($event->idevents,
            'misc', null, 1.5, 0, '',
            \App\Device::REPAIR_STATUS_FIXED_STR, null, null, 111);

        $this->assertFlags($idgroups, $admin->api_token, [
            'can_edit' => true,
            'can_demote' => true,
            'can_see_delete' => true,
            'can_perform_delete' => false,
            'can_perform_archive' => true,
        ]);
    }

    public function testSessionAuthenticatedUserAlsoGetsPermissions(): void
    {
        // Confirm the endpoint also resolves permissions for web-session (actingAs) users, not just api_token,
        // since the group view page is loaded via a normal browser session.
        $idgroups = $this->createGroupAsAdmin();

        $admin = User::factory()->administrator()->create([
            'api_token' => 'admin4-tok-' . Str::random(20),
        ]);
        $this->actingAs($admin);

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['data']['permissions']['can_edit']);
        $this->assertTrue($json['data']['permissions']['can_see_delete']);
    }
}
