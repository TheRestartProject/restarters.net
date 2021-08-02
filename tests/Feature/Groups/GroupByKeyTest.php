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

    public function testGetGroupsWithTag()
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
        $response = $this->get('/api/1234/groups/group-tag/');
        $ret = json_decode($response->getContent(), true);
        $this->assertEquals(1, count($ret));
        $this->assertEquals($group->idgroups, $ret[0]['id']);
        $this->assertEquals($group->name, $ret[0]['name']);
        $this->assertEquals(1, count($ret[0]['upcoming_parties']));
        $this->assertEquals($eventfuture->idevents, $ret[0]['upcoming_parties'][0]['event_id']);
        $this->assertEquals(1, count($ret[0]['past_parties']));
        $this->assertEquals($eventpast->idevents, $ret[0]['past_parties'][0]['event_id']);

        // Get same group by id.
        $response = $this->get('/api/1234/group/'.$group->idgroups.'/2000-01-01/2030-01-01');
        $ret = json_decode($response->getContent(), true);
        $this->assertEquals($group->idgroups, $ret['id']);
        $this->assertEquals($group->name, $ret['name']);
        $this->assertEquals(1, count($ret['upcoming_parties']));
        $this->assertEquals($eventfuture->idevents, $ret['upcoming_parties'][0]['event_id']);
        $this->assertEquals(1, count($ret['past_parties']));
        $this->assertEquals($eventpast->idevents, $ret['past_parties'][0]['event_id']);

        // Get stats
        $response = $this->get('/api/group-tag/stats/'.$group->idgroups);
        $stats = json_decode($response->getContent(), true);
        $this->assertEquals(1, $stats['parties']);

        // Get but exclude by date
        $response = $this->get('/api/1234/group/'.$group->idgroups.'/2000-01-02/2029-12-31');
        $ret = json_decode($response->getContent(), true);
        $this->assertEquals($group->idgroups, $ret['id']);
        $this->assertEquals($group->name, $ret['name']);
        $this->assertEquals(0, count($ret['upcoming_parties']));
        $this->assertEquals(0, count($ret['past_parties']));
    }
}
