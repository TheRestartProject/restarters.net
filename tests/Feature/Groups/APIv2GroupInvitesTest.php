<?php

namespace Tests\Feature\Groups;

use App\Invite;
use App\Notifications\JoinGroup;
use App\User;
use App\UserGroups;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class APIv2GroupInvitesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function createGroupAsHost(User $host): int
    {
        $this->actingAs($host);

        return $this->createGroup(
            'Invite Test Group '.Str::random(8),
            'https://therestartproject.org',
            'London'
        );
    }

    public function testRequiresAuthentication(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->postJson("/api/v2/groups/$idgroups/invites", ['emails' => ['a@example.com']]);

        $response->assertStatus(401);
    }

    public function testDeniedForRestarterWithNoPermission(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $restarter = User::factory()->restarter()->create(['api_token' => 'invite-tok-1']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->post("/api/v2/groups/$idgroups/invites?api_token=invite-tok-1", [
            'emails' => ['someone@example.com'],
        ]);

        $response->assertStatus(403);
    }

    public function testUnknownGroupReturns404(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'invite-tok-404']);
        $this->actingAs($host);

        $response = $this->post('/api/v2/groups/999999/invites?api_token=invite-tok-404', [
            'emails' => ['someone@example.com'],
        ]);

        $response->assertStatus(404);
    }

    public function testValidationFailsWithoutEmails(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'invite-tok-422']);
        $idgroups = $this->createGroupAsHost($host);

        // Json variant: a non-JSON-accepting request that fails validation gets redirected
        // (302) rather than returning a 422, unlike the abort(403)/abort(404) cases elsewhere
        // in this file which preserve their status regardless.
        $response = $this->postJson("/api/v2/groups/$idgroups/invites?api_token=invite-tok-422", []);

        $response->assertStatus(422);
    }

    public function testHostCanInviteAnExistingPlatformUser(): void
    {
        Notification::fake();

        $host = User::factory()->host()->create(['api_token' => 'invite-tok-2']);
        $idgroups = $this->createGroupAsHost($host);

        $invitee = User::factory()->restarter()->create([
            'email' => 'invitee@example.com',
            'invites' => 1,
        ]);

        $response = $this->post("/api/v2/groups/$idgroups/invites?api_token=invite-tok-2", [
            'emails' => ['invitee@example.com'],
            'message' => 'Come and join us!',
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.invites_sent'));
        $this->assertEquals([], $response->json('data.invalid'));

        $member = UserGroups::where('group', $idgroups)->where('user', $invitee->id)->first();
        $this->assertNotNull($member);
        // Pending until confirmed: status holds the invite hash, not '1'.
        $this->assertNotEquals('1', (string) $member->status);

        Notification::assertSentTo($invitee, JoinGroup::class);
    }

    public function testHostCanInviteSomeoneNotYetOnThePlatform(): void
    {
        Notification::fake();

        $host = User::factory()->host()->create(['api_token' => 'invite-tok-3']);
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->post("/api/v2/groups/$idgroups/invites?api_token=invite-tok-3", [
            'emails' => ['newperson@example.com'],
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.invites_sent'));

        $invite = Invite::where('email', 'newperson@example.com')->where('type', 'group')->first();
        $this->assertNotNull($invite);
        $this->assertEquals($idgroups, $invite->record_id);

        Notification::assertSentTo($invite, JoinGroup::class);
    }

    public function testInvalidEmailAddressesAreReportedSeparately(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'invite-tok-4']);
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->post("/api/v2/groups/$idgroups/invites?api_token=invite-tok-4", [
            'emails' => ['not-an-email', 'valid@example.com'],
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.invites_sent'));
        $this->assertEquals(['not-an-email'], $response->json('data.invalid'));
    }

    public function testNetworkCoordinatorForGroupCanInvite(): void
    {
        Notification::fake();

        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $network = \App\Network::factory()->create(['shortname' => 'invitenetwork'.Str::random(6)]);
        $network->addGroup(\App\Group::find($idgroups));

        $coordinator = User::factory()->networkCoordinator()->create(['api_token' => 'invite-tok-5']);
        $network->addCoordinator($coordinator);

        $this->app['auth']->forgetGuards();
        $this->actingAs($coordinator);

        $response = $this->post("/api/v2/groups/$idgroups/invites?api_token=invite-tok-5", [
            'emails' => ['coordinvitee@example.com'],
        ]);

        $response->assertSuccessful();
        $this->assertEquals(1, $response->json('data.invites_sent'));
    }
}
