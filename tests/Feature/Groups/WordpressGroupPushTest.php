<?php

namespace Tests\Feature;

use App\Events\ApproveGroup;
use App\Events\EditGroup;
use App\Group;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\CreateDiscourseGroupForGroup;
use App\Network;
use App\Party;
use App\User;
use Carbon\Carbon;
use DB;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class WordpressGroupPushTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // These tests are hard to get working with genuinely queued events, so use the sync queue.
        $queueManager = $this->app['queue'];
        $queueManager->setDefaultDriver('sync');
    }

    /** @test */
    public function group_approved_wordpress_and_discourse(): void
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('newPost')->once();
        }));

        // When we approve a Restarters group, we should try to create the Discourse group.
        $this->instance(CreateDiscourseGroupForGroup::class, Mockery::mock(CreateDiscourseGroupForGroup::class, function ($mock) {
            $mock->shouldReceive('handle')->once();
        }));

        $network = Network::factory()->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
                                              'approved' => true,
                                           ]);
        $network->addGroup($group);

        $groupData = Group::factory()->raw();
        $groupData['moderate'] = 'approve';
        $groupData['group_avatar'] = 'foo.png';

        event(new ApproveGroup($group, $groupData));
        $this->artisan("queue:work --stop-when-empty");
    }

    /** @test */
    public function groups_pushed_to_wordpress_when_edited(): void
    {
        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('getPost')->andReturn(100);
            $mock->shouldReceive('editPost')->once();
        }));

        $network = Network::factory()->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create();
        $group->wordpress_post_id = 100;
        $group->approved = true;
        $group->save();
        $network->addGroup($group);

        $groupData = Group::factory()->raw();
        $groupData['free_text'] = 'Some change';
        $groupData['website'] = 'https://foo.bar';
        $groupData['group_avatar'] = 'foo.png';
        $groupData['latitude'] = '1';
        $groupData['longitude'] = '1';

        event(new EditGroup($group, $groupData));
        $this->artisan("queue:work --stop-when-empty");
    }
}
