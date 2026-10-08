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
    public function testJoin(): void
    {
        Notification::fake();

        $this->withoutExceptionHandling();

        $group = Group::factory()->create();
        $tag = GroupTags::factory()->create();
        $group->addTag($tag);

        $host = User::factory()->restarter()->create([
          'api_token' => '1234',
        ]);
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $user = User::factory()->restarter()->create();
        $this->actingAs($user);

        // When we follow a Restarters group, we should try to follow the Discourse group.
        $this->instance(AddUserToDiscourseGroup::class, Mockery::mock(AddUserToDiscourseGroup::class, function ($mock) {
            $mock->shouldReceive('handle')->once();
        }));

        // GET /group/join/{id} is gone; joining a group is now a plain API mutation
        // (GroupMembershipController::joinv2).
        $response = $this->post('/api/v2/groups/'.$group->idgroups.'/members/me?api_token='.$user->api_token);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['data']['already_member']);

        $this->artisan("queue:work --stop-when-empty");

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

        // Try again - joining a second time is idempotent and reports already_member.
        $response = $this->post('/api/v2/groups/'.$group->idgroups.'/members/me?api_token='.$user->api_token);
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['data']['already_member']);

        // Now leave via API.
        $this->actingAs($host);
        $userGroupAssociation = UserGroups::where('user', $host->id)
            ->where('group', $group->idgroups)->first();
        $url = '/api/usersgroups/'.$userGroupAssociation->group.'?api_token=1234';
        $response = $this->call('DELETE', $url);
        $response->assertSee('"success":true', false);

        // Try leaving again.
        $response = $this->call('DELETE', $url);
        $response->assertSee('"success":true', false);
    }
}
