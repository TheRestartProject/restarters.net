<?php

namespace Tests\Feature;

use App\Group;
use App\GroupTags;
use App\User;

use DB;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupEditTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Group::truncate();
        User::truncate();
        GroupTags::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function group_tags_retained_after_edited_by_host()
    {
        $this->withoutExceptionHandling();

        $group = factory(Group::class)->create();
        $tag = factory(GroupTags::class)->create();
        $group->addTag($tag);

        $host = factory(User::class)->states('Host')->create();
        $this->actingAs($host);
    }
}
