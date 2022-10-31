<?php

namespace Tests\Feature;

use App\Events\UserUpdated;
use App\Group;
use App\GroupTags;
use App\Notifications\GroupConfirmed;
use App\Notifications\NewGroupWithinRadius;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class GroupsNearbyTest extends TestCase
{
    public function testNoLocation()
    {
        $user = factory(User::class)->create();
        $this->assertEquals(0, count($user->groupsNearby()));
    }

    public function testLocationNoGroups()
    {
        $user = factory(User::class)->create([
            'latitude' => -12.0464,
            'longitude' => -77.0428,
        ]);
        $this->assertEquals(0, count($user->groupsNearby()));
    }

    public function testOneGroupNearby()
    {
        $groupAttributes = factory(Group::class)->raw();
        $groupAttributes['name'] = 'Lancaster Fixers';
        $groupAttributes['wordpress_post_id'] = '99999';
        $group = factory(Group::class)->create([
            'latitude' => -12.0464,
            'longitude' => -77.0428,
            'wordpress_post_id' => '99999',
        ]);
        $user = factory(User::class)->create([
                                                 'latitude' => -12.0463,
                                                 'longitude' => -77.0427,
                                             ]);
        $groups = $user->groupsNearby();
        $this->assertEquals(1, count($groups));
        $this->assertEquals($group->id, $groups[0]->id);
    }

    public function testDontShowUnlessApproved()
    {
        $group = factory(Group::class)->create([
                                                   'latitude' => -12.0464,
                                                   'longitude' => -77.0428,
                                               ]);
        $user = factory(User::class)->create([
                                                 'latitude' => -12.37,
                                                 'longitude' => -77.37,
                                             ]);

        $groups = $user->groupsNearby();
        $this->assertEquals(0, count($groups));
    }

    public function testCloseButNoCigar()
    {
        $group = factory(Group::class)->create([
                                                   'latitude' => -12.0464,
                                                   'longitude' => -77.0428,
                                                   'wordpress_post_id' => '99999',
                                               ]);
        $user = factory(User::class)->create([
                                                 'latitude' => -12.37,
                                                 'longitude' => -77.37,
                                             ]);

        // This is about 50.6km away.
        $groups = $user->groupsNearby(10, null, User::NEARBY_KM + 1);
        $this->assertEquals(1, count($groups));
        $this->assertEquals($group->id, $groups[0]->id);

        $groups = $user->groupsNearby();
        $this->assertEquals(0, count($groups));
    }

    public function testInactive()
    {
        // Add a group with a tag.
        $group = factory(Group::class)->create([
                                                   'latitude' => -12.0464,
                                                   'longitude' => -77.0428,
                                                   'wordpress_post_id' => '99999',
                                               ]);

        $active = factory(GroupTags::class)->create([
                                                          'id' => GroupTags::INACTIVE + 1,
                                                          'tag_name' => 'Not Inactive',
                                                      ]);

        $group->addTag($active);

        // Should find it nearby.
        $user = factory(User::class)->create([
                                                 'latitude' => -12.0463,
                                                 'longitude' => -77.0427,
                                             ]);
        $groups = $user->groupsNearby();
        $this->assertEquals(1, count($groups));

        // Make the group inactive.
        $inactive = factory(GroupTags::class)->create([
                                                          'id' => GroupTags::INACTIVE,
                                                          'tag_name' => 'Inactive',
                                                      ]);

        $group->addTag($inactive);

        // Should no longer show up.
        $groups = $user->groupsNearby();
        $this->assertEquals(0, count($groups));
    }

    public function testNotification() {
        Notification::fake();

        // Create a user in London.
        $user = factory(User::class)->create([
            'location' => 'London',
             'latitude' => 51.5072178,
             'longitude' => -0.1275862,
         ]);

        $admin1 = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin1);

        $idgroups = $this->createGroup('Test Group');
        $group = Group::find($idgroups);

        $admin2 = factory(User::class)->state('Administrator')->create();
        $this->actingAs($admin2);

        // Approve the group.
        $this->patch('/api/v2/groups/' . $group->idgroups, [
            'description' => 'Test',
            'location' => 'London',
            'name' => $group->name,
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
            'moderate' => 'approve',
            'area' => 'London',
            'postcode' => 'SW9 7QD'
        ])->assertSuccessful();

        // This should trigger a notification.
        Notification::assertSentTo(
            [$user],
            NewGroupWithinRadius::class,
            function ($notification, $channels, $host) use ($group) {
                $mailData = $notification->toMail($host)->toArray();
                self::assertEquals(__('notifications.new_group_subject', [], $host->language), $mailData['subject']);

                // Mail should mention the group name.
                self::assertRegexp('/' . $group->name . '/', $mailData['introLines'][0]);

                return true;
            }
        );
    }
}
