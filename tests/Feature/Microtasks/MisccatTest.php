<?php

namespace Tests\Feature;

use App\Category;
use App\Device;
use App\Group;
use App\Misccat;
use DB;
use Tests\TestCase;

class MisccatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        Group::truncate();
        Device::truncate();
        Category::truncate();
        Misccat::truncate();
        DB::table('devices_misc_adjudicated')->truncate();
    }

    /** @test */
    public function fetch_misccat_record()
    {
        $devices = 5;
        $this->_insert_misccat_devices($devices);

        $iddevices = array_fill(1, $devices, 0);
        $Misccat = new Misccat;
        for ($i = 0; $i < 100; $i++) {
            $result = $Misccat->fetchMisc();
            $this->assertTrue(is_array($result));
            $this->assertEquals(count($result), 1);
            $this->assertGreaterThan(0, $result[0]->iddevices);
            $iddevices[$result[0]->iddevices]++;
        }
        for ($i = 1; $i < $devices; $i++) {
            $this->assertGreaterThan(0, $iddevices[$i], 'fetch_misccat_record: 0 fetched for iddevices='.$i);
        }

        $misccat = Misccat::factory()->count(3)->misc()->create([
            'iddevices' => 3,
        ]);

        $iddevices = array_fill(1, $devices, 0);
        $Misccat = new Misccat;
        for ($i = 0; $i < 100; $i++) {
            $result = $Misccat->fetchMisc();
            $this->assertTrue(is_array($result));
            $this->assertEquals(count($result), 1);
            $this->assertGreaterThan(0, $result[0]->iddevices);
            $iddevices[$result[0]->iddevices]++;
        }
        $this->assertEquals($iddevices[3], 0, 'fetch_misccat_record: records fetched for iddevices=3');
    }

    protected function _insert_misccat_devices($num)
    {
        $device = Device::factory()->count($num)->misccat()->create();
        for ($i = 1; $i <= $num; $i++) {
            $this->assertDatabaseHas('devices', [
                'iddevices' => $i,
                'category' => 46,
                'category_creation' => 46,
            ]);
        }
    }

    /** @test */
    public function fetch_misccat_status()
    {
        $this->_setup_data();

        $Misccat = new Misccat;
        $result = $Misccat->fetchStatus();

        $this->assertEquals(3, count($result['list_recats']), 'fetch_misccat_status: wrong count for list_recats');

        $this->assertEquals(1, $result['list_recats'][0]->items, 'fetch_misccat_status: wrong total for list_recats[0]');
        $this->assertEquals('Cat1', $result['list_recats'][0]->top_opinion, 'fetch_misccat_status: wrong top_opinion for list_recats[0]');

        $this->assertEquals(1, $result['list_recats'][1]->items, 'fetch_misccat_status: wrong total for list_recats[1]');
        $this->assertEquals('Cat2', $result['list_recats'][1]->top_opinion, 'fetch_misccat_status: wrong top_opinion for list_recats[1]');

        $this->assertEquals(1, $result['list_recats'][2]->items, 'fetch_misccat_status: wrong total for list_recats[2]');
        $this->assertEquals('Mobile', $result['list_recats'][2]->top_opinion, 'fetch_misccat_status: wrong top_opinion for list_recats[2]');

        $this->assertEquals(1, count($result['list_splits']), 'fetch_misccat_status: wrong count for list_splits');

        $this->assertEquals(6, $result['list_splits'][0]->iddevices, 'fetch_misccat_status: wrong iddevices for list_splits[0]');
        $this->assertEquals('Cat1,Cat2,Cat3', $result['list_splits'][0]->opinions, 'fetch_misccat_status: wrong opinions for list_splits[0]');
    }

    /** @test */
    public function update_misccat_devices()
    {
        $this->_setup_data();

        $Misccat = new Misccat;
        $result = $Misccat->updateDevices();
        logger(print_r($result, 1));
        $this->assertDatabaseHas('devices', [
            'problem' => 'category should be Cat1',
            'category' => 111,
            'category_creation' => 46,
            'repair_status' => 1,
        ]);
        $this->assertDatabaseHas('devices', [
            'problem' => 'category should be Cat2',
            'category' => 222,
            'category_creation' => 46,
            'repair_status' => 1,
        ]);
    }

    protected function _setup_data()
    {
        Category::factory()->count(1)->misc()->create();
        Category::factory()->count(1)->mobile()->create();
        Category::factory()->count(1)->cat1()->create();
        Category::factory()->count(1)->cat2()->create();
        Category::factory()->count(1)->cat3()->create();

        $data = $this->_get_setup_data();
        foreach ($data as $elems) {
            foreach ($elems as $tablename => $records) {
                foreach ($records as $record) {
                    if ($tablename == 'devices') {
                        Device::factory()->count(1)->create($record);
                    } elseif ($tablename == 'devices_misc_opinions') {
                        Misccat::factory()->count(1)->create($record);
                    } elseif ($tablename == 'devices_misc_adjudicated') {
                        DB::update('INSERT INTO devices_misc_adjudicated SET iddevices = '.$record['iddevices'].", category = '".$record['category']."'");
                    }
                    $this->assertDatabaseHas($tablename, $record);
                }
            }
        }
    }

    protected function _get_setup_data()
    {
        $iddevices = 0;
        $result = [];

        $result['-2'] = [
            'devices' => [
                [
                    'iddevices' => ++$iddevices,
                    'category' => 25,
                    'category_creation' => 25,
                    'repair_status' => 1,
                ],
            ],
            'devices_misc_opinions' => [],
            'devices_misc_adjudicated' => [],
        ];

        //WHEN -1 THEN 'Category has been updated from Misc'
        // 1 device record = "Mobile", 2 misccat records = "Mobile"

        $result['-1'] = [
            'devices' => [
                [
                    'iddevices' => ++$iddevices,
                    'category' => 25,
                    'category_creation' => 46,
                    'repair_status' => 1,
                ],
            ],
            'devices_misc_opinions' => [
                [
                    'iddevices' => $iddevices,
                    'category' => 'Mobile',
                ],
                [
                    'iddevices' => $iddevices,
                    'category' => 'Mobile',
                ],
            ],
            'devices_misc_adjudicated' => [],
        ];

        //WHEN 0 THEN 'Is Misc and has no opinions'
        // 1 device record = "Misc", 0 misccat records
        $result['0'] = [
            'devices' => [
                [
                    'iddevices' => ++$iddevices,
                    'category' => 46,
                    'category_creation' => 46,
                    'repair_status' => 1,
                ],
            ],
            'devices_misc_opinions' => [],
            'devices_misc_adjudicated' => [],
        ];

        //WHEN 1 THEN 'Is Misc and has only one opinion'
        // 1 device record = "Misc", 1 misccat record = "Cat1"
        $result['1'] = [
            'devices' => [
                [
                    'iddevices' => ++$iddevices,
                    'category' => 46,
                    'category_creation' => 46,
                    'repair_status' => 1,
                ],
            ],
            'devices_misc_opinions' => [
                [
                    'iddevices' => $iddevices,
                    'category' => 'Cat1',
                ],
            ],
            'devices_misc_adjudicated' => [],
        ];

        //WHEN 2 THEN 'Is Misc and needs one more opinion'
        // 1 device record = "Misc", 2 misccat records = "Cat1"/"Cat2"
        $result['2'] = [
            'devices' => [
                [
                    'iddevices' => ++$iddevices,
                    'category' => 46,
                    'category_creation' => 46,
                    'repair_status' => 1,
                ],
            ],
            'devices_misc_opinions' => [
                [
                    'iddevices' => $iddevices,
                    'category' => 'Cat1',
                ],
                [
                    'iddevices' => $iddevices,
                    'category' => 'Cat2',
                ],
            ],
            'devices_misc_adjudicated' => [],
        ];

        //WHEN 3 THEN 'Is Misc and opinions are split, adjudication needed'
        // 1 device record = "Misc", 3 misccat records = "Cat1"/"Cat2"/"Cat3"
        $result['3'] = [
            'devices' => [
                [
                    'iddevices' => ++$iddevices,
                    'category' => 46,
                    'category_creation' => 46,
                    'repair_status' => 1,
                ],
            ],
            'devices_misc_opinions' => [
                [
                    'iddevices' => $iddevices,
                    'category' => 'Cat1',
                ],
                [
                    'iddevices' => $iddevices,
                    'category' => 'Cat2',
                ],
                [
                    'iddevices' => $iddevices,
                    'category' => 'Cat3',
                ],
            ],
            'devices_misc_adjudicated' => [],
        ];

        //WHEN 4 THEN 'Is Misc and majority opinions agree it should remain as Misc'
        // 3 device records in total
        // 1 device record = "Misc", 3 misccat records = "Misc"
        // 1 device record = "Misc", 2 misccat records = "Misc", 1 misccat records = "Cat1"
        // 1 device record = "Misc", 3 misccat records = "Cat1"/"Cat2"/"Misc", 1 adjudication record = "Misc"
        $result['4']['devices'] = [];
        $result['4']['devices_misc_opinions'] = [];
        $result['4']['devices_misc_adjudicated'] = [];

        $result['4']['devices'][] = [
            'iddevices' => ++$iddevices,
            'category' => 46,
            'category_creation' => 46,
            'repair_status' => 1,
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Misc',
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Misc',
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Misc',
        ];

        $result['4']['devices'][] = [
            'iddevices' => ++$iddevices,
            'category' => 46,
            'category_creation' => 46,
            'repair_status' => 1,
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Misc',
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Misc',
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat1',
        ];

        $result['4']['devices'][] = [
            'iddevices' => ++$iddevices,
            'category' => 46,
            'category_creation' => 46,
            'repair_status' => 1,
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Misc',
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat1',
        ];
        $result['4']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat2',
        ];
        $result['4']['devices_misc_adjudicated'][] = [
            'iddevices' => $iddevices,
            'category' => 'Misc',
        ];

        //WHEN 5 THEN 'Is Misc and majority opinions say not Misc so it will be updated'
        // 2 device records in total
        // 1 device record = "Misc", 3 misccat records = "Cat1"
        // 1 device record = "Misc", 3 misccat records = "Cat1"/"Cat2"/"Misc", 1 adjudication record = "Cat1"
        $result['5']['devices'] = [];
        $result['5']['devices_misc_opinions'] = [];
        $result['5']['devices_misc_adjudicated'] = [];

        $result['5']['devices'][] = [
            'iddevices' => ++$iddevices,
            'category' => 46,
            'category_creation' => 46,
            'repair_status' => 1,
            'problem' => 'category should be Cat1',
        ];
        $result['5']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat1',
        ];
        $result['5']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat1',
        ];
        $result['5']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat1',
        ];

        $result['5']['devices'][] = [
            'iddevices' => ++$iddevices,
            'category' => 46,
            'category_creation' => 46,
            'repair_status' => 1,
            'problem' => 'category should be Cat2',
        ];
        $result['5']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Misc',
        ];
        $result['5']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat1',
        ];
        $result['5']['devices_misc_opinions'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat2',
        ];
        $result['5']['devices_misc_adjudicated'][] = [
            'iddevices' => $iddevices,
            'category' => 'Cat2',
        ];

        return $result;
    }
}
