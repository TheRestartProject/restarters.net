<?php

namespace Tests\Feature\Groups;

use App\Group;
use App\GroupTags;
use App\Role;
use App\User;
use Tests\TestCase;

class GroupJoinTest extends TestCase
{
    public function testJoin()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $tag = factory(GroupTags::class)->create();
        $group->addTag($tag);

        $host = factory(User::class)->states('Restarter')->create();
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
    }
}
