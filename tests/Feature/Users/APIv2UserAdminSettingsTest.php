<?php

namespace Tests\Feature\Users;

use App\Group;
use App\Network;
use App\Permissions;
use App\Preferences;
use App\Role;
use App\User;
use App\UserGroups;
use DB;
use Tests\TestCase;

class APIv2UserAdminSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testRequiresAuth(): void
    {
        $target = User::factory()->restarter()->create();

        $response = $this->patchJson("/api/v2/users/{$target->id}/admin-settings", [
            'user_role' => Role::HOST,
            'assigned_groups' => [],
            'preferences' => [],
            'permissions' => [],
        ]);

        $response->assertStatus(401);
    }

    public function testGetRequiresAuth(): void
    {
        $target = User::factory()->restarter()->create();

        $response = $this->getJson("/api/v2/users/{$target->id}/admin-settings");

        $response->assertStatus(401);
    }

    public function testGetNonAdminForbidden(): void
    {
        $attacker = User::factory()->host()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create();
        $this->actingAs($attacker);

        $response = $this->getJson("/api/v2/users/{$target->id}/admin-settings?api_token=tok1");

        $response->assertStatus(403);
    }

    public function testGetReturnsOptionsAndCurrentSelection(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create();
        $this->actingAs($admin);

        $group = Group::factory()->create();
        $target->groups()->attach($group->idgroups);

        $preferenceId = DB::table('preferences')->insertGetId([
            'name' => 'Test Preference',
            'purpose' => 'Testing',
            'slug' => 'test-preference-get',
        ]);

        $response = $this->getJson("/api/v2/users/{$target->id}/admin-settings?api_token=tok1");
        $response->assertSuccessful();

        $this->assertEquals(Role::RESTARTER, $response->json('data.role'));
        $this->assertContains($group->idgroups, $response->json('data.assigned_groups'));

        $roleValues = array_column($response->json('data.roles'), 'value');
        $this->assertContains(Role::ADMINISTRATOR, $roleValues);

        $groupIds = array_column($response->json('data.groups'), 'id');
        $this->assertContains($group->idgroups, $groupIds);

        $preferenceIds = array_column($response->json('data.preferences_options'), 'id');
        $this->assertContains($preferenceId, $preferenceIds);
    }

    public function testNonAdminForbidden(): void
    {
        $attacker = User::factory()->host()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create();
        $this->actingAs($attacker);

        $response = $this->patchJson("/api/v2/users/{$target->id}/admin-settings?api_token=tok1", [
            'user_role' => Role::ADMINISTRATOR,
            'assigned_groups' => [],
            'preferences' => [],
            'permissions' => [],
        ]);

        $response->assertStatus(403);

        // Target role must NOT have changed - critical privilege-escalation guard.
        $this->assertEquals(Role::RESTARTER, $target->fresh()->role);
    }

    public function testRestarterForbidden(): void
    {
        $attacker = User::factory()->restarter()->create(['api_token' => 'tok1']);
        $this->actingAs($attacker);

        $response = $this->patchJson("/api/v2/users/{$attacker->id}/admin-settings?api_token=tok1", [
            'user_role' => Role::ADMINISTRATOR,
            'assigned_groups' => [],
            'preferences' => [],
            'permissions' => [],
        ]);

        $response->assertStatus(403);
        $this->assertEquals(Role::RESTARTER, $attacker->fresh()->role);
    }

    public function testAdminCanSetRoleGroupsPreferencesPermissions(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create();
        $this->actingAs($admin);

        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        $preferenceId = DB::table('preferences')->insertGetId([
            'name' => 'Test Preference',
            'purpose' => 'Testing',
            'slug' => 'test-preference',
        ]);
        $preference = Preferences::find($preferenceId);

        $permissionId = DB::table('permissions')->insertGetId([
            'permission' => 'Test Permission',
            'purpose' => 'Testing',
            'slug' => 'test-permission',
        ], 'idpermissions');
        $permission = Permissions::where('idpermissions', $permissionId)->first();

        $response = $this->patchJson("/api/v2/users/{$target->id}/admin-settings?api_token=tok1", [
            'user_role' => Role::HOST,
            'assigned_groups' => [$group1->idgroups, $group2->idgroups],
            'preferences' => [$preference->id],
            'permissions' => [$permission->idpermissions],
        ]);

        $response->assertSuccessful();

        $fresh = $target->fresh();
        $this->assertEquals(Role::HOST, $fresh->role);

        $groupIds = $fresh->groups()->pluck('idgroups')->toArray();
        $this->assertEqualsCanonicalizing([$group1->idgroups, $group2->idgroups], $groupIds);

        // preferences()/permissions() are self-referencing BelongsToMany relations onto the
        // users table (a pre-existing quirk of this codebase - see User::preferences()/permissions()),
        // so we assert against the pivot tables directly rather than via the relation.
        $preferenceIds = DB::table('users_preferences')->where('user_id', $target->id)->pluck('preference_id')->toArray();
        $this->assertEquals([$preference->id], $preferenceIds);

        $permissionIds = DB::table('users_permissions')->where('user_id', $target->id)->pluck('permission_id')->toArray();
        $this->assertEquals([$permission->idpermissions], $permissionIds);
    }

    public function testDemotingNetworkCoordinatorDetachesNetworks(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $target = User::factory()->networkCoordinator()->create();
        $this->actingAs($admin);

        $network = Network::factory()->create();
        $target->networks()->attach($network->id);
        $this->assertCount(1, $target->fresh()->networks);

        $response = $this->patchJson("/api/v2/users/{$target->id}/admin-settings?api_token=tok1", [
            'user_role' => Role::HOST,
            'assigned_groups' => [],
            'preferences' => [],
            'permissions' => [],
        ]);

        $response->assertSuccessful();

        $fresh = $target->fresh();
        $this->assertEquals(Role::HOST, $fresh->role);
        $this->assertCount(0, $fresh->networks);
    }

    public function testRestoresSoftDeletedGroupMembershipBeforeSync(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create();
        $this->actingAs($admin);

        $group = Group::factory()->create();

        // Simulate a previous membership that was soft-deleted (e.g. user left the group).
        $membership = UserGroups::create([
            'user' => $target->id,
            'group' => $group->idgroups,
            'status' => 1,
        ]);
        $membership->delete();
        $this->assertTrue($membership->fresh()->trashed());

        $response = $this->patchJson("/api/v2/users/{$target->id}/admin-settings?api_token=tok1", [
            'user_role' => Role::RESTARTER,
            'assigned_groups' => [$group->idgroups],
            'preferences' => [],
            'permissions' => [],
        ]);

        $response->assertSuccessful();

        $restored = UserGroups::where('user', $target->id)->where('group', $group->idgroups)->first();
        $this->assertNotNull($restored);
        $this->assertFalse($restored->trashed());
    }

    public function testValidatesRoleIsRequired(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create();
        $this->actingAs($admin);

        $response = $this->patchJson("/api/v2/users/{$target->id}/admin-settings?api_token=tok1", [
            'assigned_groups' => [],
            'preferences' => [],
            'permissions' => [],
        ]);

        $response->assertStatus(422);
    }

    public function testResponseHidesPii(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $target = User::factory()->restarter()->create();
        $this->actingAs($admin);

        $response = $this->patchJson("/api/v2/users/{$target->id}/admin-settings?api_token=tok1", [
            'user_role' => Role::HOST,
            'assigned_groups' => [],
            'preferences' => [],
            'permissions' => [],
        ]);

        $response->assertSuccessful();

        $json = $response->getContent();
        $this->assertStringNotContainsString('api_token', $json);
        $this->assertStringNotContainsString('"password"', $json);
    }
}
