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

    public function testGetEventsWithTag()
    {
        $tag1 = factory(GroupTags::class)->create();

        // Create a group with the tag and one past and one future event.
        $group = factory(Group::class)->create();
        $group->addTag($tag1);

        $eventpast = factory(Party::class)->create(['group' => $group->idgroups,  'event_date' => '2000-01-01']);
        $eventpast->wordpress_post_id = 100;
        $eventpast->save();

        $eventfuture = factory(Party::class)->create(['group' => $group->idgroups,  'event_date' => '2030-01-01']);
        $eventfuture->wordpress_post_id = 100;
        $eventfuture->save();

        factory(User::class)->states('Administrator')->create(
            [
                'api_token' => '1234',
                'access_group_tag_id' => $tag1->id,
            ]
        );

        // Get by tag.
        $response = $this->get('/api/1234/events/group-tag/2000-01-01/2030-01-01');
        $ret = json_decode($response->getContent(), true);
        $this->assertEquals(2, count($ret));
        $this->assertEquals($eventpast->idevents, $ret[0]['id']);
        $this->assertEquals($eventpast->location, $ret[0]['location']['value']);
        $this->assertEquals($eventfuture->idevents, $ret[1]['id']);
        $this->assertEquals($eventfuture->location, $ret[1]['location']['value']);

        // Get same event by id.
        $response = $this->get('/api/1234/event/'.$eventpast->idevents);
        $ret = json_decode($response->getContent(), true);
        $this->assertEquals($eventpast->idevents, $ret['id']);
        $this->assertEquals($eventpast->location, $ret['location']['value']);

        // Get but exclude by date
        $this->expectException(NotFoundHttpException::class);
        $response = $this->get('/api/1234/events/group-tag/2000-01-02/2029-12-31');
    }
}
