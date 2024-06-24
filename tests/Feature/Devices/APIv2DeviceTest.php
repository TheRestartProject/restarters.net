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
     * This logic duplicates that in DeviceController, but it's worth testing to make sure that the API is
     * behaving as we'd expect from the DB entries.
     *
     * @dataProvider providerDevice
     */
    public function testGetDevice($repair_status_str, $parts_provider_str, $barrierstr, $next_steps_str) {
        $this->loginAsTestUser(Role::ADMINISTRATOR);
        $idGroup = $this->createGroup();
        $this->assertNotNull($idGroup);

        $idEvents = $this->createEvent($idGroup, 'yesterday');
        $this->assertNotNull($idEvents);

        switch ($repair_status_str) {
            case Device::REPAIR_STATUS_FIXED_STR:
                $repair_status = Device::REPAIR_STATUS_FIXED;
                break;
            case Device::REPAIR_STATUS_REPAIRABLE_STR:
                $repair_status = Device::REPAIR_STATUS_REPAIRABLE;
                break;
            case Device::REPAIR_STATUS_ENDOFLIFE_STR:
                $repair_status = Device::REPAIR_STATUS_ENDOFLIFE;
                break;
        }

        switch ($parts_provider_str) {
            case Device::PARTS_PROVIDER_NO_STR:
                $parts_provider = 0;
                break;
            case Device::PARTS_PROVIDER_THIRD_PARTY_STR:
                $parts_provider = Device::PARTS_PROVIDER_THIRD_PARTY;
                break;
            case Device::PARTS_PROVIDER_MANUFACTURER_STR:
                $parts_provider = Device::PARTS_PROVIDER_MANUFACTURER;
        }

        switch ($next_steps_str) {
            case Device::NEXT_STEPS_MORE_TIME_NEEDED_STR:
                $more_time_needed = 1;
                break;
            case Device::NEXT_STEPS_PROFESSIONAL_HELP_STR:
                $professional_help = 1;
                break;
            case Device::NEXT_STEPS_DO_IT_YOURSELF_STR:
                $do_it_yourself = 1;
                break;
        }

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
            'parts_provider' => $parts_provider,
            'more_time_needed' => $more_time_needed ?? 0,
            'professional_help' => $professional_help ?? 0,
            'do_it_yourself' => $do_it_yourself ?? 0,
        ]);

        $iddevices = $device->iddevices;
        $this->assertNotNull($iddevices);

        if ($barrierstr) {
            $barrier = DB::table('barriers')->where('barrier', $barrierstr)->first()->id;

            DeviceBarrier::create([
                'device_id' => $iddevices,
                'barrier_id' => $barrier
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

        if ($parts_provider_str) {
            $this->assertEquals($parts_provider_str, $json['data']['spare_parts']);
        }

        if ($barrierstr) {
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
    public function testCreate($repair_status_str, $parts_provider_str, $barrierstr, $next_steps_str) {
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
            'parts_provider' => $parts_provider_str,
            'barrier' => $barrierstr,
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
        $this->assertEquals($iddevices, $json['device']['id']);

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

        if ($parts_provider_str) {
            $this->assertEquals($parts_provider_str, $json['data']['spare_parts']);
        }

        if ($barrierstr) {
            $this->assertEquals($barrierstr, $json['data']['barrier']);
        }

        if ($next_steps_str) {
            $this->assertEquals($next_steps_str, $json['data']['next_steps']);
        }
    }

    public function providerDevice()
    {
        // TODO Neil - I am not confident that I know the valid combinations of repair status/parts provider/
        // barrier/next steps.
        //
        // Please can you consider which values we should be testing here?
        return [
            [
                Device::REPAIR_STATUS_FIXED_STR,
                Device::PARTS_PROVIDER_NO_STR,
                null,
                null,
            ],
            [
                Device::REPAIR_STATUS_REPAIRABLE_STR,
                Device::PARTS_PROVIDER_THIRD_PARTY_STR,
                Device::BARRIER_SPARE_PARTS_NOT_AVAILABLE_STR,
                Device::NEXT_STEPS_PROFESSIONAL_HELP_STR,
            ],
            [
                Device::REPAIR_STATUS_ENDOFLIFE_STR,
                Device::PARTS_PROVIDER_NO_STR,
                Device::BARRIER_SPARE_PARTS_NOT_AVAILABLE_STR,
                null,
            ],
        ];
    }
}
