<?php

namespace Tests\Feature\Auth;

use App\SsoTicket;
use App\User;
use DB;
use Tests\TestCase;

class SsoBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        DB::statement('SET foreign_key_checks=0');
        DB::table('personal_access_tokens')->truncate();
        DB::table('sso_tickets')->truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    public function testTicketIssueRequiresAuth(): void
    {
        $this->postJson('/api/v2/auth/sso-ticket')->assertStatus(401);
    }

    public function testTicketIssueAndBridgeEstablishesWebSession(): void
    {
        $user = User::factory()->restarter()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $issue = $this->post('/api/v2/auth/sso-ticket', [], [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ]);
        $issue->assertOk();
        $ticket = $issue->json('data.ticket');
        $this->assertNotEmpty($ticket);
        $this->assertStringContainsString('/auth/bridge', $issue->json('data.bridge_url'));

        // The API call must not have left a web session behind.
        app('auth')->forgetGuards();
        $this->assertGuest('web');

        $target = url('/discourse/sso').'?sso=abc&sig=def';
        $response = $this->get('/auth/bridge?ticket='.$ticket.'&redirect='.urlencode($target));

        $response->assertRedirect($target);
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function testTicketIsSingleUse(): void
    {
        $user = User::factory()->restarter()->create();
        $ticket = SsoTicket::issue($user);

        $this->get('/auth/bridge?ticket='.$ticket)->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');

        // Fresh "browser": no session, ticket already consumed → SPA login.
        app('auth')->forgetGuards();
        $this->flushSession();

        $second = $this->get('/auth/bridge?ticket='.$ticket.'&redirect='.urlencode(url('/discourse/sso')));
        $second->assertRedirect();
        $this->assertStringContainsString('/login?redirect=', $second->headers->get('Location'));
        $this->assertGuest('web');
    }

    public function testExpiredTicketRejected(): void
    {
        $user = User::factory()->restarter()->create();
        $ticket = SsoTicket::issue($user);
        DB::table('sso_tickets')->update(['expires_at' => now()->subMinute()]);

        $response = $this->get('/auth/bridge?ticket='.$ticket);

        $this->assertStringContainsString('/login', $response->headers->get('Location'));
        $this->assertGuest('web');
    }

    public function testRedirectAllowlistBlocksOpenRedirect(): void
    {
        $user = User::factory()->restarter()->create();
        $ticket = SsoTicket::issue($user);

        $response = $this->get('/auth/bridge?ticket='.$ticket.'&redirect='.urlencode('https://evil.example.com/phish'));

        // Logged in, but bounced to the SPA rather than the hostile target.
        $this->assertAuthenticatedAs($user, 'web');
        $response->assertRedirect(config('restarters.frontend_url'));
    }

    public function testRedirectAllowlistBlocksSuffixBypass(): void
    {
        $user = User::factory()->restarter()->create();
        $ticket = SsoTicket::issue($user);

        // A hostile host that merely *starts with* the allowlisted frontend
        // origin string (e.g. "https://app.example.com.attacker.com") must NOT
        // pass - str_starts_with without an origin boundary is an open-redirect
        // bypass.
        $frontend = rtrim(config('restarters.frontend_url'), '/');
        $evil = $frontend.'.attacker.example.com/phish';

        $response = $this->get('/auth/bridge?ticket='.$ticket.'&redirect='.urlencode($evil));

        $this->assertAuthenticatedAs($user, 'web');
        $response->assertRedirect(config('restarters.frontend_url'));
    }

    public function testUnauthenticatedDiscourseSsoRedirectsToSpaLogin(): void
    {
        $response = $this->get('/discourse/sso?sso=abc&sig=def');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith(config('restarters.frontend_url').'/login?redirect=', $location);
        $this->assertStringContainsString(urlencode('/discourse/sso'), $location);
    }

    public function testBridgeWithExistingSessionSkipsTicket(): void
    {
        $user = User::factory()->restarter()->create();
        $this->actingAs($user, 'web');

        $target = url('/discourse/sso').'?sso=abc';
        $this->get('/auth/bridge?redirect='.urlencode($target))->assertRedirect($target);
    }
}
