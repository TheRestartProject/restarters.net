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

use DB;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class ZapierNetworkTests extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        Group::truncate();
        Party::truncate();
        Network::truncate();
        UserGroups::truncate();
        DB::statement('delete from audits');
        GroupNetwork::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    // New group is created as part of the network represented by the current domain.
    /** @test */
    public function given_specific_domain_when_group_created_then_it_is_created_as_part_of_corresponding_network()
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = factory(User::class)->states('Administrator')->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = factory(Network::class)->create([
            'shortname' => 'repairtogether',
            'include_in_zapier' => true,
        ]);

        $groupAttributes = factory(Group::class)->raw();
        $groupAttributes['name'] = 'Lancaster Fixers';

        // act
        Config::set('app.url', 'https://repairtogether.restarters.net');
        $response = $this->post('/group/create/', $groupAttributes);

        // assert
        $group = Group::where('name', 'Lancaster Fixers')->first();
        $this->assertTrue($group->isMemberOf($network));

        $network1 = factory(Network::class)->create();
        $this->assertFalse($group->isMemberOf($network1));
    }
}
