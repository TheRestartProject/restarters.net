<?php

namespace Tests\Feature;

use App\Events\ApproveEvent;
use App\Events\EditEvent;
use App\Group;
use App\GroupNetwork;
use App\Network;
use App\Party;
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

        // arrange
        $admin = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = Network::factory()->create([
            'shortname' => 'repairtogether',
            'include_in_zapier' => true,
        ]);

        $groupAttributes = Group::factory()->raw();
        $groupAttributes['name'] = 'Lancaster Fixers';

        // act
        Config::set('app.url', 'https://repairtogether.restarters.net');
        \URL::forceRootUrl('https://repairtogether.restarters.net');
        $response = $this->post('/group/create/', $groupAttributes);

        // assert
        $group = Group::where('name', 'Lancaster Fixers')->first();
        $this->assertTrue($group->isMemberOf($network));

        $network1 = Network::factory()->create();
        $this->assertFalse($group->isMemberOf($network1));
    }
}
