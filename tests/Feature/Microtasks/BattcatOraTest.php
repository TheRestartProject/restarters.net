<?php

namespace Tests\Feature;

use App\BattcatOra;
use DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BattcatOraTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        BattcatOra::truncate();
        DB::table('devices_battcat_ora')->truncate();
        DB::table('devices_faults_batteries_ora_adjudicated')->truncate();
        DB::table('fault_types_batteries')->truncate();
    }

    /** @test */
    public function fetch_battcatora_record()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $BattcatOra = new BattcatOra;

        // insert 8 records, no exclusions
        // expects any record
        $result = $BattcatOra->fetchFault();
        $this->assertTrue(is_array($result), 'fetch_battcatora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_battcatora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_battcatora_record: id_ords is null');

        // use locale, no exclusions
        // expects a record with language matching locale
        // except for invalid language where any record returned
        $exclude = [];
        $locales = ['de', 'nl', 'en', 'foo'];
        foreach ($locales as $locale) {
            $result = $BattcatOra->fetchFault($exclude, $locale);
            $this->assertTrue(is_array($result), 'fetch_battcatora_record: result is not array');
            $this->assertEquals(1, count($result), 'fetch_battcatora_record: wrong result count');
            $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_battcatora_record: id_ords is null');
            if ($locale !== 'foo') {
                $this->assertEquals($locale, $result[0]->language, 'fetch_battcatora_record: wrong language');
            }
        }

        // exclude 8 records
        // expects no records returned
        foreach ($data as $v) {
            $exclude[] = $v['id_ords'];
        }
        $result = $BattcatOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_battcatora_record: result is not array');
        $this->assertEquals(0, count($result), 'fetch_battcatora_record: wrong result count');

        // exclude 7 records
        // expects only the leftover/included record
        $include = array_pop($exclude);
        $result = $BattcatOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_battcatora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_battcatora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_battcatora_record: id_ords is null');
        $this->assertEquals($include, $result[0]->id_ords, 'fetch_battcatora_record: wrong value');

        // use locale, exclude 7 records
        // expects the same record regardless of locale
        foreach ($locales as $locale) {
            $result = $BattcatOra->fetchFault($exclude, $locale);
            $this->assertTrue(is_array($result), 'fetch_battcatora_record: result is not array');
            $this->assertEquals(1, count($result), 'fetch_battcatora_record: wrong result count');
            $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_battcatora_record: id_ords is null');
            $this->assertEquals($include, $result[0]->id_ords, 'fetch_battcatora_record: wrong value');
        }

        // no exclusions, opinions exist
        // expects 1 of the 3 records with not enough opinions
        $exclude = [];
        $locale = '';
        $opinions = $this->_setup_opinions($data);
        $expect = $opinions['not_enough_opinions'];
        $result = $BattcatOra->fetchFault($exclude, $locale);
        $this->assertTrue(is_array($result), 'fetch_battcatora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_battcatora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_battcatora_record: id_ords is null');
        $this->assertTrue(in_array($result[0]->id_ords, $expect), 'fetch_battcatora_record: invalid id_ords');
    }

    /** @test */
    public function battcatora_should_redirect_to_status_page()
    {
        // BattCat is closed - should redirect to status page.
        $response = $this->get('/battcat');
        $response->assertRedirect(url()->current().'/status');
        $response = $this->get('/battcat/status');
        $response->assertStatus(200);
        $response->assertSee('BattCat Status');
    }

    /** @test */
    public function fetch_battcatora_status()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $BattcatOra = new BattcatOra;
        $result = $BattcatOra->fetchStatus();
        $this->assertTrue(is_array($result), 'fetch_battcatora_status: result is not array');
        foreach ($opinions['status'] as $k => $v) {
            $this->assertTrue(isset($result, $k), 'fetch_battcatora_status: missing key - '.$k);
            if ($k == 'list_recats') {
                $this->assertTrue(is_array($result[$k]), 'fetch_battcatora_status: not array - '.$k);
                foreach ($v[0] as $key => $val) {
                    $this->assertTrue(property_exists($result[$k][0], $key), 'fetch_battcatora_status #'.$k.': missing key - '.$key);
                    $this->assertEquals($val, $result[$k][0]->{$key}, 'fetch_battcatora_status #'.$k.': wrong '.$key);
                }
            } else {
                $this->assertEquals(1, count($result[$k]), 'fetch_battcatora_status: wrong array count '.$k);
                $this->assertTrue(is_object($result[$k][0]), 'fetch_battcatora_status: not object '.$k);
                $this->assertTrue(property_exists($result[$k][0], 'total'), 'fetch_battcatora_status #'.$k.': missing key - total');
                $this->assertEquals($v, $result[$k][0]->total, 'fetch_battcatora_status: wrong total for '.$k);
            }
        }
    }

    /** @test */
    public function update_battcatora_devices()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $BattcatOra = new BattcatOra;
        $before = DB::select('SELECT id_ords, fault_type_id FROM devices_battcat_ora');
        foreach ($before as $k => $v) {
            $this->assertEquals($v->fault_type_id, 0, 'update_battcatora_devices: initial fault_type not 0: '.$v->fault_type_id);
        }
        $updated = $BattcatOra->updateDevices();
        $after = DB::select('SELECT id_ords, fault_type_id FROM devices_battcat_ora');
        $this->assertEquals($updated, count($opinions['updates']), 'update_battcatora_devices: wrong number of records updated: '.$updated);
        foreach ($after as $k => $v) {
            if (isset($opinions['updates'][$v->id_ords])) {
                $this->assertEquals($v->fault_type_id, $opinions['updates'][$v->id_ords], 'update_battcatora_devices: updated fault_type is wrong: '.$v->id_ords.' => '.$v->fault_type_id);
            } else {
                $this->assertEquals($v->fault_type_id, 0, 'update_battcatora_devices: fault_type should still be 0: '.$v->fault_type_id);
            }
        }
    }

    protected function _setup_devices()
    {
        $data = [
            [
                'id_ords' => 'anstiftung_502',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Handy / Smartphone / Tablet ~ Tablet',
                'product_category' => 'Tablet',
                'brand' => 'Samsung',
                'year_of_manufacture' => '2015',
                'repair_status' => 'Repairable',
                'event_date' => '2018-11-28',
                'problem' => 'Akku defekt',
                'language' => 'de',
                'translation' => 'Defective battery',
                'fault_type_id' => '0',
                'fault_type' => '',
            ],
            [
                'id_ords' => 'rcint_7168',
                'data_provider' => 'Repair Café International',
                'country' => 'NLD',
                'partner_product_category' => 'Household appliances electric ~ Lamp/lighting',
                'product_category' => 'Lamp',
                'brand' => 'MultiLight',
                'year_of_manufacture' => '2011',
                'repair_status' => 'Repairable',
                'event_date' => '2018-05-19',
                'problem' => 'Accu vermoeid',
                'language' => 'nl',
                'translation' => 'battery exhausted',
                'fault_type_id' => '0',
                'fault_type' => '',
            ],
            [
                'id_ords' => 'restart_10452',
                'data_provider' => 'The Restart Project',
                'country' => 'NOR',
                'partner_product_category' => 'Tablet',
                'product_category' => 'Tablet',
                'brand' => 'Samsung',
                'year_of_manufacture' => '2013',
                'repair_status' => 'Repairable',
                'event_date' => '2018-11-21',
                'problem' => 'Won\'t charge',
                'language' => 'en',
                'translation' => 'Won\'t charge',
                'fault_type_id' => '0',
                'fault_type' => '',
            ],
            [
                'id_ords' => 'restart_11931',
                'data_provider' => 'The Restart Project',
                'country' => 'GB',
                'partner_product_category' => 'Laptop small',
                'product_category' => 'Laptop',
                'brand' => 'HP',
                'year_of_manufacture' => '2012',
                'repair_status' => 'Repairable',
                'event_date' => '2019-03-09',
                'problem' => 'battery dead',
                'language' => 'en',
                'translation' => 'battery dead',
                'fault_type_id' => '0',
                'fault_type' => '',
            ],
            [
                'id_ords' => 'anstiftung_5424',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Computer ~ Laptop',
                'product_category' => 'Laptop',
                'brand' => 'Unknown',
                'year_of_manufacture' => '????',
                'repair_status' => 'End of life',
                'event_date' => '2020-07-05',
                'problem' => 'akku kaput',
                'language' => 'de',
                'translation' => 'battery kaput',
                'fault_type_id' => '0',
                'fault_type' => '',
            ],
            [
                'id_ords' => 'rcint_3689',
                'data_provider' => 'Repair Café International',
                'country' => 'NLD',
                'partner_product_category' => 'Computers/phones ~ Laptop',
                'product_category' => 'Laptop',
                'brand' => 'Siemens',
                'year_of_manufacture' => '2007',
                'repair_status' => 'End of life',
                'event_date' => '2017-12-02',
                'problem' => 'Batterij defect',
                'language' => 'nl',
                'translation' => 'battery failure',
                'fault_type_id' => '0',
                'fault_type' => '',
            ],
            [
                'id_ords' => 'restart_17002',
                'data_provider' => 'The Restart Project',
                'country' => 'GB',
                'partner_product_category' => 'Vacuum',
                'product_category' => 'Vacuum',
                'brand' => 'Ilife',
                'year_of_manufacture' => '2019',
                'repair_status' => 'End of life',
                'event_date' => '2020-02-08',
                'problem' => 'Charger not working',
                'language' => 'en',
                'translation' => 'Charger not working',
                'fault_type_id' => '0',
                'fault_type' => '',
            ],
            [
                'id_ords' => 'restart_9918',
                'data_provider' => 'The Restart Project',
                'country' => 'GB',
                'partner_product_category' => 'Mobile',
                'product_category' => 'Mobile',
                'brand' => 'Samsung',
                'year_of_manufacture' => '2012',
                'repair_status' => 'End of life',
                'event_date' => '2018-10-27',
                'problem' => 'Doesn\'t charge',
                'language' => 'en',
                'translation' => 'Doesn\'t charge',
                'fault_type_id' => '0',
                'fault_type' => '',
            ],
        ];

        foreach ($data as $k => $v) {
            DB::table('devices_battcat_ora')->insert([
                'id_ords' => $v['id_ords'],
                'data_provider' => $v['data_provider'],
                'country' => $v['country'],
                'partner_product_category' => $v['partner_product_category'],
                'product_category' => $v['product_category'],
                'brand' => $v['brand'],
                'year_of_manufacture' => $v['year_of_manufacture'],
                'repair_status' => $v['repair_status'],
                'event_date' => $v['event_date'],
                'problem' => $v['problem'],
                'language' => $v['language'],
                'translation' => $v['translation'],
                'fault_type_id' => $v['fault_type_id'],
                'fault_type' => $v['fault_type'],
            ]);
            $this->assertDatabaseHas('devices_battcat_ora', [
                'id_ords' => $v['id_ords'],
            ]);
        }

        return $data;
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
    protected function _setup_opinions($data)
    {
        $opinions = [];
        $updates = [];

        // $data[0] : 3 rep opinions with consensus : recat
        $opinions[$data[0]['id_ords']][] = $this->_insert_opinion($data[0]['id_ords'], 4);
        $opinions[$data[0]['id_ords']][] = $this->_insert_opinion($data[0]['id_ords'], 4);
        $opinions[$data[0]['id_ords']][] = $this->_insert_opinion($data[0]['id_ords'], 4);
        $updates[$data[0]['id_ords']] = 4;

        // $data[1] : 3 rep opinions with majority : recat
        $opinions[$data[1]['id_ords']][] = $this->_insert_opinion($data[1]['id_ords'], 4);
        $opinions[$data[1]['id_ords']][] = $this->_insert_opinion($data[1]['id_ords'], 4);
        $opinions[$data[1]['id_ords']][] = $this->_insert_opinion($data[1]['id_ords'], 7);
        $updates[$data[1]['id_ords']] = 4;

        // $data[2] : 3 rep opinions split
        $opinions[$data[2]['id_ords']][] = $this->_insert_opinion($data[2]['id_ords'], 4);
        $opinions[$data[2]['id_ords']][] = $this->_insert_opinion($data[2]['id_ords'], 7);
        $opinions[$data[2]['id_ords']][] = $this->_insert_opinion($data[2]['id_ords'], 8);

        // $data[3] : 3 rep opinions adjudicated : recat
        $opinions[$data[3]['id_ords']][] = $this->_insert_opinion($data[3]['id_ords'], 4);
        $opinions[$data[3]['id_ords']][] = $this->_insert_opinion($data[3]['id_ords'], 7);
        $opinions[$data[3]['id_ords']][] = $this->_insert_opinion($data[3]['id_ords'], 8);
        DB::update("INSERT INTO devices_faults_batteries_ora_adjudicated SET id_ords = '".$data[3]['id_ords']."', fault_type_id=4");
        $updates[$data[3]['id_ords']] = 4;

        // $data[4] : 2 eol opinions with majority : recat
        $opinions[$data[4]['id_ords']][] = $this->_insert_opinion($data[4]['id_ords'], 9);
        $opinions[$data[4]['id_ords']][] = $this->_insert_opinion($data[4]['id_ords'], 9);
        $updates[$data[4]['id_ords']] = 9;

        // $data[5] : 2 eol opinions split
        $opinions[$data[5]['id_ords']][] = $this->_insert_opinion($data[5]['id_ords'], 9);
        $opinions[$data[5]['id_ords']][] = $this->_insert_opinion($data[5]['id_ords'], 17);

        // $data[6] : 1 eol opinion
        $opinions[$data[6]['id_ords']][] = $this->_insert_opinion($data[6]['id_ords'], 18);

        // $data[7] : no opinions

        $not_enough_opinions = [
            $data[5]['id_ords'],
            $data[6]['id_ords'],
            $data[7]['id_ords'],
        ];

        /*
        % progress towards completion
        total number of opinions given (this replaces the previous quest's ‘Items / opinions’ section
        a table of items with majority opinions for each of end-of-life and repairable statuses
        */
        $status = [
            'total_devices' => 8,
            'total_opinions' => 17,
            'total_recats' => 4,
            'list_recats' => [
                0 => [
                    'winning_opinion' => 'Replace with new battery',
                    'total' => 3,
                ],
                1 => [
                    'winning_opinion' => 'Battery not readily available',
                    'total' => 1,
                ],
            ],
            'progress' => 50,
        ];

        return [
            'status' => $status,
            'opinions' => $opinions,
            'updates' => $updates,
            'not_enough_opinions' => $not_enough_opinions,
        ];
    }

    protected function _insert_opinion($id_ords, $fault_type_id)
    {
        $insert = [
            'id_ords' => $id_ords,
            'fault_type_id' => $fault_type_id,
        ];
        DB::table('devices_faults_batteries_ora_opinions')->insert($insert);
        $this->assertDatabaseHas('devices_faults_batteries_ora_opinions', [
            'id_ords' => $id_ords,
        ]);

        return $insert;
    }

    protected function _bypass_cta()
    {
        $this->app['session']->put('microtask.cta', 1);
        $this->app['session']->put('microtask.clicks', 5);
        $this->app['session']->put('microtask.sesh', \Carbon\Carbon::now()->timestamp);
    }

    protected function _setup_fault_types()
    {
        $fault_types = [
            ['id' => '1', 'title' => 'Clean battery contacts', 'description' => '', 'repair_status' => 'Repairable'],
            ['id' => '2', 'title' => 'Fix connectors or casing', 'description' => '', 'repair_status' => 'Repairable'],
            ['id' => '3', 'title' => 'Fix the charging port', 'description' => '', 'repair_status' => 'Repairable'],
            ['id' => '4', 'title' => 'Replace with new battery', 'description' => '', 'repair_status' => 'Repairable'],
            ['id' => '5', 'title' => 'Replace the charger or charging cable', 'description' => '', 'repair_status' => 'Repairable'],
            ['id' => '6', 'title' => 'Battery is not the main problem', 'description' => '', 'repair_status' => 'Repairable'],
            ['id' => '7', 'title' => 'Other', 'description' => '', 'repair_status' => 'Repairable'],
            ['id' => '8', 'title' => 'Poor data', 'description' => '', 'repair_status' => 'Repairable'],
            ['id' => '9', 'title' => 'Battery not readily available', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '10', 'title' => 'Built-in or soldered battery, cannot remove', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '11', 'title' => 'Charger not readily available', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '12', 'title' => 'Damaged while replacing battery', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '13', 'title' => 'Difficult to remove battery', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '14', 'title' => 'Irrepairable corrosion, leakage, bulging', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '15', 'title' => 'New battery too expensive', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '16', 'title' => 'Battery is not the main problem', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '17', 'title' => 'Other', 'description' => '', 'repair_status' => 'End of life'],
            ['id' => '18', 'title' => 'Poor data', 'description' => '', 'repair_status' => 'End of life'],
        ];

        foreach ($fault_types as $row) {
            DB::table('fault_types_batteries')->insert($row);
            $this->assertDatabaseHas('fault_types_batteries', ['id' => $row['id']]);
        }

        return $fault_types;
    }
}
