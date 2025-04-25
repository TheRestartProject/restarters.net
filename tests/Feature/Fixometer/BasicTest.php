<?php

namespace Tests\Feature\Fixometer;

use App\Models\Category;
use App\Models\Device;
use App\Models\Group;
use App\Helpers\RepairNetworkService;
use App\Models\Party;
use App\Models\Role;
use App\Models\User;
use DB;
use Hash;
use Mockery;
use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testPageLoads(): void
    {
        // Create test data more efficiently
        $group = Group::factory()->create([
            'approved' => true,
        ]);
        
        $event = Party::factory()->create([
            'group' => $group,
            'event_start_utc' => '2000-01-01T12:13:00+00:00',
            'event_end_utc' => '2000-01-01T13:14:00+00:00',
        ]);
        
        // Create device with minimal attributes
        $device = Device::factory()->fixed()->create([
            'category' => 111,
            'category_creation' => 111,
            'event' => $event->idevents,
        ]);

        // Use fast login instead of HTTP login
        $user = $this->fastLoginAsTestUser();
        $response = $this->get('/fixometer');

        $clusters = json_encode([
            [
                'idclusters' => 1,
                'name' => 'Computers and Home Office',
                'categories' => [
                    0 => [
                        'idcategories' => 11,
                        'name' => 'Desktop computer',
                        'powered' => 1,
                        'weight' => null,
                        'footprint' => null,
                        'footprint_reliability' => null,
                        'lifecycle' => null,
                        'lifecycle_reliability' => null,
                        'extendend_lifecycle' => null,
                        'extendend_lifecycle_reliability' => null,
                        'revision' => 2,
                        'cluster' => 1,
                        'aggregate' => 0,
                        'description_short' => '',
                        'description_long' => '',
                    ]
                ]
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
                ':is-admin' => 'false',
            ],
        ]);

        // Verify data structure rather than specific values
        $data = json_decode($props[1][':latest-data'], TRUE);
        $this->assertArrayHasKey('idevents', $data, 'Latest data should have idevents field');
        $this->assertIsNumeric($data['idevents'], 'idevents should be a numeric value');
        
        // Check for other expected fields that should be present in any event data
        $this->assertArrayHasKey('venue', $data, 'Latest data should have venue field');
        $this->assertArrayHasKey('location', $data, 'Latest data should have location field');
    }

    public function testExport(): void {
        // Use fast login instead of HTTP login
        $admin = $this->fastLoginAsTestUser(Role::ADMINISTRATOR);

        // Create category directly with minimal operations
        $category = Category::factory()->create([
            'idcategories' => 1,
            'revision' => 1,
            'name' => 'powered non-misc',
            'powered' => 1,
            'weight' => 4,
            'footprint' => 14.4,
        ]);

        // Create devices with minimal attributes
        Device::factory()->create([
            'category' => $category->idcategories,
            'category_creation' => $category->idcategories,
            'repair_status' => 0
        ]);
        
        Device::factory()->create([
            'category' => $category->idcategories,
            'category_creation' => $category->idcategories,
            'repair_status' => env('DEVICE_FIXED')
        ]);

        $response = $this->get('/export/devices');

        // Verify the response has the expected headers
        $this->assertEquals('attachment; filename=repair-data.csv', $response->headers->get('content-disposition'));
        
        // Since we're running in Docker, we need to read the actual file like in the original test
        $filename = base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'repair-data.csv';
        $this->assertFileExists($filename, 'CSV file should have been created');
        
        $fh = fopen($filename, 'r');
        $this->assertNotFalse($fh, 'CSV file should be readable');
        
        // Skip header row
        fgetcsv($fh);
        $row2 = fgetcsv($fh);
        $row3 = fgetcsv($fh);
        
        // Check expected values
        $this->assertNotFalse($row2, 'Should have at least 2 data rows');
        $this->assertNotFalse($row3, 'Should have at least 3 data rows');
        
        $this->assertEquals(0, (int)$row2[10], 'First device should have 0 waste prevented');
        $this->assertEquals(0, (float)$row2[11], 'First device should have 0 CO2 equivalent');
        $this->assertEquals(4, (int)$row3[10], 'Second device should have 4 waste prevented');
        $this->assertEquals(7.2, (float)$row3[11], 'Second device should have 7.2 CO2 equivalent');
    }
}
