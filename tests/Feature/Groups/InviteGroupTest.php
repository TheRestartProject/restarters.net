<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Group;
use App\Helpers\Fixometer;
use App\Helpers\Geocoder;
use App\Network;
use App\Notifications\AdminModerationEvent;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Auth;

class InviteGroupTest extends TestCase
{
    public function testInvite()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        // Invite a user.
        $user = factory(User::class)->states('Restarter')->create();

        $response = $this->post('/group/invite', [
            'group_name' => $group->name,
            'group_id' => $group->idgroups,
            'manual_invite_box' => $user->email,
            'message_to_restarters' => 'Join us, but not in a creepy zombie way',
        ]);

        $response->assertSessionHas('success');

        // We should see that we have been invited.
        $this->actingAs($user);
        $response2 = $this->get('/group/view/'.$group->idgroups);
        $response2->assertSee('You have an invitation to this group.');

        // Check the counts.
        $props = $this->assertVueProperties($response2, [
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        $initialGroup = json_decode($props[0][':initial-group'], true);
        $this->assertEquals(0, $initialGroup['all_hosts_count']);
        $this->assertEquals(0, $initialGroup['all_confirmed_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_restarters_count']);
        $this->assertEquals(0, $initialGroup['all_confirmed_restarters_count']);

        // Now accept the invite.
        preg_match('/href="(\/group\/accept-invite.*?)"/', $response2->getContent(), $matches);
        $invitation = $matches[1];
        $response3 = $this->get($invitation);
        $this->assertTrue($response3->isRedirection());
        $redirectTo = $response3->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/group/view/'.$group->idgroups));
        $response3->assertSessionHas('success');

        // Check the counts have changed.
        $response4 = $this->get('/group/view/'.$group->idgroups);
        $props = $this->assertVueProperties($response4, [
            [
                ':idgroups' => $group->idgroups,
            ],
        ]);

        $initialGroup = json_decode($props[0][':initial-group'], true);
        $this->assertEquals(0, $initialGroup['all_hosts_count']);
        $this->assertEquals(0, $initialGroup['all_confirmed_hosts_count']);
        $this->assertEquals(1, $initialGroup['all_restarters_count']);
        $this->assertEquals(1, $initialGroup['all_confirmed_restarters_count']);
    }

    public function testInviteViaLink() {
        $group = factory(Group::class)->create();

        $unique_shareable_code = Fixometer::generateUniqueShareableCode(\App\Group::class, 'shareable_code');
        $group->update([
           'shareable_code' => $unique_shareable_code,
        ]);

        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        $this->actingAs($host);
        $response = $this->get('/group/view/'.$group->idgroups);

        // Should see shareable code in there.
        $response->assertSee($group->shareable_link);

        // Now pretend we've received that code.
        Auth::logout();
        $response = $this->get($group->shareable_link);

        $this->assertTrue($response->isRedirection());
        $redirectTo = $response->getTargetUrl();
        $this->assertNotFalse(strpos($redirectTo, '/user/register'));
    }
}
