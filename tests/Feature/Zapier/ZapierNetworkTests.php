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

class ZapierNetworkTests extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Auditing package appears to no longer honour Config::set value.
        // Setting AUDIT_CONSOLE_EVENTS in phpunit.xml.
        // See: https://github.com/owen-it/laravel-auditing/issues/520
        // Config::set('audit.console', true);

        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        Group::truncate();
        Party::truncate();
        Network::truncate();
        UserGroups::truncate();
        DB::statement('delete from audits');
        GroupNetwork::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    //         When a new group is created that is in the Restart network, it IS included in the Restart Zapier trigger

    /** @test */
    public function given_restart_network_when_new_group_created_included_in_trigger(): void
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = Network::factory()->create([
            'shortname' => 'restarters',
            'include_in_zapier' => true,
        ]);

        // Get dashboard, which will set the repair_network in the logged in user.
        $response = $this->get('/');

        // act
        $response = $this->post('/api/v2/groups', [
            'name' => 'Lancaster Fixers',
            'location' => 'Lancaster, UK',
            'description' => 'A repair group',
            'timezone' => 'Europe/London'
        ]);

        $response->assertSuccessful();

        $group = Group::all()->shift();

        // assert
        $response = $this->get('/api/groups/changes?api_token=1234');
        $response->assertSee($group->name, false);
    }

    //     When a new group is created that is in Repair Together network, it is not included in the Restart Zapier trigger

    /** @test */
    public function given_nonrestart_network_when_new_group_created_not_included_in_trigger(): void
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = Network::factory()->create([
            'shortname' => 'restarters',
            'include_in_zapier' => false,
        ]);

        // Get dashboard, which will set the repair_network in the logged in user.
        $response = $this->get('/');

        // act
        $response = $this->post('/api/v2/groups', [
            'name' => 'Ghent Fixers',
            'location' => 'Ghent, Belgium',
            'description' => 'A repair group',
            'timezone' => 'Europe/Brussels'
        ]);

        $response->assertSuccessful();

        $group = Group::all()->shift();

        // assert
        $response = $this->get('/api/groups/changes?api_token=1234');
        $response->assertDontSee($group->name);
    }

    //         When a new user is created that is in the Restart network, it IS included in the Restart Zapier trigger

    /** @test */
    public function given_restart_network_when_new_user_created_then_included_in_trigger(): void
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = Network::factory()->create([
            'shortname' => 'restart',
            'include_in_zapier' => true,
        ]);

        // act
        $user = User::factory()->create([
            'repair_network' => $network->id,
        ]);

        // assert
        $response = $this->get('/api/users/changes?api_token=1234');
        $response->assertSee($user->name, false);
    }

    // When a new user is created that is in the Repair Together network, it is not included in the Restart Zapier trigger

    /** @test */
    public function given_nonrestart_network_when_new_user_created_then_not_included_in_trigger(): void
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = Network::factory()->create([
            'shortname' => 'repairtogether',
            'include_in_zapier' => false,
        ]);

        // act
        $user = User::factory()->create([
            'repair_network' => $network->id,
        ]);

        // assert
        $response = $this->get('/api/users/changes?api_token=1234');
        $response->assertDontSee($user->name, false);
    }

    // When a new user/group association is created for a user in the Restart network joining a group in the Restart network, it IS included in the Restart Zapier trigger

    /** @test */
    public function given_restart_group_and_restart_user_when_user_joins_group_then_included_in_trigger(): void
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network = Network::factory()->create([
            'shortname' => 'restart',
            'include_in_zapier' => true,
        ]);
        $group = Group::factory()->create();
        $network->addGroup($group);
        $user = User::factory()->create([
            'repair_network' => $network->id,
        ]);

        // act
        $group->addVolunteer($user);

        // assert
        $response = $this->get('/api/usersgroups/changes?api_token=1234');
        $response->assertSee($user->email, false);
    }

    // When a new user/group association is created and either the user or the group is not in the Restart network, it isn't included in the Restart Zapier trigger

    /** @test */
    public function given_nonrestart_group_or_nonrestart_user_when_user_joins_group_then_not_included_in_trigger(): void
    {
        $this->withoutExceptionHandling();

        // arrange
        $admin = User::factory()->administrator()->create([
            'api_token' => '1234',
        ]);
        $this->actingAs($admin);

        $network1 = Network::factory()->create([
            'shortname' => 'restart',
            'include_in_zapier' => true,
        ]);
        $network2 = Network::factory()->create([
            'shortname' => 'repairtogether',
            'include_in_zapier' => false,
        ]);
        $group = Group::factory()->create();
        $network1->addGroup($group);
        $user = User::factory()->create([
            'repair_network' => $network2->id,
        ]);

        // act
        $group->addVolunteer($user);

        // assert
        $response = $this->get('/api/usersgroups/changes?api_token=1234');
        $response->assertDontSee($user->email);
    }
}
