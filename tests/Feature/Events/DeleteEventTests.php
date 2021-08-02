<?php

namespace Tests\Feature;

use App\Events\EventDeleted;
use App\EventsUsers;
use App\Group;
use App\Helpers\Geocoder;
use App\Listeners\DeleteEventFromWordPress;
use App\Network;
use App\Notifications\DeleteEventFromWordpressFailed;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Party;
use App\Preferences;
use App\User;
use App\UserGroups;
use Carbon\Carbon;
use DB;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DeleteEventTests extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function an_admin_can_delete_an_event()
    {
        $this->withoutExceptionHandling();
        Event::fake();

        // arrange
        $admin = factory(User::class)->states('Administrator')->create([
                                                                           'api_token' => '1234',
                                                                       ]);
        $this->actingAs($admin);

        $event = factory(Party::class)->create();
        $eventSaved = $event->save();

        // act
        $response = $this->post('/party/delete/'.$event->idevents);

        // assert
        $response->assertRedirect('/party/');
        $this->assertSoftDeleted('events', ['idevents' => $event['idevents']]);
        Event::assertDispatched(\App\Events\EventDeleted::class);

        // Check that viewing the stats for a deleted event behaves gracefully.
        $response = $this->get("/api/party/{$event->idevents}/stats?api_token=1234");
        $this->assertEquals([
                         'message' => "Invalid party id {$event['idevents']}",
                     ], json_decode($response->getContent(), true));

        // Check that getting the outbound info behaves gracefully.
        $this->expectException(NotFoundHttpException::class);
        $this->get("/outbound/info/party/{$event->idevents}");
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
            'events_push_to_wordpress' => true,
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
            'events_push_to_wordpress' => true,
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
