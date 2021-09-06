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

        $admin = factory(User::class)->states('Administrator')->create([
                                                                           'api_token' => '1234',
                                                                       ]);
        $this->actingAs($admin);

        // Create an approved event.
        $group = factory(Group::class)->create();
        $event = factory(Party::class)->create(['wordpress_post_id' => 1, 'group' => $group->idgroups]);
        $event->save();

        // Add a volunteer so that we get some stats.
        $user = factory(User::class)->states('Restarter')->create();
        $this->actingAs($user);
        $response = $this->get('/party/join/'.$event->idevents);
        $this->assertTrue($response->isRedirection());

        // Get group stats.
        $this->actingAs($admin);
        $response = $this->get("/api/group/{$group->idgroups}/stats?api_token=1234");
        $stats = json_decode($response->getContent(), TRUE);
        $this->assertEquals(12, $stats['num_hours_volunteered']);

        // Now delete the event.
        $response = $this->post('/party/delete/'.$event->idevents);
        $response->assertRedirect('/party/');
        $this->assertSoftDeleted('events', ['idevents' => $event['idevents']]);
        Event::assertDispatched(\App\Events\EventDeleted::class);

        // Group stats should have been updated.
        $response = $this->get("/api/group/{$group->idgroups}/stats?api_token=1234");
        $stats = json_decode($response->getContent(), TRUE);
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

    public function provider()
    {
        // We return:
        // - role
        // - past/future
        // - (for past) whether to add a device
        // - whether the delete flag should show (only for admins and where no devices)
        return [
            [
                'Administrator', 'Past', FALSE, TRUE
            ],
            [
                'Administrator', 'Past', TRUE, FALSE
            ],
            [
                'Administrator', 'Future', FALSE, TRUE
            ],
            [
                'NetworkCoordinator', 'Past', FALSE, FALSE
            ],
            [
                'NetworkCoordinator', 'Future', FALSE, FALSE
            ],
            [
                'Host', 'Past', FALSE, FALSE
            ],
            [
                'Host', 'Future', FALSE, FALSE
            ],
            [
                'Restarter', 'Past', FALSE, FALSE
            ],
            [
                'Restarter', 'Future', FALSE, FALSE
            ],
        ];
    }

    /** @test
     *  @dataProvider provider
     */
    public function candelete_flag($role, $pastFuture, $addDevice, $canDelete) {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);
        $idevents = $this->createEvent($id, $pastFuture == 'Past' ? 'yesterday' : 'tomorrow');

        if ($addDevice) {
            $this->createDevice($idevents, 'misc');
        }

        $user = factory(User::class)->states($role)->create();
        $this->actingAs($user);

        $response = $this->get("/party/view/$id");

        $this->assertVueProperties($response, [
            [
                ':candelete' => $canDelete ? "true" : "false",
            ],
        ]);

    }
}
