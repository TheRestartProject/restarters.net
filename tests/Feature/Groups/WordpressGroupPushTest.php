<?php

namespace Tests\Feature;

use App\Events\ApproveGroup;
use App\Events\EditGroup;
use App\Group;
use App\Party;
use App\User;

use DB;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WordpressGroupPushTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        User::truncate();
        Group::truncate();
        Party::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    /** @test */
    public function groups_pushed_to_wordpress_when_approved()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('newPost')->once();
        }));

        $group = factory(Group::class)->create();

        $groupData = factory(Group::class)->raw();
        $groupData['moderate'] = 'approve';
        $groupData['group_avatar'] = 'foo.png';

        event(new ApproveGroup($group, $groupData));
    }

    /** @test */
    public function groups_pushed_to_wordpress_when_edited()
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('getPost')->andReturn(100);
            $mock->shouldReceive('editPost')->once();
        }));

        $group = factory(Group::class)->create();
        $group->wordpress_post_id = 100;
        $group->save();

        $groupData = factory(Group::class)->raw();
        $groupData['free_text'] = 'Some change';
        $groupData['website'] = 'https://foo.bar';
        $groupData['group_avatar'] = 'foo.png';
        $groupData['latitude'] = '1';
        $groupData['longitude'] = '1';

        event(new EditGroup($group, $groupData));
    }
}
