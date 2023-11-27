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
        $this->device_inputs = Device::factory()->raw([
                                                          'event_id' => $event->idevents,
                                                          'category' => 11,
                                                          'category_creation' => 11,
                                                          'quantity' => 1,
                                                      ]);

        $admin = User::factory()->administrator()->create();
        $this->actingAs($admin);

        $this->post('/device/create', $this->device_inputs);
        $iddevices = Device::latest()->first()->iddevices;

        $this->device_inputs['category'] = 46;
        unset($this->device_inputs['category_creation']);

        $rsp = $this->post('/device/edit/' . $iddevices, $this->device_inputs);
        self::assertEquals('Device updated!', $rsp['success']);

        $device = Device::findOrFail($iddevices);
        self::assertEquals($device->category_creation, 11);
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
