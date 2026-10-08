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
use App\Role;
use App\User;
use App\UserGroups;
use Auth;
use Carbon\Carbon;
use DB;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DeleteEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function an_admin_can_delete_an_event(): void
    {
        $this->withoutExceptionHandling();
        Event::fake();

        $admin = User::factory()->administrator()->create([
                                                                           'api_token' => '1234',
                                                                       ]);
        $this->actingAs($admin);

        // Create an approved event.
        $group = Group::factory()->create();
        $event = Party::factory()->create(['wordpress_post_id' => 1, 'approved' => true, 'group' => $group->idgroups]);
        $event->save();

        // Check the outbound controller info works.
        $response = $this->get("/outbound/info/party/{$event->idevents}");
        $response->assertSuccessful();
        $response->assertSeeText('or like the manufacture');

        // Add a volunteer so that we get some stats.
        $user = User::factory()->restarter()->create();
        $this->actingAs($user);
        $response = $this->post('/api/v2/events/'.$event->idevents.'/attendees/me?api_token='.$user->api_token);
        $response->assertSuccessful();

        // Get group stats.
        $this->actingAs($admin);
        $response = $this->get("/api/group/{$group->idgroups}/stats?api_token=1234");
        $stats = json_decode($response->getContent(), true);
        $this->assertEquals(21, $stats['num_hours_volunteered']);

        // Now delete the event.
        $response = $this->delete('/api/v2/events/'.$event->idevents.'?api_token=1234');
        $response->assertSuccessful();
        $this->assertSoftDeleted('events', ['idevents' => $event['idevents']]);
        Event::assertDispatched(\App\Events\EventDeleted::class);

        // Group stats should have been updated.
        $response = $this->get("/api/group/{$group->idgroups}/stats?api_token=1234");
        $stats = json_decode($response->getContent(), true);
        $this->assertEquals(0, $stats['num_hours_volunteered']);

        // Check that viewing the stats for a deleted event behaves gracefully.
        $response = $this->get("/api/party/{$event->idevents}/stats?api_token=1234");
        $this->assertEquals([
                         'message' => "Invalid party id {$event['idevents']}",
                     ], json_decode($response->getContent(), true));

        // Check that getting the outbound info behaves gracefully.
        $this->expectException(NotFoundHttpException::class);
        $this->get("/outbound/info/party/{$event->idevents}");
    }

    /**
     * @test
     * @dataProvider roleProvider
     */
    public function view_edit_deleted_event($role): void
    {
        $this->withoutExceptionHandling();

        switch ($role) {
            case Role::ADMINISTRATOR: $roleToCreate = 'Administrator'; $host = User::factory()->administrator()->create(); break;
            case Role::NETWORK_COORDINATOR: $roleToCreate = 'NetworkCoordinator'; $host = User::factory()->networkCoordinator()->create(); break;
            case Role::HOST: $roleToCreate = 'Host'; $host = User::factory()->host()->create(); break;
        }

        $this->actingAs($host);

        $group = Group::factory()->create([
                                              'wordpress_post_id' => '1',
                                              'approved' => true
                                           ]);
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $event = Party::factory()->create(['group' => $group->idgroups]);
        $event->save();

        // Now delete the event.
        $response = $this->delete('/api/v2/events/'.$event->idevents.'?api_token='.$host->api_token);
        $response->assertSuccessful();
        $this->assertSoftDeleted('events', ['idevents' => $event['idevents']]);

        // A deleted event should no longer be retrievable.
        try {
            $this->get('/api/v2/events/'.$event->idevents);
            $this->assertTrue(false, "Failed to throw exception");
        } catch (ModelNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function roleProvider(): array {
        return [
            [ Role::ADMINISTRATOR ],
            [ Role::NETWORK_COORDINATOR ],
            [ Role::HOST ],
        ];
    }

    /** @test */
    public function given_network_connected_to_wordpress_when_event_deleted(): void
    {
        $this->withoutExceptionHandling();

        // arrange
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('deletePost')->once();
        }));

        $network = Network::factory()->create([
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
                                              'wordpress_post_id' => '1',
                                              'approved' => true
        ]);
        $network->addGroup($group);
        $event = Party::factory()->create(['group' => $group->idgroups]);
        $event->wordpress_post_id = 100;
        $event->approved = true;
        $event->save();

        // act
        $handler = app(DeleteEventFromWordPress::class);
        $handler->handle(new EventDeleted($event));
    }

    /** @test */
    public function given_wordpress_deletion_failure(): void
    {
        $this->withoutExceptionHandling();
        Notification::fake();

        $admin = User::factory()->administrator()->create();
        $preference = Preferences::where('slug', 'delete-event-notification')->get();
        $admin->preferences()->attach($preference);

        // arrange
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('deletePost')->andThrow(new \Exception);
        }));

        $network = Network::factory()->create([
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
                                              'wordpress_post_id' => '1',
                                              'approved' => true
                                           ]);
        $network->addGroup($group);
        $event = Party::factory()->create(['group' => $group->idgroups]);
        $event->wordpress_post_id = 100;
        $event->approved = true;
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
