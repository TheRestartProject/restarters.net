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
        $groupAttributes = factory(Group::class)->raw();
        $groupAttributes['name'] = 'Lancaster Fixers';
        $groupAttributes['wordpress_post_id'] = '99999';
        $group = factory(Group::class)->create([
                                                   'latitude' => 51.5073510,
                                                   'longitude' => -0.1277584,
                                                   'wordpress_post_id' => '99999',
                                               ]);

        // Create two users nearby
        $user1 = factory(User::class)->create([
                                                  'location' => 'London',
                                                  'latitude' => 51.5073509,
                                                  'longitude' => -0.1277583
                                              ]);

        $user2 = factory(User::class)->create([
                                                  'location' => 'London',
                                                  'latitude' => 51.5073510,
                                                  'longitude' => -0.1277584
                                              ]);

        // Create another user further away.
        $user3 = factory(User::class)->create([
                                                  'location' => 'Not London',
                                                  'latitude' => 52.5073510,
                                                  'longitude' => -1.1277584
                                              ]);

        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);

        // Should see the appropriate users in the list of nearby.
        $rsp = $this->get('/group/nearby/' . $group->idgroups);
        $rsp->assertSee(e($user1->name));
        $rsp->assertSee(e($user2->name));
        $rsp->assertDontSee(e($user3->name));

        // Invite one of them.
        $rsp = $this->get('/group/nearbyinvite/' . $group->idgroups . '/' . $user1->id);

        Notification::assertSentTo(
            $user1,
            JoinGroup::class
        );
    }
}