<?php

namespace Tests\Feature;

use App\GroupTags;
use App\Role;
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
    /** @story:GroupTagsController::index */
    public function testList()
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

    /** @story:GroupTagsController::postCreateTag */
    public function testCreate()
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

    /** @story:GroupTagsController::getEditTag */
    public function testGetEdit()
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

    /** @story:GroupTagsController::postEditTag */
    public function testEdit()
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

    /** @story:GroupTagsController::getDeleteTag */
    public function testDelete() {
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
