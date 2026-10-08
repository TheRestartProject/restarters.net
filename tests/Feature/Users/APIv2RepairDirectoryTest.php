<?php

namespace Tests\Feature\Users;

use App\Role;
use App\User;
use Tests\TestCase;

class APIv2RepairDirectoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testOptionsRequiresAuth(): void
    {
        $u = User::factory()->host()->create();
        $response = $this->getJson('/api/v2/users/' . $u->id . '/repair-directory-options');
        $response->assertStatus(401);
    }

    public function testOptions404ForMissingUser(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $admin->repairdir_role = Role::REPAIR_DIRECTORY_SUPERADMIN;
        $admin->save();
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/users/999999/repair-directory-options?api_token=tok1');
        $response->assertStatus(404);
    }

    public function testOptionsForSuperAdminAllEnabled(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $admin->repairdir_role = Role::REPAIR_DIRECTORY_SUPERADMIN;
        $admin->save();
        $victim = User::factory()->host()->create();
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/users/' . $victim->id . '/repair-directory-options?api_token=tok1');
        $response->assertSuccessful();

        $options = $response->json('data.options');
        $this->assertCount(4, $options);
        foreach ($options as $opt) {
            $this->assertFalse($opt['disabled'], 'SuperAdmin should be allowed to set every role; got disabled for ' . $opt['value']);
        }
    }

    public function testOptionsForNonAdminAllDisabled(): void
    {
        $user = User::factory()->host()->create(['api_token' => 'tok1']);
        $victim = User::factory()->host()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/users/' . $victim->id . '/repair-directory-options?api_token=tok1');
        $response->assertSuccessful();

        foreach ($response->json('data.options') as $opt) {
            $this->assertTrue($opt['disabled']);
        }
    }

    public function testUpdateForbiddenForUngatedRole(): void
    {
        $regional = User::factory()->host()->create(['api_token' => 'tok1']);
        $regional->repairdir_role = Role::REPAIR_DIRECTORY_REGIONAL_ADMIN;
        $regional->save();
        $victim = User::factory()->host()->create();
        $this->actingAs($regional);

        // Regional admins can't promote others to SUPERADMIN.
        $response = $this->patchJson('/api/v2/users/' . $victim->id . '/repair-directory-role?api_token=tok1', [
            'role' => Role::REPAIR_DIRECTORY_SUPERADMIN,
        ]);
        $response->assertStatus(403);
    }

    public function testUpdatePersistsAllowedRole(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $admin->repairdir_role = Role::REPAIR_DIRECTORY_SUPERADMIN;
        $admin->save();
        $victim = User::factory()->host()->create();
        $this->actingAs($admin);

        $response = $this->patchJson('/api/v2/users/' . $victim->id . '/repair-directory-role?api_token=tok1', [
            'role' => Role::REPAIR_DIRECTORY_EDITOR,
        ]);
        $response->assertSuccessful();
        $this->assertEquals(Role::REPAIR_DIRECTORY_EDITOR, $response->json('data.role'));
        $this->assertEquals(Role::REPAIR_DIRECTORY_EDITOR, $victim->fresh()->repairdir_role());
    }

    public function testUpdateValidatesRoleEnum(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'tok1']);
        $admin->repairdir_role = Role::REPAIR_DIRECTORY_SUPERADMIN;
        $admin->save();
        $victim = User::factory()->host()->create();
        $this->actingAs($admin);

        $response = $this->patchJson('/api/v2/users/' . $victim->id . '/repair-directory-role?api_token=tok1', [
            'role' => 99,
        ]);
        $response->assertStatus(422);
    }
}
