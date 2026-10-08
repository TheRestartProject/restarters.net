<?php

namespace Tests\Feature\Auth;

use App\Events\PasswordChanged;
use App\EventsUsers;
use App\Group;
use App\Invite;
use App\Notifications\ResetPassword;
use App\Party;
use App\User;
use App\UserGroups;
use DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        // The endpoints are rate-limited (throttle:10,1); the limiter's cache
        // survives across tests in one run, so disable it here.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        DB::statement('SET foreign_key_checks=0');
        DB::table('personal_access_tokens')->truncate();
        Invite::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    private function makeUser(string $email = 'user@restarters.test', string $password = 'passw0rd'): User
    {
        return User::factory()->restarter()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }

    public function testLoginSucceedsWithoutAnyCsrfToken(): void
    {
        $user = $this->makeUser();
        $loginsBefore = $user->number_of_logins;

        // Simulate the cross-origin SPA: JSON post, Origin header, no session,
        // no CSRF token. This pins the design decision that the auth endpoints
        // live in the CSRF-free api middleware group.
        $response = $this->post('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'passw0rd',
        ], ['Origin' => 'http://localhost:3000', 'Accept' => 'application/json']);

        $response->assertOk();
        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertEquals($user->id, $response->json('data.user.id'));

        // The token authenticates API calls.
        $check = $this->getJson('/api/users/me', ['Authorization' => 'Bearer '.$token]);
        $check->assertOk();
        $this->assertEquals($user->id, $check->json('id'));

        // Login bookkeeping happened without the Login event.
        $user->refresh();
        $this->assertEquals($loginsBefore + 1, $user->number_of_logins);
        $this->assertNotNull($user->last_login_at);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    public function testLoginHoneypotRejectsBots(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'passw0rd',
            'my_name' => 'Bot McBotface',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
    }

    public function testRegisterCreatesUserWithConsentAndSkillsRole(): void
    {
        Notification::fake();

        $response = $this->post('/api/v2/auth/register', [
            'name' => 'New Volunteer',
            'email' => 'newbie@restarters.test',
            'password' => 'longenough',
            'password_confirmation' => 'longenough',
            'age' => '1980',
            'country' => 'GBR',
            'city' => 'London',
            'consent_gdpr' => true,
            'consent_future_data' => true,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        $user = User::where('email', 'newbie@restarters.test')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->consent_gdpr);
        $this->assertNotNull($user->consent_future_data);
        $this->assertNotEmpty($user->username);
        // GeocoderMock resolves London deterministically.
        $this->assertNotNull($user->latitude);

        $check = $this->getJson('/api/users/me', ['Authorization' => 'Bearer '.$token]);
        $check->assertOk();
    }

    public function testRegisterRejectsDuplicateEmailAndMissingConsent(): void
    {
        $this->makeUser('taken@restarters.test');

        $dup = $this->post('/api/v2/auth/register', [
            'name' => 'Dup',
            'email' => 'taken@restarters.test',
            'password' => 'longenough',
            'password_confirmation' => 'longenough',
            'age' => '1980',
            'country' => 'GBR',
            'consent_gdpr' => true,
            'consent_future_data' => true,
        ], ['Accept' => 'application/json']);
        $dup->assertStatus(422);

        $noConsent = $this->post('/api/v2/auth/register', [
            'name' => 'No Consent',
            'email' => 'noconsent@restarters.test',
            'password' => 'longenough',
            'password_confirmation' => 'longenough',
            'age' => '1980',
            'country' => 'GBR',
        ], ['Accept' => 'application/json']);
        $noConsent->assertStatus(422);
    }

    public function testRegisterWithInviteHashJoinsGroupAndDeletesInvite(): void
    {
        Notification::fake();
        $group = Group::factory()->create();
        Invite::create([
            'record_id' => $group->idgroups,
            'email' => '',
            'hash' => 'invitehash123',
            'type' => 'group',
        ]);

        $response = $this->post('/api/v2/auth/register', [
            'name' => 'Invited Person',
            'email' => 'invited@restarters.test',
            'password' => 'longenough',
            'password_confirmation' => 'longenough',
            'age' => '1980',
            'country' => 'GBR',
            'consent_gdpr' => true,
            'consent_future_data' => true,
            'invite_hash' => 'invitehash123',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $this->assertEquals('group', $response->json('data.invite.type'));

        $user = User::where('email', 'invited@restarters.test')->first();
        $this->assertTrue(
            UserGroups::where('user', $user->id)->where('group', $group->idgroups)->where('status', 1)->exists()
        );
        $this->assertNull(Invite::where('hash', 'invitehash123')->first());
    }

    public function testLoginWithShareableCodeJoinsExistingUser(): void
    {
        // The regression the session-based flow would cause: an EXISTING user
        // follows a shared invite link and logs in — they must still join.
        $group = Group::factory()->create(['shareable_code' => 'sharecode1']);
        $user = $this->makeUser('member@restarters.test');

        $response = $this->post('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'passw0rd',
            'invite_code' => 'sharecode1',
            'invite_type' => 'group',
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertEquals('group', $response->json('data.invite.type'));
        $this->assertFalse($response->json('data.invite.already_member'));
        $this->assertTrue(
            UserGroups::where('user', $user->id)->where('group', $group->idgroups)->where('status', 1)->exists()
        );
    }

    public function testClaimInviteEndpoint(): void
    {
        $group = Group::factory()->create(['shareable_code' => 'sharecode2']);
        $user = $this->makeUser('claimer@restarters.test');
        $token = $user->createToken('spa')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];

        // Unauthenticated → 401.
        $this->post('/api/v2/invites/claim', ['invite_code' => 'sharecode2', 'invite_type' => 'group'], ['Accept' => 'application/json'])
            ->assertStatus(401);

        // First claim joins.
        $first = $this->post('/api/v2/invites/claim', ['invite_code' => 'sharecode2', 'invite_type' => 'group'], $headers);
        $first->assertOk();
        $this->assertFalse($first->json('data.already_member'));

        // Second claim reports already a member.
        $second = $this->post('/api/v2/invites/claim', ['invite_code' => 'sharecode2', 'invite_type' => 'group'], $headers);
        $second->assertOk();
        $this->assertTrue($second->json('data.already_member'));

        // Unknown code → 404.
        $this->post('/api/v2/invites/claim', ['invite_code' => 'nope', 'invite_type' => 'group'], $headers)
            ->assertStatus(404);
    }

    public function testClaimEventShareableCode(): void
    {
        $group = Group::factory()->create();
        $party = Party::factory()->create([
            'group' => $group->idgroups,
            'shareable_code' => 'eventcode1',
        ]);
        $user = $this->makeUser('eventgoer@restarters.test');
        $token = $user->createToken('spa')->plainTextToken;

        $response = $this->post('/api/v2/invites/claim', [
            'invite_code' => 'eventcode1',
            'invite_type' => 'event',
        ], ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']);

        $response->assertOk();
        $this->assertEquals('event', $response->json('data.type'));
        $this->assertTrue(
            EventsUsers::where('user', $user->id)->where('event', $party->idevents)->where('status', 1)->exists()
        );
    }

    public function testForgotPasswordSendsRecoveryEmail(): void
    {
        Notification::fake();
        $user = $this->makeUser('forgetful@restarters.test');

        $unknown = $this->post('/api/v2/auth/password/forgot', [
            'email' => 'nobody@restarters.test',
        ], ['Accept' => 'application/json']);
        $unknown->assertStatus(422);

        $response = $this->post('/api/v2/auth/password/forgot', [
            'email' => $user->email,
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $user->refresh();
        $this->assertNotEmpty($user->recovery);
        $this->assertTrue(strtotime($user->recovery_expires) > time());
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function testResetPasswordWithValidToken(): void
    {
        Event::fake([PasswordChanged::class]);
        $user = $this->makeUser('resetme@restarters.test');
        $user->update([
            'recovery' => 'validrecoverytoken',
            'recovery_expires' => date('Y-m-d H:i:s', time() + 3600),
        ]);

        $response = $this->post('/api/v2/auth/password/reset', [
            'recovery' => 'validrecoverytoken',
            'password' => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        Event::assertDispatched(PasswordChanged::class);

        $login = $this->post('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'brandnewpass',
        ], ['Accept' => 'application/json']);
        $login->assertOk();
    }

    public function testResetPasswordTokenCannotBeReplayed(): void
    {
        $user = $this->makeUser('replay@restarters.test');
        $user->update([
            'recovery' => 'onetimetoken',
            'recovery_expires' => date('Y-m-d H:i:s', time() + 3600),
        ]);

        // Legitimate first use succeeds.
        $this->post('/api/v2/auth/password/reset', [
            'recovery' => 'onetimetoken',
            'password' => 'victimnewpass',
            'password_confirmation' => 'victimnewpass',
        ], ['Accept' => 'application/json'])->assertOk();

        // The same emailed link must not be replayable within its 24h window:
        // the recovery token is rotated on use, so a second reset with the old
        // token is rejected - an attacker who later obtains the link cannot
        // take over the (already-reset) account.
        $this->withExceptionHandling();
        $this->post('/api/v2/auth/password/reset', [
            'recovery' => 'onetimetoken',
            'password' => 'attackerpass',
            'password_confirmation' => 'attackerpass',
        ], ['Accept' => 'application/json'])->assertStatus(422);

        // The victim's password (from the legitimate first reset) still works;
        // the attacker's replay did not take effect.
        $this->post('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'victimnewpass',
        ], ['Accept' => 'application/json'])->assertOk();
    }

    public function testResetPasswordRejectsExpiredOrUnknownToken(): void
    {
        $user = $this->makeUser('expired@restarters.test');
        $user->update([
            'recovery' => 'expiredtoken',
            'recovery_expires' => date('Y-m-d H:i:s', time() - 3600),
        ]);

        $expired = $this->post('/api/v2/auth/password/reset', [
            'recovery' => 'expiredtoken',
            'password' => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ], ['Accept' => 'application/json']);
        $expired->assertStatus(422);

        $unknown = $this->post('/api/v2/auth/password/reset', [
            'recovery' => 'neverexisted',
            'password' => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ], ['Accept' => 'application/json']);
        $unknown->assertStatus(422);
    }

    public function testRecoveryInfoForValidToken(): void
    {
        $user = $this->makeUser('recoverypreview@restarters.test');
        $user->update([
            'recovery' => 'previewtoken',
            'recovery_expires' => date('Y-m-d H:i:s', time() + 3600),
        ]);

        $response = $this->get('/api/v2/auth/password/recovery/previewtoken', ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertTrue($response->json('data.valid'));
        $this->assertEquals($user->email, $response->json('data.email'));
    }

    public function testRecoveryInfoForUnknownToken(): void
    {
        $response = $this->get('/api/v2/auth/password/recovery/neverexisted', ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertFalse($response->json('data.valid'));
        $this->assertNull($response->json('data.email'));
    }

    public function testRecoveryInfoForExpiredToken(): void
    {
        $this->makeUser('recoveryexpired@restarters.test')->update([
            'recovery' => 'expiredpreview',
            'recovery_expires' => date('Y-m-d H:i:s', time() - 3600),
        ]);

        $response = $this->get('/api/v2/auth/password/recovery/expiredpreview', ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertFalse($response->json('data.valid'));
        $this->assertNull($response->json('data.email'));
    }

    public function testEmailAvailable(): void
    {
        $this->makeUser('taken2@restarters.test');

        $taken = $this->get('/api/v2/auth/email-available?email=taken2@restarters.test', ['Accept' => 'application/json']);
        $taken->assertOk();
        $this->assertFalse($taken->json('data.available'));

        $free = $this->get('/api/v2/auth/email-available?email=free@restarters.test', ['Accept' => 'application/json']);
        $free->assertOk();
        $this->assertTrue($free->json('data.available'));
    }

    public function testLogoutRevokesSanctumToken(): void
    {
        $user = $this->makeUser('leaver@restarters.test');
        $token = $user->createToken('spa')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];

        $this->getJson('/api/users/me', $headers)->assertOk();

        $this->post('/api/v2/auth/logout', [], $headers)->assertOk();

        // The auth manager caches resolved guards between in-test requests;
        // forget them so the revoked token is genuinely re-evaluated (same
        // workaround as APIv2GroupPermissionsTest).
        app('auth')->forgetGuards();

        $this->getJson('/api/users/me', $headers)->assertStatus(401);
    }
}
