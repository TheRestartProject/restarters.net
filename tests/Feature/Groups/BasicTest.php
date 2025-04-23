<?php

namespace Tests\Feature\Groups;

use App\Models\Group;
use App\Models\Network;
use App\Models\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    /**
     * @dataProvider tabProvider
     */
    public function testPageLoads($url, $tab): void
    {
        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $group = Group::factory()->create([
                                                   'latitude' => 50.6325574,
                                                   'longitude' => 5.5796662,
                                                   'approved' => true,
                                               ]);
        $user = User::factory()->create([
                                                 'latitude' => 50.6325574,
                                                 'longitude' => 5.5796662,
                                                 'location' => 'London'
                                             ]);
        $this->actingAs($user);

        $response = $this->get('/group'. $url);

        $props = $this->assertVueProperties($response, [
            [],
            [
                // Can't assert on all-group-tags dev systems might have varying info.
                'your-area' => 'London',
                ':can-create' => 'true',
                ':user-id' => $user->id,
                'tab' => $tab,
                ':network' => 'null',
                ':networks' => '[{"id":' . Network::first()->id . ',"name":"Restarters","description":null,"website":null,"default_language":"en","timezone":"Europe\\/London","created_at":"2021-05-24 12:19:37","updated_at":"2021-05-24 12:19:37","events_push_to_wordpress":0,"include_in_zapier":0,"shortname":"restarters","discourse_group":null,"auto_approve_events":0,"logo":null}]',
                ':show-tags' => 'false',
            ],
        ]);

        $groups = json_decode($props[1][':all-groups'], true);
        $this->assertEquals($group->idgroups, $groups[0]['idgroups']);
        $this->assertEquals(0, $groups[0]['location']['distance']);
    }


    public function tabProvider(): array {
        return [
            ['', 'mine'],
            ['/all', 'all'],
            ['/mine', 'mine'],
            ['/nearby','nearby'],
        ];
    }
}
