<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use App\Mobifix;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobifixTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        Device::truncate();
        Mobifix::truncate();
        DB::table('devices_faults_mobiles_adjudicated')->truncate();
    }

    /** @test */
    public function fetch_mobifix_record()
    {
        $data = $this->_setup_data();
        $Mobifix = new Mobifix;

        for ($i = 0; $i < 101; $i++) {
            $result = $Mobifix->fetchFault();
            $this->assertTrue(is_array($result), 'fetch_mobifix_record: result is not array');
            $this->assertEquals(1, count($result), 'fetch_mobifix_record: wrong result count');
            $this->assertGreaterThan(0, $result[0]->iddevices, 'fetch_mobifix_record: iddevices is 0 or null');
            $this->assertTrue(array_key_exists($result[0]->iddevices, $data['devices_include']), 'fetch_mobifix_record: result is not in list of inclusions');
            $this->assertFalse(array_key_exists($result[0]->iddevices, $data['devices_exclude']), 'fetch_mobifix_record: result is in list of exclusions');
        }
    }

    /** @test */
    public function fetch_mobifix_status()
    {
        $data = $this->_setup_data();

        $Mobifix = new Mobifix;
        $result = $Mobifix->fetchStatus();
        $this->assertTrue(is_array($result));
        foreach ($data['status'] as $k => $v) {
            $this->assertTrue(array_key_exists($k, $result), 'fetch_mobifix_status: missing key - '.$k);
            if (! is_array($v)) {
                $this->assertEquals($v, $result[$k][0]->total, 'fetch_mobifix_status: wrong '.$k);
            } else {
                foreach ($v[0] as $key => $val) {
                    $this->assertTrue(property_exists($result[$k][0], $key), 'fetch_mobifix_status: missing key - '.$key);
                    $this->assertEquals($val, $result[$k][0]->{$key}, 'fetch_mobifix_status: wrong '.$key);
                }
            }
        }
    }

    /** @test */
    public function update_mobifix_devices()
    {
        $this->_setup_data();

        $Mobifix = new Mobifix;
        $result = $Mobifix->updateDevices();
        $expected = array_fill(1, 11, '');
        $expected[1] = 'fault_type_1';
        $expected[2] = 'fault_type_1';
        $expected[4] = 'fault_type_1';
        $expected[5] = 'fault_type_1';
        $expected[10] = 'foo';
        $expected[11] = 'foo';
        foreach ($expected as $k => $v) {
            $this->assertDatabaseHas('devices', [
                'iddevices' => $k,
                'fault_type' => $v,
            ]);
        }
    }

    /** @test */
    public function update_mobifix_empty_problem()
    {
        $this->_setup_data();

        $Mobifix = new Mobifix;
        $result = $Mobifix->updateDevicesWithEmptyProblem();
        $expected = array_fill(1, 11, '');
        $expected[1] = 'foo';
        $expected[9] = 'Unknown';
        $expected[10] = 'foo';
        $expected[11] = 'foo';
        foreach ($expected as $k => $v) {
            $this->assertDatabaseHas('devices', [
                'iddevices' => $k,
                'fault_type' => $v,
            ]);
        }
    }

    /**
     * Cases:
     *  opinions = 0 (1 record)
     *  opinions = 1 (1 record)
     *  opinions = 2 (2 records - 1 majority, 1 split)
     *  opinions = 3 (4 records - 1 consensus, 1 majority, 1 split, 1 adjudicated)
     *
     * @return array
     */
    protected function _setup_data()
    {
        $cats_in = [
            'mobile' => 25,
        ];
        $cats_ex = [
            'tablet' => 26,
            'misc' => 46,
        ];
        $devs_in = [];
        $devs_ex = [];
        $i = 1;
        foreach ($cats_in as $cat => $id) {
            // 3 devices in right category with non-empty problem text
            // first has been previously assigned a fault_type which may be changed
            $devs_in[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40), 'foo');
            $devs_in[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40));
            $devs_in[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40));
            $devs_in[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40));
            $devs_in[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40));
            $devs_in[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40));
            $devs_in[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40));
            $devs_in[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40));
            // device is right category but empty problem text
            $devs_ex[$i++] = $this->_insert_mobifix_device($cat, $id, '');
        }
        foreach ($cats_ex as $cat => $id) {
            // device is wrong category with non-empty problem text
            // record already has a fault_type which must not change
            $devs_ex[$i++] = $this->_insert_mobifix_device($cat, $id, Str::random(40), 'foo');
        }
        $devs = array_keys($devs_in);
        // iddevices = 1 : 3 opinions with consensus : recat
        Mobifix::factory()->count(3)->create(
                [
                    'iddevices' => $devs[0],
                    'fault_type' => 'fault_type_1',
                ]
        );
        // iddevices = 2 : 3 opinions with majority : recat
        Mobifix::factory()->count(2)->create(
                [
                    'iddevices' => $devs[1],
                    'fault_type' => 'fault_type_1',
                ]
        );
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[1],
                    'fault_type' => 'fault_type_2',
                ]
        );
        // iddevices = 3 : 3 opinions split
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[2],
                    'fault_type' => 'fault_type_1',
                ]
        );
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[2],
                    'fault_type' => 'fault_type_2',
                ]
        );
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[2],
                    'fault_type' => 'fault_type_3',
                ]
        );
        // iddevices = 4 : 3 opinions adjudicated : recat
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[3],
                    'fault_type' => 'fault_type_1',
                ]
        );
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[3],
                    'fault_type' => 'fault_type_2',
                ]
        );
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[3],
                    'fault_type' => 'fault_type_3',
                ]
        );
        DB::update('INSERT INTO devices_faults_mobiles_adjudicated SET iddevices = '.$devs[3].", fault_type='fault_type_1'");

        // iddevices = 5 : 2 opinions with majority : recat
        Mobifix::factory()->count(2)->create(
                [
                    'iddevices' => $devs[4],
                    'fault_type' => 'fault_type_1',
                ]
        );
        // iddevices = 6 : 2 opinions split
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[5],
                    'fault_type' => 'fault_type_1',
                ]
        );
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[5],
                    'fault_type' => 'fault_type_2',
                ]
        );
        // iddevices = 7 : 1 opinion
        Mobifix::factory()->count(1)->create(
                [
                    'iddevices' => $devs[6],
                    'fault_type' => 'fault_type_1',
                ]
        );

        $status = [
            'total_devices' => 8,
            'total_opinions_3' => 4,
            'total_opinions_2' => 2,
            'total_opinions_1' => 1,
            'total_opinions_0' => 1,
            'total_recats' => 4,
            'list_recats' => [
                0 => [
                    'winning_opinion' => 'fault_type_1',
                    'total' => 4,
                ],
            ],
            'total_splits' => 1,
            'list_splits' => [
                0 => [
                    'iddevices' => 3,
                    'all_crowd_opinions_count' => 3,
                    'opinions' => 'fault_type_1,fault_type_2,fault_type_3',
                ],
            ],
        ];

        return [
            'categories_include' => $cats_in,
            'categories_exclude' => $cats_ex,
            'devices_include' => $devs_in,
            'devices_exclude' => $devs_ex,
            'status' => $status,
        ];
    }

    protected function _insert_mobifix_device($cat, $id, $problem, $fault_type = '')
    {
        $device = Device::factory()->count(1)->cat()->create(
                [
                    'problem' => $problem,
                    'fault_type' => $fault_type,
                    'repair_status' => 1,
                ]
        );
        $this->assertDatabaseHas('devices', [
            'category' => $id,
            'problem' => $problem,
            'fault_type' => $fault_type,
            'repair_status' => 1,
        ]);

        return $device->toArray()[0];
    }
}
