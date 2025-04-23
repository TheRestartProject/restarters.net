<?php

namespace Tests\Feature;

use App\Models\GroupTags;
use App\Models\Role;
use Carbon\Carbon;
use DB;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class GroupTagsTest extends TestCase
{
    public function testList(): void
    {
        $admin = $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->get('/tags');
        $response->assertRedirect('/user/forbidden');

        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);
        $tag = GroupTags::factory()->create();

        $response = $this->get('/tags');
        $response->assertSuccessful();
        $response->assertSeeText($tag->tag_name);
    }

    public function testCreate(): void
    {
        $tag = GroupTags::factory()->create();

        $admin = $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->post('/tags/create', [
            'tag-name' => $tag->tag_name,
            'tag-description' => $tag->tag_description,
        ]);
        $response->assertRedirect('/user/forbidden');

        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->post('/tags/create', [
            'tag-name' => $tag->tag_name,
            'tag-description' => $tag->tag_description,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function testGetEdit(): void
    {
        $tag = GroupTags::factory()->create();

        $admin = $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->get('/tags/edit/' . $tag->id);
        $response->assertRedirect('/user/forbidden');

        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->get('/tags/edit/' . $tag->id);
        $response->assertSuccessful();
        $response->assertSeeText($tag->tag_name);
    }

    public function testEdit(): void
    {
        $tag = GroupTags::factory()->create();

        $admin = $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->post('/tags/edit/' . $tag->id, [
            'tag-name' => $tag->tag_name,
            'tag-description' => $tag->tag_description,
        ]);
        $response->assertRedirect('/user/forbidden');

        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->post('/tags/edit/' . $tag->id, [
            'tag-name' => $tag->tag_name . '2',
            'tag-description' => $tag->tag_description . '2',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function testDelete(): void {
        $tag = GroupTags::factory()->create();

        $admin = $this->loginAsTestUser(Role::RESTARTER);
        $response = $this->get('/tags/delete/' . $tag->id);
        $response->assertRedirect('/user/forbidden');

        $admin = $this->loginAsTestUser(Role::ADMINISTRATOR);

        $response = $this->get('/tags/delete/' . $tag->id);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
