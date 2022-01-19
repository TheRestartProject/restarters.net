<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testPageLoads()
    {
        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $group = factory(Group::class)->create([
                                                   'latitude' => 50.6325574,
                                                   'longitude' => 5.5796662,
                                                   'wordpress_post_id' => '99999',
                                               ]);
        $user = factory(User::class)->create([
                                                 'latitude' => 50.6325574,
                                                 'longitude' => 5.5796662,
                                                 'location' => 'London'
                                             ]);
        $this->actingAs($user);

        $response = $this->get('/group');

        $props = $this->assertVueProperties($response, [
            [
                // Can't assert on all-group-tags dev systems might have varying info.
                'your-area' => 'London',
                ':can-create' => 'false',
                ':user-id' => '1',
                'tab' => 'mine',
                ':network' => 'null',
                ':networks' => '[{"id":1,"name":"Restarters","description":null,"website":null,"default_language":"en","timezone":"Europe\\/London","created_at":"2021-05-24 12:19:37","updated_at":"2021-05-24 12:19:37","events_push_to_wordpress":0,"include_in_zapier":0,"users_push_to_drip":0,"shortname":"restarters","discourse_group":null,"auto_approve_events":0}]',
                ':show-tags' => 'false',
            ],
        ]);

        $groups = json_decode($props[0][':all-groups'], true);
        $this->assertEquals($group->idgroups, $groups[0]['idgroups']);
        $this->assertEquals(0, $groups[0]['distance']);
    }
}
