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

        Config::set('audit.console', true);

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

    //         When a new group is created that is in the Restart network, it IS included in the Restart Zapier trigger
    /** @test */
    public function given_restart_network_when_new_group_created_included_in_trigger()
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = factory(User::class)->states('Administrator')->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = factory(Network::class)->create([
            'shortname' => 'restart',
            'include_in_zapier' => true,
        ]);

        $groupAttributes = factory(Group::class)->raw();
        $groupAttributes['name'] = 'Lancaster Fixers';

        // act
        $response = $this->post('/group/create/', $groupAttributes);
        $group = Group::all()->shift();

        // assert
        $response = $this->get('/api/groups/changes?api_token=1234');
        $response->assertSee($group->name);
    }


    //     When a new group is created that is in Repair Together network, it is not included in the Restart Zapier trigger
    /** @test */
    public function given_nonrestart_network_when_new_group_created_not_included_in_trigger()
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = factory(User::class)->states('Administrator')->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = factory(Network::class)->create([
            'shortname' => 'restart',
            'include_in_zapier' => false,
        ]);

        $groupAttributes = factory(Group::class)->raw();
        $groupAttributes['name'] = 'Ghent Fixers';

        // act
        $response = $this->post('/group/create/', $groupAttributes);
        $group = Group::all()->shift();

        // assert
        $response = $this->get('/api/groups/changes?api_token=1234');
        $response->assertDontSee($group->name);
    }

//         When a new user is created that is in the Repair Together network, it is not included in the Restart Zapier trigger
//         When a new user is created that is in the Restart network, it IS included in the Restart Zapier trigger
//         When a new user/group association is created and either the user or the group is not in the Restart network, it isn't included in the Restart Zapier trigger
// When a new user/group association is created for a user in the Restart network joining a group in the Restart network, it IS included in the Restart Zapier trigger
}
