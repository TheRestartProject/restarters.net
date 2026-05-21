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
    public function testCategoryChange(): void
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

    public function testListItems(): void {
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

    public function testCategorySuggestionLogic(): void
    {
        // Use existing categories from seed data
        $catSmallKitchen = Category::where('name', 'Small kitchen item')->first();
        $catMisc = Category::where('name', 'Misc')->first();

        // Skip test if categories don't exist
        if (!$catSmallKitchen || !$catMisc) {
            $this->markTestSkipped('Required categories not seeded');
        }

        // Create 3 devices with item_type "Test Food Processor" using Small kitchen item category
        for ($i = 0; $i < 3; $i++) {
            Device::factory()->fixed()->create([
                'category' => $catSmallKitchen->idcategories,
                'item_type' => 'Test Food Processor',
            ]);
        }

        // Create 1 device with item_type "Test Food Processor" using Misc category
        Device::factory()->fixed()->create([
            'category' => $catMisc->idcategories,
            'item_type' => 'Test Food Processor',
        ]);

        // Clear cache to ensure fresh data
        \Cache::forget('item_types');

        // Get items list
        $response = $this->get('/api/v2/items');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        // Find the Test Food Processor item
        $foodProcessor = null;
        foreach ($json['data'] as $item) {
            if ($item['type'] === 'Test Food Processor') {
                $foodProcessor = $item;
                break;
            }
        }

        // Assert that "Test Food Processor" suggests "Small kitchen item" (most common category)
        self::assertNotNull($foodProcessor, 'Test Food Processor item type should be in the list');
        self::assertEquals($catSmallKitchen->idcategories, $foodProcessor['idcategories'], 'Should suggest Small kitchen item category (most commonly used)');
        self::assertEquals('Small kitchen item', $foodProcessor['categoryname']);
        self::assertEquals(true, $foodProcessor['powered']);
    }

    public function testCategorySuggestionRespectsPoweredStatus(): void
    {
        // Use existing categories from seed data
        $catPowered = Category::where('powered', 1)->first();
        $catUnpowered = Category::where('powered', 0)->first();

        // Skip test if categories don't exist
        if (!$catPowered || !$catUnpowered) {
            $this->markTestSkipped('Required powered/unpowered categories not seeded');
        }

        // Create powered device with item_type "Test Unique Item"
        Device::factory()->fixed()->create([
            'category' => $catPowered->idcategories,
            'item_type' => 'Test Unique Item',
        ]);

        // Create unpowered device with item_type "Test Unique Item"
        Device::factory()->fixed()->create([
            'category' => $catUnpowered->idcategories,
            'item_type' => 'Test Unique Item',
        ]);

        // Clear cache to ensure fresh data
        \Cache::forget('item_types');

        // Get items list
        $response = $this->get('/api/v2/items');
        $response->assertSuccessful();
        $json = json_decode($response->getContent(), true);

        // Count items with "Test Unique Item" type
        $testItems = array_filter($json['data'], function($item) {
            return $item['type'] === 'Test Unique Item';
        });

        // Should have 2 separate entries - one powered, one unpowered
        self::assertCount(2, $testItems, 'Should have separate entries for powered and unpowered');

        // Verify each has the correct category
        $poweredItem = null;
        $unpoweredItem = null;
        foreach ($testItems as $item) {
            if ($item['powered']) {
                $poweredItem = $item;
            } else {
                $unpoweredItem = $item;
            }
        }

        self::assertNotNull($poweredItem);
        self::assertEquals($catPowered->idcategories, $poweredItem['idcategories']);
        self::assertEquals(true, $poweredItem['powered']);

        self::assertNotNull($unpoweredItem);
        self::assertEquals($catUnpowered->idcategories, $unpoweredItem['idcategories']);
        self::assertEquals(false, $unpoweredItem['powered']);
    }
}
