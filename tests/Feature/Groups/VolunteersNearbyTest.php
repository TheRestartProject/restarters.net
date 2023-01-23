<?php

namespace Tests\Feature;

use App\Group;
use App\Notifications\JoinGroup;
use App\User;
use DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VolunteersNearbyTest extends TestCase
{
    public function testBasic() {
        Notification::fake();

        // Create a group.
        $groupAttributes = Group::factory()->raw();
        $groupAttributes['name'] = 'Lancaster Fixers';
        $groupAttributes['approved'] = true;
        $group = Group::factory()->create([
                                                   'latitude' => 51.5073510,
                                                   'longitude' => -0.1277584,
                                                   'approved' => true,
                                               ]);

        // Create two users nearby
        $user1 = User::factory()->create([
                                                  'location' => 'London',
                                                  'latitude' => 51.5073509,
                                                  'longitude' => -0.1277583
                                              ]);

        $user2 = User::factory()->create([
                                                  'location' => 'London',
                                                  'latitude' => 51.5073510,
                                                  'longitude' => -0.1277584
                                              ]);

        // Create another user further away.
        $user3 = User::factory()->create([
                                                  'location' => 'Not London',
                                                  'latitude' => 52.5073510,
                                                  'longitude' => -1.1277584
                                              ]);

        $host = User::factory()->host()->create();
        $this->actingAs($host);

        // Should see the appropriate users in the list of nearby.
        $rsp = $this->get('/group/nearby/' . $group->idgroups);
        $rsp->assertSee($user1->name);
        $rsp->assertSee($user2->name);
        $rsp->assertDontSee($user3->name);

        // Invite one of them.
        $rsp = $this->get('/group/nearbyinvite/' . $group->idgroups . '/' . $user1->id);

        Notification::assertSentTo(
            $user1,
            JoinGroup::class
        );
    }
}