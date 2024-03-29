<?php

namespace Tests\Unit;

use App\Group;
use App\GrouptagsGroups;
use App\Network;
use App\Role;
use App\User;
use App\UserGroups;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GroupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        User::truncate();
        Group::truncate();
        GrouptagsGroups::truncate();
        Network::truncate();
        UserGroups::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /** @test */
    public function can_add_volunteer_to_a_group()
    {
        $group = \App\Group::factory()->create();
        $volunteer = \App\User::factory()->create();

        $group->addVolunteer($volunteer);

        $groupVolunteer = $group->allVolunteers()->first()->volunteer()->first();
        $this->assertTrue($groupVolunteer->id == $volunteer->id);
    }

    /** @test */
    public function ensure_user_is_removed_from_group_when_deleted()
    {
        /** @var Group $group */
        $group = Group::factory()->create();
        /** @var User $volunteer */
        $volunteer = User::factory()->create();

        $group->addVolunteer($volunteer);
        $volunteer->delete();
        $this->artisan("queue:work --stop-when-empty");

        $this->assertFalse($group->isVolunteer($volunteer->id));
    }

    /** @test */
    public function it_can_set_a_host_group_member_as_host()
    {
        $group = \App\Group::factory()->create();
        $host = \App\User::factory()->host()->create();

        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $groupHost = $group->allHosts()->first()->volunteer()->first();
        $this->assertTrue($host->id == $groupHost->id);

        $this->assertEquals($host->role, Role::HOST);
    }

    /** @test */
    public function it_can_set_a_restarter_group_member_as_host()
    {
        $group = \App\Group::factory()->create();
        $restarter = \App\User::factory()->restarter()->create();

        $group->addVolunteer($restarter);
        $group->makeMemberAHost($restarter);

        $groupHost = $group->allHosts()->first()->volunteer()->first();
        $this->assertTrue($restarter->id == $groupHost->id, 'Volunteer should be a member of group\'s hosts');

        $this->assertEquals(Role::HOST, $restarter->role, 'Restarter was converted to a Host');
    }

    /** @test */
    public function it_can_have_a_tag_added()
    {
        $group = \App\Group::factory()->create();
        $tag1 = \App\GroupTags::factory()->create();

        $group->addTag($tag1);

        $retrievedTag = $group->group_tags()->first();

        $this->assertTrue($tag1->tag_name == $retrievedTag->tag_name);
    }

    /** @test */
    public function given_a_network_that_should_push_then_group_should_push()
    {
        $network1 = Network::factory()->create([
            'events_push_to_wordpress' => true,
        ]);
        $network2 = Network::factory()->create([
            'events_push_to_wordpress' => false,
        ]);

        $group = Group::factory()->create([
                                              'approved' => true,
                                           ]);
        $network1->addGroup($group);
        $network2->addGroup($group);

        $shouldPush = $group->eventsShouldPushToWordpress();

        $this->assertTrue($shouldPush);
    }

    /** @test */
    public function given_no_network_that_should_push_then_group_should_not_push()
    {
        $network1 = Network::factory()->create([
            'events_push_to_wordpress' => false,
        ]);
        $network2 = Network::factory()->create([
            'events_push_to_wordpress' => false,
        ]);

        $group = Group::factory()->create();
        $network1->addGroup($group);
        $network2->addGroup($group);

        $shouldPush = $group->eventsShouldPushToWordpress();

        $this->assertFalse($shouldPush);
    }

    /**
     * @test
     * @dataProvider timezoneProvider
     */
    public function timezone_inheritance($group, $network1, $network2, $result, $exception) {
        $network1 = Network::factory()->create([
            'timezone' => $network1
        ]);
        $network2 = Network::factory()->create([
            'timezone' => $network2
        ]);

        $group = Group::factory()->create([
            'timezone' => $group
        ]);
        $network1->addGroup($group);
        $network2->addGroup($group);

        try {
            $timezone = $group->timezone;
            $this->assertEquals($result, $timezone);
        } catch(\Exception $e) {
            if ($exception) {
                $this->assertTrue(true);
            } else {
                $this->assertFalse(true, 'Unexpected exception thrown');
            }
        }
    }

    public function timezoneProvider() {
        return [
            [ NULL, 'Europe/Paris', NULL, 'Europe/Paris', FALSE ],
            [ NULL, 'Europe/Paris', 'Europe/Paris', 'Europe/Paris', FALSE ],
            [ NULL, 'Europe/Paris', 'Europe/London', NULL, TRUE ],
            [ 'Europe/Brussels','Europe/Paris', 'Europe/Paris', 'Europe/Brussels', FALSE ],
            [ 'Europe/Brussels', NULL, 'Europe/Paris', 'Europe/Brussels', FALSE ],
            [ NULL, NULL, NULL, NULL, TRUE ],
        ];
    }

    public function can_store_phone() {
        $group = Group::factory()->create([
                                                   'phone' => 1234
                                               ]);

        $group2 = Group::find($group->id);
        self::assertEquals(1234, $group2->phone);
    }
}
