<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\GroupTags;
use App\Role;
use App\User;
use App\UserGroups;
use Tests\TestCase;

class GroupJoinTest extends TestCase
{
    public function testJoin()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $tag = factory(GroupTags::class)->create();
        $group->addTag($tag);

        $host = factory(User::class)->states('Restarter')->create([
          'api_token' => '1234',
        ]);
        $this->actingAs($host);

        $this->followingRedirects();
        $response = $this->get('/group/join/'.$group->idgroups);

        // Should redirect to the dashboard.
        $this->assertVueProperties($response, [
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
        $url = '/api/usersgroups/' . $userGroupAssociation->idusers_groups . "?api_token=1234";
        $response = $this->call('DELETE', $url);
        $response->assertSee('"success":true');

        // Try leaving again.
        $response = $this->call('DELETE', $url);
        $response->assertSee('"success":true');
    }
}
