<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\GroupTags;
use App\Listeners\AddUserToDiscourseGroup;
use App\Notifications\NewGroupMember;
use App\User;
use App\UserGroups;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Mockery;

class GroupJoinTest extends TestCase
{
    public function testJoin()
    {
        Notification::fake();

        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $tag = factory(GroupTags::class)->create();
        $group->addTag($tag);

        $host = factory(User::class)->states('Restarter')->create([
          'api_token' => '1234',
        ]);
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $user = factory(User::class)->states('Restarter')->create();
        $this->actingAs($user);

        $this->followingRedirects();

        // When we follow a Restarters group, we should try to follow the Discourse group.
        $this->instance(AddUserToDiscourseGroup::class, Mockery::mock(AddUserToDiscourseGroup::class, function ($mock) {
            $mock->shouldReceive('handle')->once();
        }));

        $response = $this->get('/group/join/'.$group->idgroups);

        Notification::assertSentTo(
            [$host],
            NewGroupMember::class,
            function ($notification, $channels, $host) use ($group, $user) {
                $mailData = $notification->toMail($host)->toArray();
                self::assertEquals(__('notifications.new_member_subject', [
                    'name' => $group->name
                ], $host->language), $mailData['subject']);
                return true;
            }
        );

        // Should redirect to the dashboard.
        $this->assertVueProperties($response, [
            [],
            [
                'VueComponent' => 'dashboardpage',
            ],
        ]);

        // Try again.
        $this->followingRedirects();
        $response = $this->get('/group/join/'.$group->idgroups);
        $this->assertContains('You are already part of this group', $response->getContent());

        // Now leave via API.
        $response = $this->get('/logout');
        $this->actingAs($host);
        $userGroupAssociation = UserGroups::where('user', $host->id)
            ->where('group', $group->idgroups)->first();
        $url = '/api/usersgroups/'.$userGroupAssociation->group.'?api_token=1234';
        $response = $this->call('DELETE', $url);
        $response->assertSee('"success":true');

        // Try leaving again.
        $response = $this->call('DELETE', $url);
        $response->assertSee('"success":true');
    }
}
