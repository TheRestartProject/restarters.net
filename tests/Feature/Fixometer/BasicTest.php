<?php

namespace Tests\Feature\Fixometer;

use App\Category;
use App\Device;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testPageLoads()
    {
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

        $this->assertVueProperties($response, [
            [
                // Can't assert on latest-data or impact-data as dev systems might have varying info.
                ':clusters' => $clusters,
                ':brands' => '[]',
                ':barrier-list' => '[{"id":1,"barrier":"Spare parts not available"},{"id":2,"barrier":"Spare parts too expensive"},{"id":3,"barrier":"No way to open the product"},{"id":4,"barrier":"Repair information not available"},{"id":5,"barrier":"Lack of equipment"}]',
                ':item-types' => '[]',
                ':is-admin' => 'false',
            ],
        ]);
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
