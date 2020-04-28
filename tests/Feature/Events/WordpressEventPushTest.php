<?php

namespace Tests\Feature;

use App\Events\ApproveEvent;
use App\Events\EditEvent;
use App\Group;
use App\GroupNetwork;
use App\Network;
use App\Party;
use App\User;

use DB;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WordpressPushTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        Group::truncate();
        Party::truncate();
        Network::truncate();
        GroupNetwork::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function given_restart_network_when_event_approved_then_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('newPost')->once();
        }));

        $restart = factory(Network::class)->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true
        ]);
        $group = factory(Group::class)->create();
        $restart->addGroup($group);
        $event = factory(Party::class)->create(['group' => $group->idgroups]);

        $eventData = factory(Party::class)->raw();
        $eventData['moderate'] = 'approve';
        $eventData['latitude'] = '1';
        $eventData['longitude'] = '1';

        event(new ApproveEvent($event, $eventData));
    }


    /** @test */
    public function given_nonrestart_network_when_event_approved_then_not_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldNotReceive('newPost');
        }));

        $repairTogether = factory(Network::class)->create([
            'name' => 'Repair Together'
        ]);
        $group = factory(Group::class)->create();
        $repairTogether->addGroup($group);
        $event = factory(Party::class)->create(['group' => $group->idgroups]);

        $eventData = factory(Party::class)->raw();
        $eventData['moderate'] = 'approve';
        $eventData['latitude'] = '1';
        $eventData['longitude'] = '1';

        event(new ApproveEvent($event, $eventData));
    }

    /** @test */
    public function given_restart_network_when_event_edited_then_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('getPost')->andReturn(100);
            $mock->shouldReceive('editPost')->once();
        }));

        $restart = factory(Network::class)->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true
        ]);
        $group = factory(Group::class)->create();
        $restart->addGroup($group);
        $event = factory(Party::class)->create(['group' => $group->idgroups]);
        $event->wordpress_post_id = 100;
        $event->save();

        $eventData = factory(Party::class)->raw();
        $eventData['free_text'] = 'Some change';
        $eventData['latitude'] = '1';
        $eventData['longitude'] = '1';

        event(new EditEvent($event, $eventData));
    }

    /** @test */
    public function given_nonrestart_network_when_event_edited_then_not_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldNotReceive('getPost');
            $mock->shouldNotReceive('editPost');
        }));

        $repairTogether = factory(Network::class)->create([
            'name' => 'Repair Together',
            'events_push_to_wordpress' => false
        ]);
        $group = factory(Group::class)->create();
        $repairTogether->addGroup($group);
        $event = factory(Party::class)->create(['group' => $group->idgroups]);
        $event->wordpress_post_id = 100;
        $event->save();

        $eventData = factory(Party::class)->raw();
        $eventData['free_text'] = 'Some change';
        $eventData['latitude'] = '1';
        $eventData['longitude'] = '1';

        event(new EditEvent($event, $eventData));
    }
}
