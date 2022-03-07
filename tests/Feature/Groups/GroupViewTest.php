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
        $response = $this->get("/group/view/$id");

        $this->assertVueProperties($response, [
            [],
            [
                ':idgroups' => $id,
                ':canedit' => 'true',
                ':can-see-delete' => 'true',
                ':can-perform-delete' => 'true',
                ':top-devices' => '[]',
                ':events' => '[]',
            ],
        ]);
    }

    public function testInvalidGroup()
    {
        $this->loginAsTestUser(Role::RESTARTER);
        $this->expectException(NotFoundHttpException::class);
        $this->get('/group/view/undefined');
    }

    public function testCanDelete()
    {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $id = $this->createGroup();
        $this->assertNotNull($id);

        // Create a past event
        $event = factory(Party::class)->states('moderated')->create([
                                                                        'event_date' => Carbon::yesterday()->toDateString(),
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

        $response = $this->post('/device/create', factory(Device::class)->raw([
                                                                                  'category' => env('MISC_CATEGORY_ID_POWERED'),
                                                                                  'category_creation' => env('MISC_CATEGORY_ID_POWERED'),
                                                                                  'event_id' => $event->idevents,
                                                                                  'quantity' => 1,
                                                                                  'repair_status' => Device::REPAIR_STATUS_FIXED,
                                                                                  'category' => 111
                                                                              ]));
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
        $rsp2 = $this->get('/api/devices/1/10?sortBy=iddevices&sortDesc=asc&powered=true');
        $ret = json_decode($rsp2->getContent(), true);
        self::assertEquals(1, $ret['count']);
        self::assertEquals(1, count($ret['items']));
        self::assertEquals($event->idevents, $ret['items'][0]['event']);

        // Only administrators can delete.
        foreach (['Restarter', 'Host', 'NetworkCoordinator'] as $role) {
            $user = factory(\App\User::class)->states($role)->create();
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
}
