<?php

namespace Tests\Feature\Role;

use App\Role;
use App\User;
use DB;
use Tests\TestCase;

class APIv2RolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testListRolesAsAdmin(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/roles?api_token=admin1');
        $response->assertSuccessful();

        $data = $response->json('data');
        $this->assertIsArray($data);

        $names = array_column($data, 'name');
        $this->assertContains('Administrator', $names);
        $this->assertContains('Host', $names);
        $this->assertContains('Restarter', $names);

        // Each entry has id + name + permissions array + permissions_list string
        foreach ($data as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('name', $row);
            $this->assertArrayHasKey('permissions', $row);
            $this->assertArrayHasKey('permissions_list', $row);
            $this->assertIsArray($row['permissions']);
        }
    }

    public function testListRolesRequiresAuth(): void
    {
        $response = $this->getJson('/api/v2/roles');
        $response->assertStatus(401);
    }

    public function testListRolesForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/roles?api_token=r1');
        $response->assertStatus(403);
    }

    public function testGetSingleRoleAsAdmin(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/roles/' . Role::HOST . '?api_token=admin1');
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertEquals(Role::HOST, $data['id']);
        $this->assertEquals('Host', $data['name']);
    }

    public function testGetSingleRoleForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/roles/' . Role::HOST . '?api_token=r1');
        $response->assertStatus(403);
    }

    public function testGetMissingRoleReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/roles/99999999?api_token=admin1');
        $response->assertStatus(404);
    }

    public function testListPermissionsAsAdmin(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/v2/permissions?api_token=admin1');
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertIsArray($data);

        // Test DB seeds some permissions; if any exist, each should have id + name
        if (!empty($data)) {
            $this->assertArrayHasKey('id', $data[0]);
            $this->assertArrayHasKey('name', $data[0]);
        }
    }

    public function testListPermissionsForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->getJson('/api/v2/permissions?api_token=r1');
        $response->assertStatus(403);
    }

    public function testUpdateRolePermissionsAsAdmin(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson(
            '/api/v2/roles/' . Role::HOST . '/permissions?api_token=admin1',
            ['permissions' => [4, 6]]
        );
        $response->assertSuccessful();

        // Verify pivot rows in DB
        $this->assertDatabaseHas('roles_permissions', ['role' => Role::HOST, 'permission' => 4]);
        $this->assertDatabaseHas('roles_permissions', ['role' => Role::HOST, 'permission' => 6]);

        // Then replace with [4] only — the removed one should be gone
        $response = $this->putJson(
            '/api/v2/roles/' . Role::HOST . '/permissions?api_token=admin1',
            ['permissions' => [4]]
        );
        $response->assertSuccessful();
        $this->assertDatabaseHas('roles_permissions', ['role' => Role::HOST, 'permission' => 4]);
        $this->assertDatabaseMissing('roles_permissions', ['role' => Role::HOST, 'permission' => 6]);
    }

    public function testUpdateRolePermissionsAllowsEmpty(): void
    {
        // Pre-seed at least one
        DB::insert('INSERT IGNORE INTO roles_permissions (role, permission) VALUES (?, ?)', [Role::HOST, 4]);

        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson(
            '/api/v2/roles/' . Role::HOST . '/permissions?api_token=admin1',
            ['permissions' => []]
        );
        $response->assertSuccessful();

        $count = DB::selectOne('SELECT COUNT(*) as c FROM roles_permissions WHERE role = ?', [Role::HOST])->c;
        $this->assertEquals(0, $count);
    }

    public function testUpdateRolePermissionsRequiresAuth(): void
    {
        $response = $this->putJson(
            '/api/v2/roles/' . Role::HOST . '/permissions',
            ['permissions' => []]
        );
        $response->assertStatus(401);
    }

    public function testUpdateRolePermissionsForbiddenForNonAdmin(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'r1']);
        $this->actingAs($user);

        $response = $this->putJson(
            '/api/v2/roles/' . Role::HOST . '/permissions?api_token=r1',
            ['permissions' => []]
        );
        $response->assertStatus(403);
    }

    public function testUpdateRolePermissionsRejectsUnknownPermission(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson(
            '/api/v2/roles/' . Role::HOST . '/permissions?api_token=admin1',
            ['permissions' => [99999999]]
        );
        $response->assertStatus(422);
    }

    public function testUpdateMissingRoleReturns404(): void
    {
        $admin = User::factory()->administrator()->create(['api_token' => 'admin1']);
        $this->actingAs($admin);

        $response = $this->putJson(
            '/api/v2/roles/99999999/permissions?api_token=admin1',
            ['permissions' => []]
        );
        $response->assertStatus(404);
    }
}
