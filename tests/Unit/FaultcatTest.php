<?php

namespace Tests\Unit;

use App\Device;
use App\Category;
use App\Faultcat;
use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FaultcatTest extends TestCase {

    use RefreshDatabase;

    public function setUp() {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        Faultcat::truncate();
    }

    /** @test */
    public function fetch_faultcat_record() {

        $data = $this->_setup_data();
        $Faultcat = new Faultcat;

        for ($i = 0; $i < 101; $i++) {
            $result = $Faultcat->fetchFault();
            $this->assertTrue(is_array($result), 'fetch_faultcat_record: result is not array');
            $this->assertEquals(count($result), 1, 'fetch_faultcat_record: wrong result count');
            $this->assertGreaterThan(0, $result[0]->iddevices, 'fetch_faultcat_record: iddevices is 0 or null');
            $this->assertTrue(array_key_exists($result[0]->iddevices, $data['devices_include']), 'fetch_faultcat_record: result is not in list of inclusions');
            $this->assertFalse(array_key_exists($result[0]->iddevices, $data['devices_exclude']), 'fetch_faultcat_record: result is in list of exclusions');
        }
    }

    /** @test */
    public function fetch_faultcat_status() {

        $data = $this->_setup_data();

        $Faultcat = new Faultcat;
        $result = $Faultcat->fetchStatus();
//        logger(print_r($result, 1));
        $this->assertTrue(is_array($result));
        foreach ($data['status'] as $k => $v) {
            $this->assertTrue(array_key_exists($k, $result), 'fetch_faultcat_status: missing key - ' . $k);
            if (!is_array($v)) {
                $this->assertEquals($result[$k][0]->total, $v, 'fetch_faultcat_status: wrong ' . $k);
            } else {
                $this->assertTrue(is_array($result[$k]), 'fetch_faultcat_status: not array - ' . $k);
                foreach ($v[0] as $key => $val) {
                    $this->assertTrue(array_key_exists($key, $result[$k][0]), 'fetch_faultcat_status: missing key - ' . $key);
                    $this->assertEquals($result[$k][0]->{$key}, $val, 'fetch_faultcat_status: wrong ' . $key);
                }
            }
        }
    }

    /** @test */
    public function update_faultcat_devices() {

        $this->_setup_data();

        $Faultcat = new Faultcat;
        $result = $Faultcat->updateDevices();
        $expected = array_fill(1, 15, '');
        $expected[1] = 'fault_type_1';
        $expected[2] = 'fault_type_1';
        $expected[5] = 'fault_type_1';
        $expected[6] = 'fault_type_1';
        $expected[9] = 'fault_type_1';
        $expected[13] = 'foo';
        $expected[17] = 'foo';
        $expected[21] = 'foo';
        $expected[22] = 'foo';
        foreach ($expected as $k => $v) {
            $this->assertDatabaseHas('devices', [
                'iddevices' => $k,
                'fault_type' => $v,
            ]);
        }
    }

    /** @test */
    public function update_faultcat_empty_problem() {

        $this->_setup_data();

        $Faultcat = new Faultcat;
        $result = $Faultcat->updateDevicesWithEmptyProblem();
        $expected = array_fill(1, 15, '');
        $expected[1] = 'foo';
        $expected[5] = 'foo';
        $expected[9] = 'foo';
        $expected[13] = 'foo';
        $expected[17] = 'foo';
        $expected[21] = 'foo';
        $expected[22] = 'foo';
        $expected[4] = 'Unknown';
        $expected[8] = 'Unknown';
        $expected[12] = 'Unknown';
        $expected[16] = 'Unknown';
        $expected[20] = 'Unknown';
        foreach ($expected as $k => $v) {
            $this->assertDatabaseHas('devices', [
                'iddevices' => $k,
                'fault_type' => $v,
            ]);
        }
    }

    /**
     * Cases:
     *  opinions = 0 (5 records)
     *  opinions = 1 (1 record)
     *  opinions = 2 (1 record)
     *  opinions = 3 (2 records - 1 majority, 1 split)
     *  opinions = 4 (2 records - 1 majority, 1 split)
     *  opinions = 5 (4 records - 1 consensus, 1 majority, 1 split, 1 adjudicated)
     *
     * @return array
     */
    protected function _setup_data() {
        $cats_in = [
            'desktop' => 11,
            'laptop large' => 15,
            'laptop medium' => 16,
            'laptop small' => 17,
            'tablet' => 26,
        ];
        $cats_ex = [
            'mobile' => 25,
            'misc' => 46,
        ];
        $devs_in = [];
        $devs_ex = [];
        $i = 1;
        foreach ($cats_in as $cat => $id) {
            // 3 devices in right category with non-empty problem text
            // first has been previously assigned a fault_type which may be changed
            $devs_in[$i++] = $this->_insert_faultcat_device($cat, $id, Str::random(40), 'foo');
            $devs_in[$i++] = $this->_insert_faultcat_device($cat, $id, Str::random(40));
            $devs_in[$i++] = $this->_insert_faultcat_device($cat, $id, Str::random(40));
            // device is right category but empty problem text
            $devs_ex[$i++] = $this->_insert_faultcat_device($cat, $id, '');
        }
        foreach ($cats_ex as $cat => $id) {
            // device is wrong category with non-empty problem text
            // record already has a fault_type which must not change
            $devs_ex[$i++] = $this->_insert_faultcat_device($cat, $id, Str::random(40), 'foo');
        }

//        logger(print_r($devs_in, 1));
        $devs = array_keys($devs_in);
//        logger(print_r($devs, 1));
        // iddevices = 1 : 5 opinions with consensus
        factory(Faultcat::class, 5)->create(
                [
                    'iddevices' => $devs[0],
                    'fault_type' => 'fault_type_1',
                ]
        );
        // iddevices = 2 : 5 opinions with majority
        factory(Faultcat::class, 4)->create(
                [
                    'iddevices' => $devs[1],
                    'fault_type' => 'fault_type_1',
                ]
        );
        factory(Faultcat::class, 1)->create(
                [
                    'iddevices' => $devs[1],
                    'fault_type' => 'fault_type_2',
                ]
        );
        // iddevices = 3 : 5 opinions split
        factory(Faultcat::class, 2)->create(
                [
                    'iddevices' => $devs[2],
                    'fault_type' => 'fault_type_1',
                ]
        );
        factory(Faultcat::class, 2)->create(
                [
                    'iddevices' => $devs[2],
                    'fault_type' => 'fault_type_2',
                ]
        );
        factory(Faultcat::class, 1)->create(
                [
                    'iddevices' => $devs[2],
                    'fault_type' => 'fault_type_3',
                ]
        );
        // iddevices = 5 : 5 opinions adjudicated
        factory(Faultcat::class, 2)->create(
                [
                    'iddevices' => $devs[3],
                    'fault_type' => 'fault_type_1',
                ]
        );
        factory(Faultcat::class, 2)->create(
                [
                    'iddevices' => $devs[3],
                    'fault_type' => 'fault_type_2',
                ]
        );
        factory(Faultcat::class, 1)->create(
                [
                    'iddevices' => $devs[3],
                    'fault_type' => 'fault_type_3',
                ]
        );
        DB::update("INSERT INTO devices_faults_adjudicated SET iddevices = " . $devs[3] . ", fault_type='fault_type_1'");
        // iddevices = 6 : 4 opinions with majority
        factory(Faultcat::class, 3)->create(
                [
                    'iddevices' => $devs[4],
                    'fault_type' => 'fault_type_1',
                ]
        );
        factory(Faultcat::class, 1)->create(
                [
                    'iddevices' => $devs[4],
                    'fault_type' => 'fault_type_2',
                ]
        );
        // iddevices = 7 : 4 opinions split
        factory(Faultcat::class, 2)->create(
                [
                    'iddevices' => $devs[5],
                    'fault_type' => 'fault_type_1',
                ]
        );
        factory(Faultcat::class, 2)->create(
                [
                    'iddevices' => $devs[5],
                    'fault_type' => 'fault_type_2',
                ]
        );
        // iddevices = 9 : 3 opinions with majority
        factory(Faultcat::class, 3)->create(
                [
                    'iddevices' => $devs[6],
                    'fault_type' => 'fault_type_1',
                ]
        );
        // iddevices = 10 : 3 opinions split
        factory(Faultcat::class, 2)->create(
                [
                    'iddevices' => $devs[7],
                    'fault_type' => 'fault_type_1',
                ]
        );
        factory(Faultcat::class, 1)->create(
                [
                    'iddevices' => $devs[7],
                    'fault_type' => 'fault_type_2',
                ]
        );
        // iddevices = 11 : 2 opinions
        factory(Faultcat::class, 2)->create(
                [
                    'iddevices' => $devs[8],
                    'fault_type' => 'fault_type_1',
                ]
        );
        // iddevices = 13 : 1 opinion
        factory(Faultcat::class, 1)->create(
                [
                    'iddevices' => $devs[9],
                    'fault_type' => 'fault_type_1',
                ]
        );

        $status = [
            'total_devices' => 15,
            'total_opinions_5' => 4,
            'total_opinions_4' => 2,
            'total_opinions_3' => 2,
            'total_opinions_2' => 1,
            'total_opinions_1' => 1,
            'total_opinions_0' => 5,
            'total_recats' => 5,
            'list_recats' => [
                0 => [
                    'winning_opinion' => 'fault_type_1',
                    'total' => 5,
                ],
            ],
            'total_splits' => 1,
            'list_splits' => [
                0 => [
                    'iddevices' => 3,
                    'top_crowd_opinion' => 'fault_type_1',
                    'top_crowd_opinion_percentage' => 40,
                    'all_crowd_opinions_count' => 5,
                    'opinions' => 'fault_type_1,fault_type_1,fault_type_2,fault_type_2,fault_type_3',
                ],
            ],
        ];
//        logger(print_r($status, 1));
        return [
            'categories_include' => $cats_in,
            'categories_exclude' => $cats_ex,
            'devices_include' => $devs_in,
            'devices_exclude' => $devs_ex,
            'status' => $status,
        ];
    }

    protected function _insert_faultcat_device($cat, $id, $problem, $fault_type = '') {
        $device = factory(Device::class, 1)->states($cat)->create(
                [
                    'problem' => $problem,
                    'fault_type' => $fault_type,
                ]
        );
        $this->assertDatabaseHas('devices', [
            'category' => $id,
            'problem' => $problem,
            'fault_type' => $fault_type,
        ]);
        return $device->toArray()[0];
    }

}
