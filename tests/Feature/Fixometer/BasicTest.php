<?php

namespace Tests\Feature\Fixometer;

use App\Category;
use App\Device;
use App\Group;
use App\Helpers\RepairNetworkService;
use App\Party;
use App\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testPageLoads()
    {
        // Create a past event with a fixed device.  This is shown on the Fixometer page as the latest data.
        $group = factory(Group::class)->create([
                                                   'wordpress_post_id' => '99999'
                                               ]);
        $event = factory(Party::class)->create([
                                                   'group' => $group,
                                                   'event_start_utc' => '2000-01-01T12:13:00+00:00',
                                                   'event_end_utc' => '2000-01-01T13:14:00+00:00',
                                               ]);
        $device = factory(Device::class)->states('fixed')->create([
                                                                      'category' => 111,
                                                                      'category_creation' => 111,
                                                                      'event' => $event->idevents,
                                                                  ]);

        // Test the dashboard page loads.  Most of the work is done inside Vue, so a basic test is just that the
        // Vue component exists.
        $this->loginAsTestUser();
        $response = $this->get('/fixometer');

        // No actual cluster info in test environment.
        $clusters = json_encode([
            [
                'idclusters' => 1,
                'name' => 'Computers and Home Office',
                'categories' => [],
            ],
            [
                'idclusters' => 2,
                'name' => 'Electronic Gadgets',
                'categories' => [],
            ],
            [
                'idclusters' => 3,
                'name' => 'Home Entertainment',
                'categories' => [],
            ],
            [
                'idclusters' => 4,
                'name' => 'Kitchen and Household Items',
                'categories' => [],
            ],
            [
                'idclusters' => 5,
                'name' => 'Non-Powered Items',
                'categories' => [],
            ],
        ]);

        $props = $this->assertVueProperties($response, [
            [],
            [
                // Can't assert on latest-data or impact-data as dev systems might have varying info.
                ':clusters' => $clusters,
                ':brands' => '[]',
                ':barrier-list' => '[{"id":1,"barrier":"Spare parts not available"},{"id":2,"barrier":"Spare parts too expensive"},{"id":3,"barrier":"No way to open the product"},{"id":4,"barrier":"Repair information not available"},{"id":5,"barrier":"Lack of equipment"}]',
                ':item-types' => '[]',
                ':is-admin' => 'false',
            ],
        ]);

        $data = json_decode($props[1][':latest-data'], TRUE);
        $this->assertEquals($event->idevents, $data['idevents']);
    }

    public function testExport() {
        $this->loginAsTestUser();

        DB::statement('SET foreign_key_checks=0');
        Category::truncate();
        DB::statement('SET foreign_key_checks=1');
        factory(Category::class)->create([
                                             'idcategories' => 1,
                                             'revision' => 1,
                                             'name' => 'powered non-misc',
                                             'powered' => 1,
                                             'weight' => 4,
                                             'footprint' => 14.4,
                                         ]);

        factory(Device::class)->create([
                                                     'category' => 1,
                                                     'category_creation' => 1,
                                                     'repair_status' => 0
                                                 ]);
        factory(Device::class)->create([
                                           'category' => 1,
                                           'category_creation' => 1,
                                           'repair_status' => env('DEVICE_FIXED')
                                       ]);

        $response = $this->get('/export/devices');

        $this->assertTrue($response->headers->get('content-disposition') == 'attachment; filename=devices.csv');

        // Bit hacky, but grab the file that was created.  Can't find a way to do this in Laravel easily, though it's
        // probably possible using mocking.
        $filename = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'devices.csv';
        $fh = fopen($filename, 'r');

        # Skip headers.
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        $row3 = fgetcsv($fh);
        $this->assertEquals(0, $row2[9]);
        $this->assertEquals(0, $row2[10]);
        $this->assertEquals(4, $row3[9]);
        $this->assertEquals(7.2, $row3[10]);
    }
}
