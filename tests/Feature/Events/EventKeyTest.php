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

class EventKeyTest extends TestCase
{
    public function testGetNoParties()
    {
        factory(User::class)->states('Administrator')->create(
            [
                'api_token' => '1234',
            ]
        );
        $this->expectException(NotFoundHttpException::class);
        $this->get('/api/1234/events/group-tag/');
    }
}
