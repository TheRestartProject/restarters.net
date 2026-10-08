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
                'tab' => $tab,
                ':network' => 'null',
                ':show-tags' => 'false',
            ],
        ]);
    }


    public static function tabProvider(): array {
        return [
            ['', 'mine'],
            ['/all', 'other'],
            ['/mine', 'mine'],
            ['/nearby','other'],
            ['/other', 'other'],
        ];
    }
}
