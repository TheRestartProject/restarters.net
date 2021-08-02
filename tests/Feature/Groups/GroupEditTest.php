<?php

namespace Tests\Feature;

use App\Group;
use App\GroupTags;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupEditTest extends TestCase
{
    /** @test */
    public function group_tags_retained_after_edited_by_host()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $tag = factory(GroupTags::class)->create();
        $group->addTag($tag);

        $host = factory(User::class)->states('Host')->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $response = $this->post('/group/edit/'.$group->idgroups, [
            'description' => 'Test',
            'location' => 'London',
            'name' => 'Test',
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
        ]);

        $this->assertContains('Group updated!', $response->getContent());

        $this->assertEquals(1, count($group->group_tags));
        $this->assertEquals($tag->tag_name, $group->group_tags[0]->tag_name);
    }

    /** @test */
    public function invalid_location()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $tag = factory(GroupTags::class)->create();
        $group->addTag($tag);

        $host = factory(User::class)->states('Host')->create();
        $group->addVolunteer($host);
        $group->makeMemberAHost($host);

        $this->actingAs($host);

        $response = $this->post('/group/edit/'.$group->idgroups, [
            'description' => 'Test',
            'location' => 'zzzzzzzzzzzzz1234',
            'name' => 'Test',
            'website' => 'https://therestartproject.org',
            'free_text' => 'HQ',
        ]);

        $this->assertContains('Group could not be saved. Address not found', $response->getContent());
    }
}
