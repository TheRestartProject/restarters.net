<?php

namespace Tests\Feature;

use App\Device;
use App\DeviceBarrier;
use App\Group;
use App\GroupTags;
use App\Helpers\RepairNetworkService;
use App\Network;
use App\Role;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Auth;
use function PHPUnit\Framework\assertEquals;

class APIv2DeviceTest extends TestCase
{
    /**
     * Test that a device we create directly in the DB retrieves as expected over the API.
     *
     * @dataProvider providerDevice
     */
    public function testGetDevice($repair_status, $repair_status_str, $spare_parts, $parts_provider, $parts_provider_str, $barrierid, $barrierstr, $professional_help, $more_time_needed, $do_it_yourself, $next_steps_str) {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $idGroup = $this->createGroup();
        $this->assertNotNull($idGroup);

        $idEvents = $this->createEvent($idGroup, 'yesterday');
        $this->assertNotNull($idEvents);

        $device = Device::create([
            'event' => $idEvents,
            'category' => 11,
            'category_creation' => 11,
            'problem' => 'Test problem',
            'notes' => 'Test notes',
            'brand' => 'Test brand',
            'model' => 'Test model',
            'age' => 1.5,
            'estimate' => 100.00,
            'item_type' => 'Test item type',
            'repair_status' => $repair_status,
            'spare_parts' => $spare_parts,
            'parts_provider' => $parts_provider,
            'professional_help' => $professional_help,
            'more_time_needed' => $more_time_needed,
            'do_it_yourself' => $do_it_yourself,
        ]);

        $iddevices = $device->iddevices;
        $this->assertNotNull($iddevices);

        if ($barrierid) {
            DeviceBarrier::create([
                'device_id' => $iddevices,
                'barrier_id' => $barrierid,
            ]);
        }

        // Test invalid device id.
        try {
            $this->get('/api/v2/devices/-1');
            $this->assertFalse(true);
        } catch (ModelNotFoundException $e) {
        }

        $response = $this->get("/api/v2/devices/$iddevices");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($iddevices, $json['data']['id']);
        $this->assertEquals(11, $json['data']['category']);
        $this->assertEquals('Test item type', $json['data']['item_type']);
        $this->assertEquals('Test brand', $json['data']['brand']);
        $this->assertEquals('Test model', $json['data']['model']);
        $this->assertEquals(1.5, $json['data']['age']);
        $this->assertEquals(100.00, $json['data']['estimate']);
        $this->assertEquals('Test problem', $json['data']['problem']);
        $this->assertEquals('Test notes', $json['data']['notes']);
        $this->assertEquals($repair_status_str, $json['data']['repair_status']);

        if ($parts_provider) {
            $this->assertEquals($parts_provider_str, $json['data']['spare_parts']);
        }

        if ($barrierid) {
            $this->assertEquals($barrierstr, $json['data']['barrier']);
        }

        if ($next_steps_str) {
            $this->assertEquals($next_steps_str, $json['data']['next_steps']);
        }
    }

    /**
     * Create a device over the API and check it retrieves as expected.
     *
     * @dataProvider providerDevice
     */
    public function testCreate($repair_status, $repair_status_str, $spare_parts, $parts_provider, $parts_provider_str, $barrierid, $barrierstr, $professional_help, $more_time_needed, $do_it_yourself, $next_steps_str) {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $idGroup = $this->createGroup();
        $this->assertNotNull($idGroup);

        $idEvents = $this->createEvent($idGroup, 'yesterday');
        $this->assertNotNull($idEvents);

        $params = [
            'eventid' => $idEvents,
            'category' => 11,
            'problem' => 'Test problem',
            'notes' => 'Test notes',
            'brand' => 'Test brand',
            'model' => 'Test model',
            'age' => 1.5,
            'estimate' => 100.00,
            'item_type' => 'Test item type',
            'repair_status' => $repair_status_str,
            'parts_provider' => $parts_provider,
        ];

        if ($next_steps_str) {
            $params['next_steps'] = $next_steps_str;
        }

        if ($parts_provider_str) {
            $params['spare_parts'] = $parts_provider_str;
        }

        $response = $this->post('/api/v2/devices', $params);

        $this->assertTrue($response->isSuccessful());
        $json = json_decode($response->getContent(), true);
        $this->assertTrue(array_key_exists('id', $json));
        $iddevices = $json['id'];
        $this->assertNotNull($iddevices);

        $response = $this->get("/api/v2/devices/$iddevices");
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        $this->assertEquals($iddevices, $json['data']['id']);
        $this->assertEquals(11, $json['data']['category']);
        $this->assertEquals('Test item type', $json['data']['item_type']);
        $this->assertEquals('Test brand', $json['data']['brand']);
        $this->assertEquals('Test model', $json['data']['model']);
        $this->assertEquals(1.5, $json['data']['age']);
        $this->assertEquals(100.00, $json['data']['estimate']);
        $this->assertEquals('Test problem', $json['data']['problem']);
        $this->assertEquals('Test notes', $json['data']['notes']);
        $this->assertEquals($repair_status_str, $json['data']['repair_status']);

        if ($parts_provider) {
            $this->assertEquals($parts_provider_str, $json['data']['spare_parts']);
        }

        if ($barrierid) {
            $this->assertEquals($barrierstr, $json['data']['barrier']);
        }

        if ($next_steps_str) {
            $this->assertEquals($next_steps_str, $json['data']['next_steps']);
        }
    }

    public function providerDevice()
    {
        return [
            [
                Device::REPAIR_STATUS_FIXED,
                'Fixed',
                Device::SPARE_PARTS_NOT_NEEDED,
                null,
                null,
                0,
                null,
                0,
                0,
                0,
                null
            ],
            [
                Device::REPAIR_STATUS_REPAIRABLE,
                'Repairable',
                Device::SPARE_PARTS_NEEDED,
                Device::PARTS_PROVIDER_THIRD_PARTY,
                'Third party',
                0,
                null,
                1,
                0,
                0,
                'Professional help'
            ],
            [
                Device::REPAIR_STATUS_ENDOFLIFE,
                'End-of-life',
                Device::SPARE_PARTS_NOT_NEEDED,
                null,
                null,
                1,
                'Spare parts not available',
                0,
                0,
                0,
                null
            ],
        ];
    }
}
