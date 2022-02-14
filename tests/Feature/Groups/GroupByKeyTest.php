<?php

namespace Tests\Feature;

use App\Events\ApproveEvent;
use App\Events\EditEvent;
use App\Group;
use App\GroupNetwork;
use App\GroupTags;
use App\Network;
use App\Party;
use App\User;
use App\UserGroups;
use Carbon\Carbon;
use DB;
use HieuLe\WordpressXmlrpcClient\WordpressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class GroupByKeyTest extends TestCase
{
    public function testGetGroupsNoUserTag()
    {
        factory(User::class)->states('Administrator')->create(
            [
                'api_token' => '1234',
            ]
        );
        $this->expectException(NotFoundHttpException::class);
        $this->get('/api/1234/groups/group-tag/');
    }

    public function testGetGroupsNoGroupsWithTag()
    {
        $tag1 = factory(GroupTags::class)->create();

        factory(User::class)->states('Administrator')->create(
            [
                'api_token' => '1234',
                'access_group_tag_id' => $tag1->id,
            ]
        );

        $this->expectException(NotFoundHttpException::class);
        $this->get('/api/1234/groups/group-tag/');
    }
}
