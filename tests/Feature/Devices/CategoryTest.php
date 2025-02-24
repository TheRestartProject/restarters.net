<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use App\Party;
use App\User;
use DB;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function testCategoryChange()
    {
        $event = Party::factory()->create();

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $rsp = $this->post('/api/v2/devices', [
            'eventid' => $event->idevents,
            'category' => 11,
            'category_creation' => 11,
            'age' => 1.5,
            'estimate' => 100.00,
            'item_type' => 'Test item type',
            'repair_status' => 'Fixed',
        ]);
        $rsp->assertSuccessful();

        $iddevices = Device::latest()->first()->iddevices;

        $this->device_inputs['category'] = 46;
        unset($this->device_inputs['category_creation']);

        $rsp = $this->patch('/api/v2/devices/' . $iddevices, [
            'eventid' => $event->idevents,
            'category' => 46,
            'age' => 1.5,
            'estimate' => 100.00,
            'item_type' => 'Test item type',
            'repair_status' => 'Fixed',
        ]);

        $rsp->assertSuccessful();

        $device = Device::findOrFail($iddevices);
        self::assertEquals($device->category_creation, 11);
        self::assertEquals($device->category, 46);
    }

    public function testListItems() {
        $cat1 = Category::factory()->create([
            'idcategories' => 444,
            'revision' => 1,
            'name' => 'Flat screen 22-24"',
            'powered' => 1,
        ]);

        $dev1 = Device::factory()->fixed()->create([
            'category' => $cat1,
            'item_type' => 'flatscreen LCD'
        ]);

        $response = $this->get('/api/v2/items');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);
        self::assertEquals(1, count($json['data']));
        self::assertEquals('flatscreen LCD', $json['data'][0]['type']);
        self::assertEquals(true, $json['data'][0]['powered']);
        self::assertEquals(444, $json['data'][0]['idcategories']);
        self::assertEquals('Flat screen 22-24"', $json['data'][0]['categoryname']);
    }
}
