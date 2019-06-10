<?php

namespace Tests\Unit;

use App\Group;
use App\Role;
use App\User;
use App\UserGroups;

use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        Group::truncate();
        UserGroups::truncate();
        DB::statement("SET foreign_key_checks=1");
    }


    /** @test */
    public function can_add_volunteer_to_a_group()
    {
        $group = factory('App\Group')->create();
        $volunteer = factory('App\User')->create();

        $group->addVolunteer($volunteer);

        $groupVolunteer = $group->allVolunteers()->first()->volunteer()->first();
        $this->assertTrue($groupVolunteer->id == $volunteer->id);
    }

    /** @test */
    public function ensure_user_is_removed_from_group_when_deleted()
    {
        /** @var Group $group */
        $group = factory(Group::class)->create();
        /** @var User $volunteer */
        $volunteer = factory(User::class)->create();

        $group->addVolunteer($volunteer);
        $volunteer->delete();

        $this->assertFalse($group->isVolunteer($volunteer->id));
    }

    /** @test */
    public function it_can_set_a_host_group_member_as_host()
    {
        $group = factory('App\Group')->create();
        $host = factory('App\User')->states('Host')->create();

        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $groupHost = $group->allHosts()->first()->volunteer()->first();
        $this->assertTrue($host->id == $groupHost->id);

        $this->assertEquals($host->role, Role::HOST);
    }


    /** @test */
    public function it_can_set_a_restarter_group_member_as_host()
    {
        $group = factory('App\Group')->create();
        $restarter = factory('App\User')->states('Restarter')->create();

        $group->addVolunteer($restarter);
        $group->makeMemberAHost($restarter);

        $groupHost = $group->allHosts()->first()->volunteer()->first();
        $this->assertTrue($restarter->id == $groupHost->id, 'Volunteer should be a member of group\'s hosts');

        $this->assertEquals(Role::HOST, $restarter->role, 'Restarter was converted to a Host');
    }

    /** @test */
    public function it_can_have_a_tag_added()
    {
        $group = factory('App\Group')->create();
        $tag1 = factory('App\GroupTags')->create();
        $tag2 = factory('App\GroupTags')->create();

        $group->addTag($tag1);
        $group->addTag($tag2);

        $retrievedTag = $group->group_tags()->first();

        $this->assertTrue($tag1->tag_name == $retrievedTag->tag_name);
    }
}
