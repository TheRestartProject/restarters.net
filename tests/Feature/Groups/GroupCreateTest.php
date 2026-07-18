<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\GroupTags;
use App\Network;
use App\Notifications\GroupConfirmed;
use App\Role;
use App\User;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class GroupCreateTest extends TestCase
{
    public function testCreate(): void
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
        self::assertEquals('dummy', $ret[0]['network_data']['dummy']);
    }

    public function testCreateGroupAsRestarter(): void {
        // Restarters can create groups.  This wasn't true in the past and for backwards compatibility the act
        // of creation should convert them into a host.
        $user = $this->loginAsTestUser(Role::RESTARTER);
        $this->assertFalse($user->hasRole(Role::HOST));

        // Should be able to create a group.
        $idgroups = $this->createGroup();
        $this->assertNotNull($idgroups);
        $user->refresh();
        $this->assertEquals(Role::HOST, $user->role);
        $this->assertTrue($user->hasRole('Host'));
    }

    public function testCreateBadLocation(): void
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        // Use an address which will fail to geocode.
        $this->expectException(ValidationException::class);
        $this->assertNull($this->createGroup('Test Group', 'https://therestartproject.org', 'zzzzzzzzzzz123', 'Some text', false));
    }

    public function testCreateRejectsInvalidWebsite(): void
    {
        // A bare token like 'EDP' is not a valid URL. Without server-side validation
        // we previously accepted it and then choked further down the pipeline, which
        // surfaced as a 500 / "Request failed" hang in the UI. Validate up front.
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $this->expectException(ValidationException::class);
        $this->assertNull($this->createGroup('Test Group', 'EDP', 'London', 'Some text.', false));
    }

    public function testCreateAcceptsBlankWebsite(): void
    {
        // Website is optional. Empty / null values must still be accepted.
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        $idgroups = $this->createGroup('Test Group', '', 'London', 'Some text.');
        $this->assertNotNull($idgroups);
    }

    public function roles(): array {
        return [
            [ 'Administrator'],
            [ 'NetworkCoordinator' ]
        ];
    }

    /**
     * @dataProvider roles
     */
    public function testApprove($role): void {
        Notification::fake();

        $actas = User::factory()->{lcfirst($role)}()->create();
        $this->actingAs($actas);

        $network = Network::factory()->create();
        $idgroups = $this->createGroup('Test Group', 'https://therestartproject.org','London', 'Some text.', true, false);
        $group = Group::find($idgroups);
        $network->addGroup($group);

        $network2 = Network::factory()->create();
        // Create a global tag (NC should NOT be able to add - global tags are admin-only)
        $globalTag = GroupTags::factory()->create();
        // Create a tag belonging to the NC's network (NC CAN add this)
        $networkTag = GroupTags::factory()->create(['network_id' => $network->id]);
        // Create a tag belonging to another network (NC should NOT be able to add this)
        $otherNetworkTag = GroupTags::factory()->create(['network_id' => $network2->id]);

        if ($role == 'NetworkCoordinator') {
            $network->addCoordinator($actas);
        }

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
            'tags' => json_encode([ $globalTag->id, $networkTag->id, $otherNetworkTag->id ]),
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
            // NC can't edit networks. NC can only add tags from networks they coordinate (not global or other networks).
            $this->assertTrue($group->networks->contains($network));
            $this->assertFalse($group->networks->contains($network2));
            $this->assertFalse($group->group_tags->contains($globalTag)); // Global tag - NOT allowed for NC
            $this->assertTrue($group->group_tags->contains($networkTag)); // Own network's tag - allowed
            $this->assertFalse($group->group_tags->contains($otherNetworkTag)); // Other network's tag - NOT allowed
        } else if ($role == 'Administrator') {
            // Administrators can edit networks and all tags (global + any network's tags).
            $this->assertTrue($group->networks->contains($network));
            $this->assertTrue($group->networks->contains($network2));
            $this->assertTrue($group->group_tags->contains($globalTag));
            $this->assertTrue($group->group_tags->contains($networkTag));
            $this->assertTrue($group->group_tags->contains($otherNetworkTag));
        }
    }
}
