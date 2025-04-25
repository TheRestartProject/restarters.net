<?php

namespace Tests\Feature\Groups;

use App\Models\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\Network;
use App\Models\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    #[DataProvider('tabProvider')]
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
                ':show-tags' => 'false',
            ],
        ]);

        // Check networks exist in the response
        $networks = json_decode($props[1][':networks'], true);
        $this->assertNotEmpty($networks, 'Networks list should not be empty');
        
        // Check at least one with name "Restarters" exists
        $restartersFound = false;
        foreach ($networks as $network) {
            if ($network['name'] === 'Restarters') {
                $restartersFound = true;
                break;
            }
        }
        $this->assertTrue($restartersFound, 'Networks should contain Restarters');

        // Check groups
        $groups = json_decode($props[1][':all-groups'], true);
        $this->assertNotEmpty($groups, 'Groups list should not be empty');
        
        // Find our created group in the list
        $foundGroup = false;
        $distance = null;
        foreach ($groups as $returnedGroup) {
            if ($returnedGroup['idgroups'] === $group->idgroups) {
                $foundGroup = true;
                $distance = $returnedGroup['location']['distance'];
                break;
            }
        }
        
        $this->assertTrue($foundGroup, 'Created group should be in the returned groups list');
        $this->assertEquals(0, $distance, 'Distance to created group should be 0');
    }

    public static function tabProvider(): array {
        return [
            ['', 'mine'],
            ['/all', 'all'],
            ['/mine', 'mine'],
            ['/nearby','nearby'],
        ];
    }
}
