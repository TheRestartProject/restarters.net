<?php

namespace Tests\Feature\Users;

use App\User;
use DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Hardening of the password-recovery flow (POST /api/v2/auth/password/reset and the
 * emailed-link redirector GET /user/reset).
 *
 * The headline issue in the original flow was a type-juggling flaw: the recovery code
 * was passed through filter_var(), which returns false for an array. Laravel casts a
 * false binding to integer 0, and MySQL compares a VARCHAR column to 0 numerically -
 * coercing every non-numeric string to 0 - so `where('recovery', 0)` matched real
 * recovery tokens instead of nothing.
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

        $response = $this->post('/api/v2/auth/password/reset', [
            'recovery' => ['1'],
            'password' => 'attacker-chosen',
            'password_confirmation' => 'attacker-chosen',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $this->assertEquals(
            $originalHash,
            $victim->fresh()->password,
            'An array recovery code must not match any user, let alone reset their password.'
        );
        $this->assertFalse(Hash::check('attacker-chosen', $victim->fresh()->password));
    }

    /** @test */
    public function array_recovery_code_does_not_break_or_disclose_via_the_emailed_link_redirector(): void
    {
        $this->withExceptionHandling();

        $victim = User::factory()->restarter()->create();
        $this->giveLiveRecoveryToken($victim, 'b7d2e4f6a8c0912e3b5d');

        $response = $this->get('/user/reset?recovery[]=1');

        // Redirected on to the SPA without a code, not a 500 from urlencode(array).
        $response->assertRedirect();
        $this->assertStringNotContainsString('recovery=', $response->headers->get('Location'));
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
        $this->post('/api/v2/auth/password/reset', [
            'recovery' => $token,
            'password' => 'first-reset',
            'password_confirmation' => 'first-reset',
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertTrue(Hash::check('first-reset', $user->fresh()->password));

        // The token must now be spent.
        $this->assertNull($user->fresh()->recovery, 'recovery must be cleared after a successful reset');

        $this->post('/api/v2/auth/password/reset', [
            'recovery' => $token,
            'password' => 'second-reset',
            'password_confirmation' => 'second-reset',
        ], ['Accept' => 'application/json'])->assertStatus(422);

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
            'api_token' => 'tok-change',
        ]);
        DB::table('users')->where('id', $user->id)->update([
            'recovery' => null,
            'recovery_expires' => null,
        ]);

        $this->actingAs($user);

        $this->patchJson('/api/v2/users/me/password?api_token=tok-change', [
            'current_password' => 'current-password',
            'new_password' => 'brand-new-password',
            'new_password_confirmation' => 'brand-new-password',
        ])->assertOk();

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
        $stolenBearer = $user->createToken('spa')->plainTextToken;

        $token = 'd2e4f6a8b0c2345d6e7f';
        $this->giveLiveRecoveryToken($user, $token);

        $this->post('/api/v2/auth/password/reset', [
            'recovery' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNotEquals(
            $stolenToken,
            $user->fresh()->api_token,
            'Resetting the password must invalidate an API token that may have been stolen.'
        );
        $this->assertSame(
            0,
            $user->fresh()->tokens()->count(),
            'Resetting the password must revoke the bearer tokens issued before it.'
        );
        $this->assertNotEmpty($stolenBearer);
    }

    /** @test */
    public function api_token_is_rotated_when_the_password_is_changed(): void
    {
        $user = User::factory()->restarter()->create([
            'password' => Hash::make('current-password'),
            'api_token' => 'tok-rotate',
        ]);
        $stolenToken = 'tok-rotate';

        $this->actingAs($user);

        $this->patchJson('/api/v2/users/me/password?api_token=tok-rotate', [
            'current_password' => 'current-password',
            'new_password' => 'brand-new-password',
            'new_password_confirmation' => 'brand-new-password',
        ])->assertOk();

        $this->assertNotEquals($stolenToken, $user->fresh()->api_token);
    }
}
