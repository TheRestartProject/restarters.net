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

class WordpressEventPushTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function given_restart_network_when_event_approved_then_pushed_to_wordpress()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('newPost')->once();
        }));

        $restart = factory(Network::class)->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
        ]);
        $group = factory(Group::class)->create();
        $restart->addGroup($group);
        $event = factory(Party::class)->create([
            'group' => $group->idgroups,
            'latitude' => 1,
            'longitude' => 1,
            'event_date' => '2100-01-01',
        ]);

        $event->approve();
    }

    /** @test */
    public function date_format_in_events() {
        $network = factory(Network::class)->create([
           'events_push_to_wordpress' => true,
        ]);
        $group = factory(Group::class)->create();
        $network->addGroup($group);
        $event = factory(Party::class)->create(['group' => $group->idgroups]);
        $event->save();

        $this->mock(WordpressClient::class, function ($mock) use ($event) {
            $mock->shouldReceive('newPost')
                ->withArgs(function($name, $text, $content) use ($event) {
                    // Check name, and that timestamp doesn't include seconds.
                    $party_time = substr($event->start, 0, 5) . ' - ' . substr($event->end, 0, 5);
                    return $name == $event->venue && $content['custom_fields'][3]['value'] == $party_time;
                })->once();
            $mock->shouldReceive('getPost')
                ->andReturn([]);
            $mock->shouldReceive('editPost')
                ->withArgs(function($event2, $content) use ($event) {
                    // Check name, and that timestamp doesn't include seconds.
                    $party_time = substr($event->start, 0, 5) . ' - ' . substr($event->end, 0, 5);
                    return $party_time == $content['custom_fields'][5]['value'];
                })
                ->once();
        });

        // Approve event.
        $handler = app(CreateWordpressPostForEvent::class);
        $handler->handle(new ApproveEvent($event));

        # Fake approval
        $event->wordpress_post_id = 1;
        $event->save();

        # Edit event.
        $handler = app(EditWordpressPostForEvent::class);
        $handler->handle(new EditEvent($event, [
            'event_date' => $event->date,
            'start' => $event->start,
            'end' => $event->end,
            'latitude' => 1,
            'longitude' => 2,
            'group' => 3,
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

        $repairTogether = factory(Network::class)->create([
            'name' => 'Repair Together',
        ]);
        $group = factory(Group::class)->create();
        $repairTogether->addGroup($group);
        $event = factory(Party::class)->create(['group' => $group->idgroups]);

        $eventData = factory(Party::class)->raw();
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

        $restart = factory(Network::class)->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
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
            'events_push_to_wordpress' => false,
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
