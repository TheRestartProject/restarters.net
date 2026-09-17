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

    /** @test */
    public function group_avatar_pushed_as_absolute_url_when_edited(): void
    {
        $edited = null;

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) use (&$edited) {
            $mock->shouldReceive('getPost')->andReturn([]);
            $mock->shouldReceive('editPost')->once()->andReturnUsing(function ($postId, $content) use (&$edited) {
                $edited = $content;

                return true;
            });
        }));

        $network = Network::factory()->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
            'wordpress_post_id' => 100,
            'approved' => true,
        ]);
        $network->addGroup($group);

        $path = '1740242182255d38b64da3e4f70b8a1c13030c3a3f4ab44dbf11313.jpg';
        $this->giveGroupAnImage($group, $path);

        $groupData = Group::factory()->raw();
        // The API used to put a bare filename in here, because UPLOADS_URL has never been defined.
        $groupData['group_avatar'] = 'mid_'.$path;

        event(new EditGroup($group, $groupData));
        $this->artisan('queue:work --stop-when-empty');

        $this->assertNotNull($edited);
        $avatar = $this->customField($edited, 'group_avatar_url');
        $this->assertEquals(asset('/uploads/mid_'.$path), $avatar);
    }

    /** @test */
    public function group_with_no_image_still_pushes_an_absolute_url_when_edited(): void
    {
        $edited = null;

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) use (&$edited) {
            $mock->shouldReceive('getPost')->andReturn([]);
            $mock->shouldReceive('editPost')->once()->andReturnUsing(function ($postId, $content) use (&$edited) {
                $edited = $content;

                return true;
            });
        }));

        $network = Network::factory()->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create([
            'wordpress_post_id' => 100,
            'approved' => true,
        ]);
        $network->addGroup($group);

        $groupData = Group::factory()->raw();
        // This is what the API sent for a group with no image at all.
        $groupData['group_avatar'] = 'null';

        event(new EditGroup($group, $groupData));
        $this->artisan('queue:work --stop-when-empty');

        $this->assertNotNull($edited);
        $avatar = $this->customField($edited, 'group_avatar_url');
        $this->assertEquals($group->fresh()->groupImagePath(), $avatar);
        $this->assertStringStartsWith('http', $avatar);
    }

    /** @test */
    public function created_and_edited_groups_push_the_same_avatar_url(): void
    {
        $created = null;
        $edited = null;

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) use (&$created, &$edited) {
            $mock->shouldReceive('getPost')->andReturn([]);
            $mock->shouldReceive('newPost')->once()->andReturnUsing(function ($title, $body, $content) use (&$created) {
                $created = $content;

                return 100;
            });
            $mock->shouldReceive('editPost')->once()->andReturnUsing(function ($postId, $content) use (&$edited) {
                $edited = $content;

                return true;
            });
        }));

        $this->instance(CreateDiscourseGroupForGroup::class, Mockery::mock(CreateDiscourseGroupForGroup::class, function ($mock) {
            $mock->shouldReceive('handle');
        }));

        $network = Network::factory()->create([
            'name' => 'Restart',
            'events_push_to_wordpress' => true,
        ]);
        $group = Group::factory()->create();
        $network->addGroup($group);

        $path = '1740242182255d38b64da3e4f70b8a1c13030c3a3f4ab44dbf11313.jpg';
        $this->giveGroupAnImage($group, $path);

        $groupData = Group::factory()->raw();
        $groupData['group_avatar'] = 'mid_'.$path;

        event(new ApproveGroup($group, $groupData));
        $this->artisan('queue:work --stop-when-empty');

        event(new EditGroup($group->fresh(), $groupData));
        $this->artisan('queue:work --stop-when-empty');

        $this->assertNotNull($created);
        $this->assertNotNull($edited);
        $this->assertEquals(
            $this->customField($created, 'group_avatar_url'),
            $this->customField($edited, 'group_avatar_url')
        );
    }

    private function giveGroupAnImage(Group $group, string $path): void
    {
        $imageId = DB::table('images')->insertGetId(['path' => $path]);

        DB::table('xref')->insert([
            'object' => $imageId,
            'object_type' => 5,
            'reference' => $group->idgroups,
            'reference_type' => env('TBL_GROUPS'),
        ]);
    }

    private function customField(array $content, string $key)
    {
        foreach ($content['custom_fields'] as $field) {
            if ($field['key'] === $key) {
                return $field['value'];
            }
        }

        $this->fail("No $key custom field was pushed to WordPress");
    }
}
