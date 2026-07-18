<?php

namespace Tests\Feature\Fixometer;

use App\Category;
use App\Device;
use App\Role;
use DB;
use Tests\TestCase;

class BasicTest extends TestCase
{
    // testPageLoads was a pure Blade-render check of the /fixometer page
    // (Vue props for clusters/brands/barrier-list/latest-data). That route
    // is retired under the Nuxt cutover — the Fixometer page is now
    // client-side in the SPA, backed by /api/devices and friends, which
    // have their own coverage (see DeviceApiQueryTest).

    public function testExport(): void {
        $this->loginAsTestUser(Role::ADMINISTRATOR);

        DB::statement('SET foreign_key_checks=0');
        Category::truncate();
        DB::statement('SET foreign_key_checks=1');
        Category::factory()->create([
                                             'idcategories' => 1,
                                             'revision' => 1,
                                             'name' => 'powered non-misc',
                                             'powered' => 1,
                                             'weight' => 4,
                                             'footprint' => 14.4,
                                         ]);

        Device::factory()->create([
                                                     'category' => 1,
                                                     'category_creation' => 1,
                                                     'repair_status' => 0
                                                 ]);
        Device::factory()->create([
                                           'category' => 1,
                                           'category_creation' => 1,
                                           'repair_status' => env('DEVICE_FIXED')
                                       ]);

        $response = $this->get('/export/devices');

        $this->assertEquals('attachment; filename=repair-data.csv', $response->headers->get('content-disposition'));

        // Bit hacky, but grab the file that was created.  Can't find a way to do this in Laravel easily, though it's
        // probably possible using mocking.
        $filename = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'repair-data.csv';
        $fh = fopen($filename, 'r');

        # Skip headers.
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        $row3 = fgetcsv($fh);
        $this->assertEquals(0, $row2[10]);
        $this->assertEquals(0, $row2[11]);
        $this->assertEquals(4, $row3[10]);
        $this->assertEquals(7.2, $row3[11]);
    }
}
