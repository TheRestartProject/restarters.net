<?php

namespace Tests\Feature\Users;

use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class APIv2UserPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function testRequiresAuth(): void
    {
        $response = $this->patchJson('/api/v2/users/me/password', [
            'current_password' => 'whatever',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'newPassword123',
        ]);
        $response->assertStatus(401);
    }

    public function testWrongCurrentPasswordReturns422(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'password' => Hash::make('originalPassword'),
        ]);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/password?api_token=tok1', [
            'current_password' => 'wrongPassword',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'newPassword123',
        ]);

        $response->assertStatus(422);

        // Password must NOT have changed.
        $this->assertTrue(Hash::check('originalPassword', $user->fresh()->password));
    }

    public function testMismatchedConfirmationReturns422(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'password' => Hash::make('originalPassword'),
        ]);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/password?api_token=tok1', [
            'current_password' => 'originalPassword',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'somethingElse',
        ]);

        $response->assertStatus(422);

        // Password must NOT have changed.
        $this->assertTrue(Hash::check('originalPassword', $user->fresh()->password));
    }

    public function testSuccessfulChangePersistsNewPassword(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'password' => Hash::make('originalPassword'),
        ]);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/password?api_token=tok1', [
            'current_password' => 'originalPassword',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'newPassword123',
        ]);

        $response->assertSuccessful();

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('newPassword123', $fresh->password));
        $this->assertFalse(Hash::check('originalPassword', $fresh->password));
    }

    public function testResponseNeverContainsPasswordOrHash(): void
    {
        $user = User::factory()->host()->create([
            'api_token' => 'tok1',
            'password' => Hash::make('originalPassword'),
        ]);
        $this->actingAs($user);

        $response = $this->patchJson('/api/v2/users/me/password?api_token=tok1', [
            'current_password' => 'originalPassword',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'newPassword123',
        ]);

        $response->assertSuccessful();

        $data = $response->json();
        $this->assertArrayNotHasKey('password', $data['data'] ?? []);
        $this->assertArrayNotHasKey('current_password', $data['data'] ?? []);
        $this->assertArrayNotHasKey('new_password', $data['data'] ?? []);
    }

    public function testCannotChangeAnotherUsersPassword(): void
    {
        // The API endpoint always operates on Auth::user() (no id param), so there is
        // no way to target another user's password - confirm the attacker's own
        // password changes, not some other id they might try to smuggle in.
        $attacker = User::factory()->restarter()->create([
            'api_token' => 'tok1',
            'password' => Hash::make('attackerPass'),
        ]);
        $victim = User::factory()->restarter()->create([
            'password' => Hash::make('victimPass'),
        ]);
        $this->actingAs($attacker);

        $response = $this->patchJson('/api/v2/users/me/password?api_token=tok1', [
            'id' => $victim->id,
            'current_password' => 'attackerPass',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'newPassword123',
        ]);

        $response->assertSuccessful();

        // Victim's password must be untouched.
        $this->assertTrue(Hash::check('victimPass', $victim->fresh()->password));
        // Attacker's own password changed instead.
        $this->assertTrue(Hash::check('newPassword123', $attacker->fresh()->password));
    }
}
