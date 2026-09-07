<?php

namespace Tests\Feature;

use App\Group;
use DB;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Mockery;
use Tests\TestCase;

class WordpressFixGroupAvatarsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['restarters.features.wordpress_integration' => true]);
    }

    /** @test */
    public function relative_avatar_paths_are_replaced_with_the_full_url(): void
    {
        $path = '1652779858f105814c6992b17930bf453ab9783610a5c8fb95489.png';
        $group = $this->groupWithPost(100, $path);

        $edited = null;

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) use (&$edited) {
            $mock->shouldReceive('getPost')->with(100)->andReturn([
                'custom_fields' => [
                    ['id' => '7', 'key' => 'group_city', 'value' => 'Portsmouth'],
                    ['id' => '11', 'key' => 'group_avatar_url', 'value' => 'mid_1652779858f105814c6992b17930bf453ab9783610a5c8fb95489.png'],
                ],
            ]);
            $mock->shouldReceive('editPost')->once()->andReturnUsing(function ($postId, $content) use (&$edited) {
                $edited = [$postId, $content];

                return true;
            });
        }));

        $this->artisan('wordpress:group:fix-avatars')->assertSuccessful();

        $this->assertNotNull($edited);
        [$postId, $content] = $edited;
        $this->assertEquals(100, $postId);

        // Only the avatar field should be touched, and it must carry the existing field id so that
        // WordPress replaces the value rather than adding a second field.
        $this->assertEquals([
            'custom_fields' => [
                ['key' => 'group_avatar_url', 'value' => asset('/uploads/mid_'.$path), 'id' => '11'],
            ],
        ], $content);
    }

    /** @test */
    public function the_literal_string_null_is_replaced_too(): void
    {
        $this->groupWithPost(101, null);

        $edited = null;

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) use (&$edited) {
            $mock->shouldReceive('getPost')->with(101)->andReturn([
                'custom_fields' => [
                    ['id' => '11', 'key' => 'group_avatar_url', 'value' => 'null'],
                ],
            ]);
            $mock->shouldReceive('editPost')->once()->andReturnUsing(function ($postId, $content) use (&$edited) {
                $edited = $content;

                return true;
            });
        }));

        $this->artisan('wordpress:group:fix-avatars')->assertSuccessful();

        $this->assertNotNull($edited);
        $this->assertStringStartsWith('http', $edited['custom_fields'][0]['value']);
    }

    /** @test */
    public function groups_which_are_already_correct_are_left_alone(): void
    {
        $path = '1740242182255d38b64da3e4f70b8a1c13030c3a3f4ab44dbf11313.jpg';
        $this->groupWithPost(102, $path);

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) use ($path) {
            $mock->shouldReceive('getPost')->with(102)->andReturn([
                'custom_fields' => [
                    ['id' => '11', 'key' => 'group_avatar_url', 'value' => 'https://restarters.net/uploads/mid_'.$path],
                ],
            ]);
            $mock->shouldReceive('editPost')->never();
        }));

        $this->artisan('wordpress:group:fix-avatars')->assertSuccessful();
    }

    /** @test */
    public function a_dry_run_changes_nothing(): void
    {
        $this->groupWithPost(103, 'foo.png');

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('getPost')->with(103)->andReturn([
                'custom_fields' => [
                    ['id' => '11', 'key' => 'group_avatar_url', 'value' => 'mid_foo.png'],
                ],
            ]);
            $mock->shouldReceive('editPost')->never();
        }));

        $this->artisan('wordpress:group:fix-avatars --dry-run')->assertSuccessful();
    }

    /** @test */
    public function a_group_whose_post_has_gone_is_reported_but_does_not_stop_the_run(): void
    {
        $this->groupWithPost(104, 'foo.png');
        $this->groupWithPost(105, 'bar.png');

        $edited = [];

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) use (&$edited) {
            $mock->shouldReceive('getPost')->with(104)->andThrow(new \Exception('Invalid post ID.'));
            $mock->shouldReceive('getPost')->with(105)->andReturn([
                'custom_fields' => [
                    ['id' => '11', 'key' => 'group_avatar_url', 'value' => 'mid_bar.png'],
                ],
            ]);
            $mock->shouldReceive('editPost')->once()->andReturnUsing(function ($postId, $content) use (&$edited) {
                $edited[] = $postId;

                return true;
            });
        }));

        // The failure is reported in the exit code, but the second group is still repaired.
        $this->artisan('wordpress:group:fix-avatars')->assertFailed();
        $this->assertEquals([105], $edited);
    }

    /** @test */
    public function it_refuses_to_run_when_wordpress_integration_is_disabled(): void
    {
        config(['restarters.features.wordpress_integration' => false]);

        $this->groupWithPost(106, 'foo.png');

        $this->instance(WordpressClient::class, Mockery::mock(WordpressClient::class, function ($mock) {
            $mock->shouldReceive('getPost')->never();
            $mock->shouldReceive('editPost')->never();
        }));

        $this->artisan('wordpress:group:fix-avatars')
            ->expectsOutputToContain('disabled')
            ->assertFailed();
    }

    private function groupWithPost(int $wordpressPostId, ?string $imagePath): Group
    {
        $group = Group::factory()->create([
            'wordpress_post_id' => $wordpressPostId,
            'approved' => true,
        ]);

        if ($imagePath !== null) {
            $imageId = DB::table('images')->insertGetId(['path' => $imagePath]);

            DB::table('xref')->insert([
                'object' => $imageId,
                'object_type' => 5,
                'reference' => $group->idgroups,
                'reference_type' => env('TBL_GROUPS'),
            ]);
        }

        return $group;
    }
}
