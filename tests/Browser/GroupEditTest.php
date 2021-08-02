<?php

namespace Tests\Browser;

use App\Group;
use App\GroupTags;
use App\GrouptagsGroups;
use App\Party;
use App\User;
use App\UserGroups;
use DB;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class GroupEditTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        Group::truncate();
        User::truncate();
        GroupTags::truncate();
        GrouptagsGroups::truncate();
        Party::truncate();
        UserGroups::truncate();
        DB::statement('SET foreign_key_checks=1');
    }

    /** @test */
    public function group_tags_retained_after_edited_by_host()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $tag1 = factory(GroupTags::class)->create();
        $tag2 = factory(GroupTags::class)->create();
        $group->addTag($tag1);
        $group->addTag($tag2);

        $host = factory(User::class)->states('Host')->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->browse(function (Browser $browser) use ($group, $host) {
            $browser->loginAs($host);
            $browser->visit('/group/edit/'.$group->idgroups);
            $browser->type('name', 'Foo Fixers');
            $browser->type('location', 'London');
            $browser->press('Save changes');
        });

        $group = Group::find($group->idgroups);
        $this->assertEquals(2, $group->group_tags->count());
    }
}
