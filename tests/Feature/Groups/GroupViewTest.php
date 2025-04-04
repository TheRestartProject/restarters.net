<?php

namespace Tests\Feature\Groups;

use App\Device;
use App\Party;
use App\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class GroupViewTest extends TestCase
{
    public function testBasic()
    {
        // Check we can create a group and view it.
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        // Create an event and a fixed device, as this will be used in stats info returned from GroupController.
        // We want the fixed device to be in a category with a cluster.
        $event = Party::factory()->create([
            'group' => $id,
            'event_start_utc' => Carbon::parse('1pm tomorrow')->toIso8601String(),
            'event_end_utc' => Carbon::parse('3pm tomorrow')->toIso8601String()
        ]);
        $device = Device::factory()->fixed()->create([
            'category' => 11,
            'category_creation' => 11,
            'event' => $event->idevents,
        ]);

        // View with id.
        $response = $this->get("/group/view/$id");

        $props = $this->assertVueProperties($response, [
            [],
            [
                ':idgroups' => $id,
                ':canedit' => 'true',
                ':can-see-delete' => 'true',
                ':can-perform-delete' => 'false',
                ':top-devices' => json_encode([
                    [
                        'counter' => 1,
                        'name' => 'Desktop computer'
                    ]
                ]),
            ],
        ]);
        $this->assertEquals(1, count(json_decode($props[1][':events'], TRUE)));
    }

    public function testInvalidGroup()
    {
        $this->loginAsTestUser(Role::RESTARTER);
        $this->expectException(NotFoundHttpException::class);
        $this->get('/group/view/undefined');
    }

    public function testInvalidGroup2()
    {
        $this->loginAsTestUser(Role::RESTARTER);
        $this->expectException(NotFoundHttpException::class);
        $this->get('/group/view/1');
    }

    public function testCanDelete()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        // Create a past event
        $event = Party::factory()->moderated()->create([
                                                                        'event_start_utc' => '2000-01-01T10:15:05+05:00',
                                                                        'event_end_utc' => '2000-01-0113:45:05+05:00',
                                                                        'group' => $id,
                                                                    ]);

        // Groups are deletable unless they have an event with a device.
        $response = $this->get("/group/view/$id");
        $this->assertVueProperties($response, [
            [],
            [
                ':idgroups' => $id,
                ':can-see-delete' => 'true',
                ':can-perform-delete' => 'true',
            ],
        ]);

        $iddevices = $this->createDevice($event->idevents,
            'misc', null, 1.5, 0, '',
            Device::REPAIR_STATUS_FIXED_STR, null, null, 111);

        $response = $this->get("/group/view/$id");
        $this->assertVueProperties($response, [
            [],
            [
                ':idgroups' => $id,
                ':can-see-delete' => 'true',
                ':can-perform-delete' => 'false',
            ],
        ]);

        # Check the device shows in the API.
        $rsp2 = $this->get('/api/devices/1/10?sortBy=iddevices&sortDesc=asc&powered=false');
        $ret = json_decode($rsp2->getContent(), true);
        self::assertEquals(1, $ret['count']);
        self::assertEquals(1, count($ret['items']));
        self::assertEquals($event->idevents, $ret['items'][0]['eventid']);

        // Only administrators can delete.
        foreach (['Restarter', 'Host', 'NetworkCoordinator'] as $role) {
            $user = \App\User::factory()->{lcfirst($role)}()->create();
            $this->actingAs($user);
            $response = $this->get("/group/view/$id");
            $this->assertVueProperties($response, [
                [],
                [
                    ':idgroups' => $id,
                    ':can-see-delete' => 'false',
                    ':can-perform-delete' => 'false',
                ],
            ]);
        }

        // Test stats API.
        foreach (['fixometer', 'consume', 'manufacture'] as $format) {
            $response = $this->get("/api/outbound/info/party/{$event->idevents}/$format");
            $stats = json_decode($response->getContent(), TRUE);
            self::assertEquals(1, $stats['co2']);

            $response = $this->get("/api/outbound/info/group/{$id}/$format");
            $stats = json_decode($response->getContent(), TRUE);
            self::assertEquals(1, $stats['co2']);
        }
    }

    public function testInProgressVisible() {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        Party::factory()->moderated()->create([
                                                                    'event_start_utc' => Carbon::parse('1 hour ago')->toIso8601String(),
                                                                    'event_end_utc' => Carbon::parse('4pm tomorrow')->toIso8601String(),
                                                                    'group' => $id,
                                                                ]);

        // Event should show in list for group.
        $response = $this->get("/group/view/$id");
        $props = $this->getVueProperties($response);
        $events = json_decode($props[1][':events'], true);
        self::assertEquals(Party::latest()->first()->idevents, $events[0]['idevents']);
    }
}
