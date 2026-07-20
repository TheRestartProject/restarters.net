<?php

namespace Tests\Feature\Groups;

use App\Network;
use App\User;
use Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the shareable_link/is_member fields added to app/Http/Resources/Group.php for
 * the Nuxt client (api-gaps.md B4/B5 gaps).
 */
class APIv2GroupResourceFieldsTest extends TestCase
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
            'Resource Fields Group '.Str::random(8),
            'https://therestartproject.org',
            'London'
        );
    }

    public function testShareableLinkPointsAtTheFrontend(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $link = $response->json('data.shareable_link');
        $this->assertStringStartsWith(rtrim(config('restarters.frontend_url'), '/').'/group/invite/', $link);
    }

    public function testIsMemberNullForAnonymousUser(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);
        Auth::logout();

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $this->assertNull($response->json('data.is_member'));
    }

    public function testIsMemberTrueForAHost(): void
    {
        $host = User::factory()->host()->create(['api_token' => 'resfields-tok-1']);
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->get("/api/v2/groups/$idgroups?api_token=resfields-tok-1");
        $response->assertSuccessful();

        $this->assertTrue($response->json('data.is_member'));
    }

    public function testIsMemberFalseForANonMemberRestarter(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);
        Auth::logout();

        $restarter = User::factory()->restarter()->create(['api_token' => 'resfields-tok-2']);
        $this->app['auth']->forgetGuards();
        $this->actingAs($restarter);

        $response = $this->get("/api/v2/groups/$idgroups?api_token=resfields-tok-2");
        $response->assertSuccessful();

        $this->assertFalse($response->json('data.is_member'));
    }

    public function testAutoApproveFalseWithNoNetwork(): void
    {
        // getAutoApproveAttribute() (App\Group) returns false for a group with no networks at
        // all - matches develop's "no networks = not auto-approved" behaviour.
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $this->assertFalse($response->json('data.auto_approve'));
    }

    public function testAutoApproveTrueWhenEveryNetworkAutoApproves(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $network = Network::factory()->create(['auto_approve_events' => true]);
        \App\Group::find($idgroups)->networks()->attach($network->id);

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $this->assertTrue($response->json('data.auto_approve'));
    }

    public function testAutoApproveFalseWhenAnyNetworkDoesNotAutoApprove(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $autoApproving = Network::factory()->create(['auto_approve_events' => true]);
        $notAutoApproving = Network::factory()->create(['auto_approve_events' => false]);
        \App\Group::find($idgroups)->networks()->attach([$autoApproving->id, $notAutoApproving->id]);

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $this->assertFalse($response->json('data.auto_approve'));
    }

    public function testDiscourseGroupNullWhenNotLinked(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $this->assertNull($response->json('data.discourse_group'));
    }

    public function testDiscourseGroupReturnedWhenLinked(): void
    {
        $host = User::factory()->host()->create();
        $idgroups = $this->createGroupAsHost($host);

        // discourse_group isn't in Group::$fillable (it's set by direct property assignment in
        // Group::createDiscourseGroup(), not mass-assignment), so update() would silently no-op it.
        $group = \App\Group::find($idgroups);
        $group->discourse_group = 'fixers-united';
        $group->save();

        $response = $this->get("/api/v2/groups/$idgroups");
        $response->assertSuccessful();

        $this->assertEquals('fixers-united', $response->json('data.discourse_group'));
    }
}
