<?php

namespace Tests\Feature;

use App\Events\ApproveEvent;
use App\Events\EditEvent;
use App\Group;
use App\GroupNetwork;
use App\Listeners\CreateWordpressPostForEvent;
use App\Listeners\EditWordpressPostForEvent;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
use DB;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class WordpressEventPushTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // These tests are hard to get working with genuinely queued events, so use the sync queue.
        $queueManager = $this->app['queue'];
        $queueManager->setDefaultDriver('sync');
    }

    /** @test */
    public function given_restart_network_when_event_approved_then_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('newPost')->once();
        }));

        $restart = Network::factory()->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
                                              'approved' => true,
                                           ]);
        $restart->addGroup($group);
        $event = Party::factory()->create([
            'group' => $group->idgroups,
            'latitude' => 1,
            'longitude' => 1,
            'event_start_utc' => '2100-01-01T10:15:05+05:00',
            'event_end_utc' => '2100-01-0113:45:05+05:00',
        ]);

        $event->approve();
        $this->artisan("queue:work --stop-when-empty");

        $this->artisan('sync:events');
    }

    /** @test */
    public function date_format_in_events() {
        $network = Network::factory()->create([
           'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
          'wordpress_post_id' => 1,
          'approved' => true,
        ]);
        $network->addGroup($group);
        $event = Party::factory()->create([
            'group' => $group->idgroups,
            'event_start_utc' => Carbon::parse('1pm tomorrow')->toIso8601String(),
            'event_end_utc' => Carbon::parse('3pm tomorrow')->toIso8601String()
           ]);
        $event->save();

        $this->mock(WordpressClient::class, function ($mock) use ($event) {
            $mock->shouldReceive('newPost')
                ->withArgs(function($name, $text, $content) use ($event) {
                    // Check name, and that timestamp doesn't include seconds.
                    $party_time = substr($event->start_local, 0, 5) . ' - ' . substr($event->end_local, 0, 5);
                    return $name == $event->venue && $content['custom_fields'][3]['value'] == $party_time;
                })->once();
            $mock->shouldReceive('getPost')
                ->andReturn([]);
            $mock->shouldReceive('editPost')
                ->withArgs(function($event2, $content) use ($event) {
                    // Check name, and that timestamp doesn't include seconds.
                    $party_time = substr($event->start_local, 0, 5) . ' - ' . substr($event->end_local, 0, 5);
                    return $party_time == $content['custom_fields'][5]['value'];
                })
                ->once();
        });

        // Approve event.
        $handler = app(CreateWordpressPostForEvent::class);
        $handler->handle(new ApproveEvent($event));

        # Fake approval
        $event->approved = true;
        $event->wordpress_post_id = 1;
        $event->save();

        # Edit event.
        $handler = app(EditWordpressPostForEvent::class);
        $handler->handle(new EditEvent($event, [
            'event_start_utc' => Carbon::parse('2pm tomorrow')->toIso8601String(),
            'event_end_utc' => Carbon::parse('4pm tomorrow')->toIso8601String(),
            'latitude' => 1,
            'longitude' => 2,
            'online' => FALSE,
            'venue' => 'Cloud 9',
            'location' => 'Cloud 10',
            'free_text' => 'Text is free'
        ]));
    }

    /** @test */
    public function given_nonrestart_network_when_event_approved_then_not_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldNotReceive('newPost');
        }));

        $repairTogether = Network::factory()->create([
            'name' => 'Repair Together',
        ]);
        $group = Group::factory()->create([
                                              'approved' => true,
                                           ]);
        $repairTogether->addGroup($group);
        $event = Party::factory()->create(['group' => $group->idgroups]);

        $eventData = Party::factory()->raw();
        $eventData['moderate'] = 'approve';
        $eventData['latitude'] = '1';
        $eventData['longitude'] = '1';

        event(new ApproveEvent($event));
    }

    /** @test */
    public function given_restart_network_when_event_edited_then_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('getPost')->andReturn(100);
            $mock->shouldReceive('editPost')->once();
        }));

        $restart = Network::factory()->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
                                              'wordpress_post_id' => 1,
                                              'approved' => true,
                                           ]);
        $restart->addGroup($group);
        $event = Party::factory()->create([
            'group' => $group->idgroups,
            'event_start_utc' => Carbon::parse('1pm tomorrow')->toIso8601String(),
            'event_end_utc' => Carbon::parse('3pm tomorrow')->toIso8601String()
        ]);
        $event->wordpress_post_id = 100;
        $event->approved = true;
        $event->save();

        $eventData = Party::factory()->raw();
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

        $repairTogether = Network::factory()->create([
            'name' => 'Repair Together',
            'events_push_to_wordpress' => false,
        ]);
        $group = Group::factory()->create([
                                              'approved' => true,
                                           ]);
        $repairTogether->addGroup($group);
        $event = Party::factory()->create(['group' => $group->idgroups]);
        $event->approved = true;
        $event->save();

        $eventData = Party::factory()->raw();
        $eventData['free_text'] = 'Some change';
        $eventData['latitude'] = '1';
        $eventData['longitude'] = '1';

        event(new EditEvent($event, $eventData));
    }


    /** @test */
    public function given_group_not_approved_then_not_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldNotReceive('getPost');
            $mock->shouldNotReceive('editPost');
        }));

        $restart = Network::factory()->create([
                                                       'name' => 'Restart',
                                                       'events_push_to_wordpress' => true,
                                                   ]);
        $group = Group::factory()->create([
                                              'approved' => false,
                                        ]);
        $restart->addGroup($group);
        $event = Party::factory()->create([
                                                   'group' => $group->idgroups,
                                                   'latitude' => 1,
                                                   'longitude' => 1,
                                                   'event_start_utc' => '2100-01-01T10:15:05+05:00',
                                                   'event_end_utc' => '2100-01-0113:45:05+05:00',
                                               ]);

        $event->approve();
    }
}
