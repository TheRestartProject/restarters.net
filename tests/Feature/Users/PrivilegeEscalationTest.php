<?php

namespace Tests\Feature\Users;

use App\Role;
use App\User;
use DB;
use Tests\TestCase;

/**
 * Tests covering C1 (broken access control on profile edit endpoints),
 * C2 (mass assignment via edit() method), and M1 (sensitive $fillable fields).
 *
 * All tests are written BEFORE the fixes — they should fail on the unpatched code.
 */
class PrivilegeEscalationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    // -------------------------------------------------------------------------
    // C1: Any user can edit any other user's profile by supplying a foreign id
    // -------------------------------------------------------------------------

    /** @test */
    public function restarter_cannot_edit_another_users_info(): void
    {
        $this->withExceptionHandling();

        $attacker = User::factory()->restarter()->create();
        $victim = User::factory()->restarter()->create();

        $this->actingAs($attacker);

        $response = $this->post('/profile/edit-info', [
            'id'    => $victim->id,
            'name'  => 'Hacked Name',
            'email' => $victim->email,
            'age'   => $victim->age,
            'country' => 'GB',
        ]);

        $response->assertStatus(403);
        $this->assertEquals($victim->name, $victim->fresh()->name);
    }

    /** @test */
    public function restarter_cannot_change_another_users_password(): void
    {
        $this->withExceptionHandling();

        $attacker = User::factory()->restarter()->create(['password' => bcrypt('original')]);
        $victim   = User::factory()->restarter()->create(['password' => bcrypt('original')]);

        $this->actingAs($attacker);

        $response = $this->post('/profile/edit-password', [
            'id'                 => $victim->id,
            'current-password'   => 'original',
            'new-password'       => 'hacked123',
            'new-password-repeat'=> 'hacked123',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function restarter_cannot_change_another_users_photo(): void
    {
        $this->withExceptionHandling();

        $attacker = User::factory()->restarter()->create();
        $victim   = User::factory()->restarter()->create();

        $this->actingAs($attacker);

        $response = $this->post('/profile/edit-photo', [
            'id' => $victim->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function restarter_cannot_access_admin_edit_settings(): void
    {
        $this->withExceptionHandling();

        $attacker = User::factory()->restarter()->create();

        $this->actingAs($attacker);

        $response = $this->post('/profile/edit-admin-settings', [
            'id'        => $attacker->id,
            'user_role' => Role::ROOT,
            'assigned_groups' => [],
            'preferences' => [],
            'permissions' => [],
        ]);

        $response->assertStatus(403);
        $this->assertEquals(Role::RESTARTER, $attacker->fresh()->role);
    }

    /** @test */
    public function admin_can_change_role_via_admin_edit_settings(): void
    {
        $admin  = User::factory()->administrator()->create();
        $target = User::factory()->restarter()->create();

        $this->actingAs($admin);

        $response = $this->post('/profile/edit-admin-settings', [
            'id'             => $target->id,
            'user_role'      => Role::HOST,
            'assigned_groups'=> [],
            'preferences'    => [],
            'permissions'    => [],
        ]);

        $this->assertTrue($response->isRedirection());
        $this->assertEquals(Role::HOST, $target->fresh()->role);
    }

    /** @test */
    public function admin_can_edit_another_users_info(): void
    {
        $admin  = User::factory()->administrator()->create();
        $target = User::factory()->restarter()->create();

        $this->actingAs($admin);

        $response = $this->post('/profile/edit-info', [
            'id'      => $target->id,
            'name'    => 'Admin Changed This',
            'email'   => $target->email,
            'age'     => $target->age,
            'country' => 'GB',
        ]);

        $this->assertTrue($response->isRedirection());
        $this->assertEquals('Admin Changed This', $target->fresh()->name);
    }

    // -------------------------------------------------------------------------
    // C2 / M1: Mass assignment via edit() allows setting role, api_token
    // -------------------------------------------------------------------------

    /** @test */
    public function user_cannot_escalate_role_via_edit_endpoint(): void
    {
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        $this->post('/user/edit/' . $user->id, [
            'name'    => $user->name,
            'email'   => $user->email,
            'groups'  => [],
            'role'    => Role::ROOT,
        ]);

        $this->assertEquals(Role::RESTARTER, $user->fresh()->role);
    }

    /** @test */
    public function user_cannot_overwrite_api_token_via_edit_endpoint(): void
    {
        $user = User::factory()->restarter()->create(['api_token' => 'legitimate_token']);
        $this->actingAs($user);

        $this->post('/user/edit/' . $user->id, [
            'name'      => $user->name,
            'email'     => $user->email,
            'groups'    => [],
            'api_token' => 'evil_token',
        ]);

        $this->assertEquals('legitimate_token', $user->fresh()->api_token);
    }
}
