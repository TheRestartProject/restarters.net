<?php

namespace Tests\Feature\Users;

use App\Role;
use App\User;
use DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Hardening of the custom password-recovery flow (UserController::recover/reset).
 *
 * The headline issue is a type-juggling flaw: `reset()` passed the request value
 * straight to filter_var(), which returns false for an array. Laravel casts a
 * false binding to integer 0, and MySQL compares a VARCHAR column to 0
 * numerically — coercing every non-numeric string to 0 — so
 * `where('recovery', 0)` matched real recovery tokens instead of nothing.
 *
 * Written before the fix; these should fail on the unpatched code.
 */
class PasswordResetHardeningTest extends TestCase
{
    /**
     * Give a user a live recovery token, bypassing the controller so the test
     * states the precondition directly.
     */
    private function giveLiveRecoveryToken(User $user, string $token): void
    {
        DB::table('users')->where('id', $user->id)->update([
            'recovery' => $token,
            'recovery_expires' => date('Y-m-d H:i:s', time() + 3600),
        ]);
    }

    /** @test */
    public function array_recovery_code_cannot_reset_another_users_password(): void
    {
        $this->withExceptionHandling();

        $victim = User::factory()->restarter()->create([
            'password' => Hash::make('victim-original-password'),
        ]);

        // A non-numeric token, which is what MySQL coerces to 0. Every user gets
        // one of these at registration, so this is the normal state of the table.
        $this->giveLiveRecoveryToken($victim, 'a3f5c1d9e7b2408f6a1c');

        $originalHash = $victim->fresh()->password;

        // The exploit: recovery as an array makes filter_var() return false,
        // which becomes the integer 0 in the SQL binding.
        $this->post('/user/reset', [
            'recovery' => ['1'],
            'password' => 'attacker-chosen',
            'confirm_password' => 'attacker-chosen',
        ]);

        $this->assertEquals(
            $originalHash,
            $victim->fresh()->password,
            'An array recovery code must not match any user, let alone reset their password.'
        );
        $this->assertFalse(Hash::check('attacker-chosen', $victim->fresh()->password));
    }

    /** @test */
    public function array_recovery_code_does_not_disclose_a_users_email(): void
    {
        $this->withExceptionHandling();

        $victim = User::factory()->restarter()->create();
        $this->giveLiveRecoveryToken($victim, 'b7d2e4f6a8c0912e3b5d');

        $response = $this->get('/user/reset?recovery[]=1');

        $response->assertDontSee($victim->email, false);
    }

    /** @test */
    public function recovery_code_is_single_use(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->restarter()->create([
            'password' => Hash::make('original'),
        ]);
        $token = 'c1d3e5f7a9b1234c5d6e';
        $this->giveLiveRecoveryToken($user, $token);

        // First use succeeds.
        $this->post('/user/reset', [
            'recovery' => $token,
            'password' => 'first-reset',
            'confirm_password' => 'first-reset',
        ]);
        $this->assertTrue(Hash::check('first-reset', $user->fresh()->password));

        // The token must now be spent.
        $this->assertNull($user->fresh()->recovery, 'recovery must be cleared after a successful reset');

        $this->post('/user/reset', [
            'recovery' => $token,
            'password' => 'second-reset',
            'confirm_password' => 'second-reset',
        ]);

        $this->assertTrue(
            Hash::check('first-reset', $user->fresh()->password),
            'A spent recovery token must not be reusable.'
        );
    }

    /** @test */
    public function changing_your_password_does_not_mint_a_recovery_token(): void
    {
        $user = User::factory()->restarter()->create([
            'password' => Hash::make('current-password'),
        ]);
        DB::table('users')->where('id', $user->id)->update([
            'recovery' => null,
            'recovery_expires' => null,
        ]);

        $this->actingAs($user);

        $this->post('/profile/edit-password', [
            'id' => $user->id,
            'current-password' => 'current-password',
            'new-password' => 'brand-new-password',
            'new-password-repeat' => 'brand-new-password',
        ]);

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertNull(
            $user->fresh()->recovery,
            'A password change must not leave a live password-reset token behind.'
        );
    }

    /** @test */
    public function api_token_is_rotated_when_the_password_is_reset(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->restarter()->create([
            'password' => Hash::make('original'),
        ]);
        $user->ensureAPIToken();
        $stolenToken = $user->fresh()->api_token;
        $this->assertNotEmpty($stolenToken);

        $token = 'd2e4f6a8b0c2345d6e7f';
        $this->giveLiveRecoveryToken($user, $token);

        $this->post('/user/reset', [
            'recovery' => $token,
            'password' => 'new-password',
            'confirm_password' => 'new-password',
        ]);

        $this->assertNotEquals(
            $stolenToken,
            $user->fresh()->api_token,
            'Resetting the password must invalidate an API token that may have been stolen.'
        );
    }

    /** @test */
    public function api_token_is_rotated_when_the_password_is_changed(): void
    {
        $user = User::factory()->restarter()->create([
            'password' => Hash::make('current-password'),
        ]);
        $user->ensureAPIToken();
        $stolenToken = $user->fresh()->api_token;

        $this->actingAs($user);

        $this->post('/profile/edit-password', [
            'id' => $user->id,
            'current-password' => 'current-password',
            'new-password' => 'brand-new-password',
            'new-password-repeat' => 'brand-new-password',
        ]);

        $this->assertNotEquals($stolenToken, $user->fresh()->api_token);
    }
}
