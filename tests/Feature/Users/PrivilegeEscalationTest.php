<?php

namespace Tests\Feature\Users;

use App\Role;
use App\Skills;
use App\User;
use App\UsersSkills;
use DB;
use Illuminate\Support\Facades\Hash;
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

    // -------------------------------------------------------------------------
    // F001: Any user can soft-delete any other user via POST /user/soft-delete
    // -------------------------------------------------------------------------

    /** @test */
    public function restarter_cannot_soft_delete_another_user(): void
    {
        $this->withExceptionHandling();

        $attacker = User::factory()->restarter()->create();
        $victim   = User::factory()->restarter()->create();

        $this->actingAs($attacker);

        $response = $this->post('/user/soft-delete', ['id' => $victim->id]);

        $response->assertStatus(403);
        // Victim must NOT be soft-deleted.
        $this->assertFalse(User::withTrashed()->find($victim->id)->trashed());
    }

    /** @test */
    public function admin_can_soft_delete_another_user(): void
    {
        $admin  = User::factory()->administrator()->create();
        $victim = User::factory()->restarter()->create();

        $this->actingAs($admin);

        $response = $this->post('/user/soft-delete', ['id' => $victim->id]);

        $response->assertRedirect('user/all');
        // Victim is soft-deleted.
        $this->assertTrue(User::withTrashed()->find($victim->id)->trashed());
    }

    /** @test */
    public function user_can_soft_delete_themselves(): void
    {
        $user = User::factory()->restarter()->create();

        $this->actingAs($user);

        $response = $this->post('/user/soft-delete', ['id' => $user->id]);

        $response->assertRedirect('login');
    }

    // -------------------------------------------------------------------------
    // F002: A Host can edit/reset the password of any unrelated user via edit()
    // -------------------------------------------------------------------------

    /** @test */
    public function host_cannot_reset_unrelated_users_password_via_edit_endpoint(): void
    {
        $this->withExceptionHandling();

        $host   = User::factory()->host()->create();
        $victim = User::factory()->restarter()->create(['password' => bcrypt('originalPassword')]);

        $this->actingAs($host);

        $response = $this->post('/user/edit/' . $victim->id, [
            'name'             => $victim->name,
            'email'            => $victim->email,
            'groups'           => [],
            'new-password'     => 'hackedPassword',
            'password-confirm' => 'hackedPassword',
        ]);

        $response->assertStatus(403);
        $this->assertTrue(Hash::check('originalPassword', $victim->fresh()->password));
    }

    /** @test */
    public function host_cannot_edit_unrelated_users_profile_via_edit_endpoint(): void
    {
        $this->withExceptionHandling();

        $host   = User::factory()->host()->create();
        $victim = User::factory()->restarter()->create(['name' => 'Original Name']);

        $this->actingAs($host);

        $response = $this->post('/user/edit/' . $victim->id, [
            'name'   => 'Hacked Name',
            'email'  => $victim->email,
            'groups' => [],
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Original Name', $victim->fresh()->name);
    }

    /** @test */
    public function admin_can_reset_another_users_password_via_edit_endpoint(): void
    {
        $GLOBALS['_FILES'] = [];

        $admin  = User::factory()->administrator()->create();
        $target = User::factory()->restarter()->create(['password' => bcrypt('originalPassword')]);

        $this->actingAs($admin);

        $response = $this->post('/user/edit/' . $target->id, [
            'name'             => $target->name,
            'email'            => $target->email,
            'groups'           => [],
            'new-password'     => 'newPassword123',
            'password-confirm' => 'newPassword123',
        ]);

        $this->assertFalse($response->isClientError());
        $this->assertTrue(Hash::check('newPassword123', $target->fresh()->password));
    }

    /** @test */
    public function user_can_reset_own_password_via_edit_endpoint(): void
    {
        $GLOBALS['_FILES'] = [];

        $user = User::factory()->restarter()->create(['password' => bcrypt('originalPassword')]);

        $this->actingAs($user);

        $response = $this->post('/user/edit/' . $user->id, [
            'name'             => $user->name,
            'email'            => $user->email,
            'groups'           => [],
            'new-password'     => 'newPassword123',
            'password-confirm' => 'newPassword123',
        ]);

        $this->assertFalse($response->isClientError());
        $this->assertTrue(Hash::check('newPassword123', $user->fresh()->password));
    }

    // -------------------------------------------------------------------------
    // F004: Any user can overwrite any other user's skills via edit-tags
    // -------------------------------------------------------------------------

    /** @test */
    public function restarter_cannot_edit_another_users_tags(): void
    {
        $this->withExceptionHandling();

        $attacker = User::factory()->restarter()->create();
        $victim   = User::factory()->restarter()->create();

        $this->actingAs($attacker);

        $response = $this->post('/profile/edit-tags', [
            'id'   => $victim->id,
            'tags' => [1],
        ]);

        $response->assertStatus(403);
        $this->assertEmpty(UsersSkills::where('user', $victim->id)->get());
    }

    /** @test */
    public function user_can_edit_own_tags_and_self_promote_to_host(): void
    {
        $user = User::factory()->restarter()->create();

        // A category-1 ("organising") skill qualifies the user for the Host role by design.
        $skill = Skills::create([
            'skill_name'  => 'UT Host Skill',
            'category'    => 1,
            'description' => 'Organising',
        ]);

        $this->actingAs($user);

        $response = $this->post('/profile/edit-tags', [
            'id'   => $user->id,
            'tags' => [$skill->id],
        ]);

        $this->assertTrue($response->isRedirection());
        $this->assertEquals(Role::HOST, $user->fresh()->role);
    }

    // -------------------------------------------------------------------------
    // F006: Any user can change any other user's language via edit-language
    // -------------------------------------------------------------------------

    /** @test */
    public function restarter_cannot_change_another_users_language(): void
    {
        $this->withExceptionHandling();

        $attacker = User::factory()->restarter()->create();
        $victim   = User::factory()->restarter()->create(['language' => 'en']);

        $this->actingAs($attacker);

        $response = $this->post('/profile/edit-language', [
            'id'            => $victim->id,
            'user_language' => 'fr',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('en', $victim->fresh()->language);
    }

    /** @test */
    public function user_can_change_own_language(): void
    {
        $user = User::factory()->restarter()->create(['language' => 'en']);

        $this->actingAs($user);

        $response = $this->post('/profile/edit-language', [
            'id'            => $user->id,
            'user_language' => 'fr',
        ]);

        $this->assertTrue($response->isRedirection());
        $this->assertEquals('fr', $user->fresh()->language);
    }

    /** @test */
    public function admin_can_change_another_users_language(): void
    {
        $admin  = User::factory()->administrator()->create();
        $victim = User::factory()->restarter()->create(['language' => 'en']);

        $this->actingAs($admin);

        $response = $this->post('/profile/edit-language', [
            'id'            => $victim->id,
            'user_language' => 'fr',
        ]);

        $this->assertTrue($response->isRedirection());
        $this->assertEquals('fr', $victim->fresh()->language);
    }

    // -------------------------------------------------------------------------
    // F007: Any user can toggle any other user's invites preference
    // -------------------------------------------------------------------------

    /** @test */
    public function restarter_cannot_toggle_another_users_invites(): void
    {
        $this->withExceptionHandling();

        $attacker = User::factory()->restarter()->create();
        $victim   = User::factory()->restarter()->create(['invites' => 1]);

        $this->actingAs($attacker);

        // Omitting 'invites' attempts to set it to 0.
        $response = $this->post('/profile/edit-preferences', ['id' => $victim->id]);

        $response->assertStatus(403);
        $this->assertEquals(1, $victim->fresh()->invites);
    }

    /** @test */
    public function user_can_toggle_own_invites(): void
    {
        $user = User::factory()->restarter()->create(['invites' => 1]);

        $this->actingAs($user);

        $response = $this->post('/profile/edit-preferences', ['id' => $user->id]);

        $this->assertTrue($response->isRedirection());
        $this->assertEquals(0, $user->fresh()->invites);
    }
}
