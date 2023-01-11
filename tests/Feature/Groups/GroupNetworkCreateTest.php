<?php

namespace Tests\Feature;

use App\Events\ApproveEvent;
use App\Events\EditEvent;
use App\Group;
use App\GroupNetwork;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;
use Carbon\Carbon;
use DB;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class GroupNetworkCreateTest extends TestCase
{
    // New group is created as part of the network represented by the current domain.

    /** @test */
    public function given_specific_domain_when_group_created_then_it_is_created_as_part_of_corresponding_network()
    {
        $this->withoutExceptionHandling();

        $network = Network::factory()->create([
            'shortname' => 'repairtogether',
            'include_in_zapier' => true,
        ]);

        Config::set('app.url', 'https://repairtogether.restarters.net');
        \URL::forceRootUrl('https://repairtogether.restarters.net');

        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Get the dashboard.  This will result in CheckForRepairNetwork being called, which will set the repair_network in
        // the logged in user.  That will then be picked up by the create API call.
        $response = $this->get('/');

        $admin->refresh();
        self::assertGreaterThan(0, $admin->repair_network);

        $response = $this->post('/api/v2/groups', [
            'name' => 'Lancaster Fixers',
            'location' => 'Lancaster, UK',
            'description' => 'A repair group',
            'timezone' => 'Europe/London'
        ]);

        $response->assertSuccessful();

        // assert
        $group = Group::where('name', 'Lancaster Fixers')->first();
        $this->assertTrue($group->isMemberOf($network));

        $network1 = Network::factory()->create();
        $this->assertFalse($group->isMemberOf($network1));
    }
}
