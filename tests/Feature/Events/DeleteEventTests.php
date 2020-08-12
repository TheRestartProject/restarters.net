<?php

namespace Tests\Feature;

use App\EventsUsers;
use App\Events\EventDeleted;
use App\Listeners\DeleteEventFromWordPress;
use App\Group;
use App\Network;
use App\Notifications\DeleteEventFromWordpressFailed;
use App\Party;
use App\Preferences;
use App\User;
use App\UserGroups;
use App\Helpers\Geocoder;
use App\Notifications\NotifyRestartersOfNewEvent;

use DB;
use Carbon\Carbon;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteEventTests extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        Group::truncate();
        Party::truncate();
        EventsUsers::truncate();
        UserGroups::truncate();
        DB::delete('delete from users_preferences');
        DB::delete('delete from group_network');
        DB::delete('delete from user_network');
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function an_admin_can_delete_an_event()
    {
        $this->withoutExceptionHandling();
        Event::fake();

        // arrange
        $admin = factory(User::class)->states('Administrator')->create();
        $this->actingAs($admin);

        $event = factory(Party::class)->create();
        $eventSaved = $event->save();

        // act
        $response = $this->post('/party/delete/'.$event->idevents);

        // assert
        $response->assertRedirect('/party/');
        $this->assertSoftDeleted('events', ['idevents' => $event['idevents']]);
        Event::assertDispatched(\App\Events\EventDeleted::class);
    }

    /** @test */
    public function given_network_connected_to_wordpress_when_event_deleted()
    {
        $this->withoutExceptionHandling();

        // arrange
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('deletePost')->once();
        }));

        $network = factory(Network::class)->create([
            'events_push_to_wordpress' => true
        ]);
        $group = factory(Group::class)->create();
        $network->addGroup($group);
        $event = factory(Party::class)->create(['group' => $group->idgroups]);
        $event->wordpress_post_id = 100;
        $event->save();

        // act
        $handler = app(DeleteEventFromWordPress::class);
        $handler->handle(new EventDeleted($event));
    }


    /** @test */
    public function given_wordpress_deletion_failure()
    {
        $this->withoutExceptionHandling();
        Notification::fake();

        $admin = factory(User::class)->states('Administrator')->create();
        $preference = Preferences::where('slug', 'delete-event-notification')->get();
        $admin->preferences()->attach($preference);

        // arrange
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('deletePost')->andThrow(new \Exception);
        }));

        $network = factory(Network::class)->create([
            'events_push_to_wordpress' => true
        ]);
        $group = factory(Group::class)->create();
        $network->addGroup($group);
        $event = factory(Party::class)->create(['group' => $group->idgroups]);
        $event->wordpress_post_id = 100;
        $event->save();

        // act
        $handler = app(DeleteEventFromWordPress::class);
        $handler->handle(new EventDeleted($event));

        // assert
        Notification::assertSentTo(
            $admin,
            DeleteEventFromWordpressFailed::class
        );
    }
}
