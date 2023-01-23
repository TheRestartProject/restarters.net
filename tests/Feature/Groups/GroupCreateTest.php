<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\GroupTags;
use App\Network;
use App\Notifications\GroupConfirmed;
use App\Party;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class GroupCreateTest extends TestCase
{
    public function testCreate()
    {
        $user = User::factory()->administrator()->create([
                                                                      'api_token' => '1234',
                                                                  ]);
        $this->actingAs($user);

        $idgroups = $this->createGroup();
        $this->assertNotNull($idgroups);
        $group = Group::find($idgroups);

        $response = $this->get('/api/groups?api_token=1234');
        $response->assertSuccessful();
        $ret = json_decode($response->getContent(), TRUE);
        self::assertEquals(1, count($ret));
        self::assertEquals($idgroups, $ret[0]['idgroups']);
        self::assertEquals($group->name, $ret[0]['name']);
    }

    public function testCreateBadLocation()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Use an address which will fail to geocode.
        $this->expectException(ValidationException::class);
        $this->assertNull($this->createGroup('Test Group', 'https://therestartproject.org', 'zzzzzzzzzzz123', 'Some text', false));
    }

    public function roles() {
        return [
            [ 'Administrator'],
            [ 'NetworkCoordinator' ]
        ];
    }

    /**
     * @dataProvider roles
     */
    public function testApprove($role) {
        Notification::fake();

        $actas = User::factory()->{lcfirst($role)}()->create();
        $this->actingAs($actas);

        $network = Network::factory()->create();
        $idgroups = $this->createGroup('Test Group', 'https://therestartproject.org','London', 'Some text.', true, false);
        $group = Group::find($idgroups);
        $network->addGroup($group);

        $network2 = Network::factory()->create();
        $tag = GroupTags::factory()->create();

        if ($role == 'NetworkCoordinator') {
            $network->addCoordinator($actas);
        }

        // Vue component should exist for group to be moderated, though the component itself fetches the group info
        // so it won't show as props.
        $response = $this->get('/group');
        $response->assertSuccessful();

        $props = $this->assertVueProperties($response, [
            [],
            [
                'VueComponent' => 'groupsrequiringmoderation'
            ],
        ]);

        // Log in as someone else with the same role so that the GroupConfirmed notification gets sent.
        $actas2 = User::factory()->$role()->create();

        if ($role == 'NetworkCoordinator') {
            $network->addCoordinator($actas2);
        }

        $this->actingAs($actas2);

        $response = $this->patch('/api/v2/groups/' . $idgroups, [
            'description' => 'Test',
            'location' => 'London',
            'name' => $group->name,
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
            'moderate' => 'approve',
            'area' => 'London',
            'postcode' => 'SW9 7QD',
            'networks' => json_encode([ $network->id, $network2->id ]),
            'tags' => json_encode([ $tag->id ]),
        ]);

        $response->assertSuccessful();

        Notification::assertSentTo(
            [$actas],
            GroupConfirmed::class,
            function ($notification, $channels, $host) use ($group) {
                $mailData = $notification->toMail($host)->toArray();
                self::assertEquals(__('notifications.group_confirmed_subject', [], $host->language), $mailData['subject']);

                // Mail should mention the group name.
                self::assertMatchesRegularExpression ('/' . $group->name . '/', $mailData['introLines'][0]);

                return true;
            }
        );

        $group->refresh();
        if ($role == 'NetworkCoordinator') {
            // Attempt to edit the networks or tags should be ignored.
            $this->assertTrue($group->networks->contains($network));
            $this->assertFalse($group->networks->contains($network2));
            $this->assertFalse($group->group_tags->contains($tag));
        } else if ($role == 'Administrator') {
            // Administrators can edit networks and tags.
            $this->assertTrue($group->networks->contains($network));
            $this->assertTrue($group->networks->contains($network2));
            $this->assertTrue($group->group_tags->contains($tag));
        }
    }

    public function testEventVisibility() {
        // Create a network.
        $network = Network::factory()->create();

        // Create an unapproved group in that network.
        $admin1 = User::factory()->administrator()->create();
        $this->actingAs($admin1);
        $idgroups = $this->createGroup('Test Group', 'https://therestartproject.org', 'London', 'Some text.', true, false);
        $group = Group::find($idgroups);
        $network->addGroup($group);

        // Create a host for the group.
        $host = User::factory()->host()->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);
        $this->actingAs($host);

        // Create an event on this as yet unapproved group.
        $eventAttributes = Party::factory()->raw();
        $eventAttributes['group'] = $idgroups;
        $eventAttributes['link'] = 'https://therestartproject.org/';
        $eventAttributes['event_start_utc'] = Carbon::parse('1pm tomorrow')->toIso8601String();
        $eventAttributes['event_end_utc'] = Carbon::parse('3pm tomorrow')->toIso8601String();

        $this->post('/party/create/', $eventAttributes);
        $event = Party::latest()->first();
        $this->assertEquals($host->id, $event->user_id);

        // The event should be visible to the host.
        $this->get('/party/view/'.$event->idevents)->assertSee($eventAttributes['venue']);
        $this->get('/party')->assertSee($eventAttributes['venue']);

        // ...and on the page for this group's events.
        $this->get('/party/group/' . $idgroups)->assertSee($eventAttributes['venue']);

        // And to a network coordinator
        $coordinator = User::factory()->networkCoordinator()->create();
        $network->addCoordinator($coordinator);
        $this->actingAs($coordinator);
        $this->get('/party/view/'.$event->idevents)->assertSee($eventAttributes['venue']);
        $this->get('/party')->assertSee($eventAttributes['venue']);

        // This event should not be visible to a Restarter, as the group is not yet approved.
        $restarter = User::factory()->restarter()->create();
        $this->actingAs($restarter);
        try {
            $this->get('/party/view/'.$event->idevents)->assertDontSee(e($eventAttributes['venue']));
            $this->assertTrue(false);
        } catch (NotFoundHttpException $e) {}

        $this->get('/party')->assertDontSee($eventAttributes['venue']);

        // Now approve the group.
        $group->approved = true;
        $group->save();

        // Should now be visible.
        $this->get('/party/view/'.$event->idevents)->assertSee($eventAttributes['venue']);
        $this->get('/party')->assertSee($eventAttributes['venue']);
    }
}
