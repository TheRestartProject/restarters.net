<?php

namespace Tests\Feature;

use App\TabicatOra;
use DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TabicatOraTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        TabicatOra::truncate();
        DB::table('devices_tabicat_ora')->truncate();
        DB::table('devices_faults_tablets_ora_adjudicated')->truncate();
        DB::table('fault_types_tablets')->truncate();
    }

    /** @test */
    public function fetch_tabicatora_record()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $TabicatOra = new TabicatOra;

        // insert 8 records, no exclusions
        // expects any record
        $result = $TabicatOra->fetchFault();
        $this->assertTrue(is_array($result), 'fetch_tabicatora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_tabicatora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_tabicatora_record: id_ords is null');

        // use locale, no exclusions
        // expects a record with language matching locale
        // except for invalid language where any record returned
        $exclude = [];
        $locales = ['de', 'nl', 'en', 'foo'];
        foreach ($locales as $locale) {
            $result = $TabicatOra->fetchFault($exclude, $locale);
            $this->assertTrue(is_array($result), 'fetch_tabicatora_record: result is not array');
            $this->assertEquals(1, count($result), 'fetch_tabicatora_record: wrong result count');
            $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_tabicatora_record: id_ords is null');
            if ($locale !== 'foo') {
                $this->assertEquals($locale, $result[0]->language, 'fetch_tabicatora_record: wrong language');
            }
        }

        // exclude 8 records
        // expects no records returned
        foreach ($data as $v) {
            $exclude[] = $v['id_ords'];
        }
        $result = $TabicatOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_tabicatora_record: result is not array');
        $this->assertEquals(0, count($result), 'fetch_tabicatora_record: wrong result count');

        // exclude 7 records
        // expects only the leftover/included record
        $include = array_pop($exclude);
        $result = $TabicatOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_tabicatora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_tabicatora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_tabicatora_record: id_ords is null');
        $this->assertEquals($include, $result[0]->id_ords, 'fetch_tabicatora_record: wrong value');

        // use locale, exclude 7 records
        // expects the same record regardless of locale
        foreach ($locales as $locale) {
            $result = $TabicatOra->fetchFault($exclude, $locale);
            $this->assertTrue(is_array($result), 'fetch_tabicatora_record: result is not array');
            $this->assertEquals(1, count($result), 'fetch_tabicatora_record: wrong result count');
            $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_tabicatora_record: id_ords is null');
            $this->assertEquals($include, $result[0]->id_ords, 'fetch_tabicatora_record: wrong value');
        }

        // no exclusions, opinions exist
        // expects 1 of the 3 records with not enough opinions
        $exclude = [];
        $opinions = $this->_setup_opinions($data);
        $expect = ['rcint_8243', 'rcint_9462', 'fixitclinic_141'];
        $this->assertTrue(is_array($result), 'fetch_tabicatora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_tabicatora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_tabicatora_record: id_ords is null');
        $this->assertTrue(in_array($result[0]->id_ords, $expect), 'fetch_tabicatora_record: invalid id_ords');
    }

    /** @test */
    public function tabicatora_should_redirect_to_status_page()
    {
        // TabiCat is closed - should redirect to status page.
        $response = $this->get('/tabicat');
        $response->assertRedirect(url()->current().'/status');
    }

    /** @test */
    public function fetch_tabicatora_status()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $TabicatOra = new TabicatOra;
        $result = $TabicatOra->fetchStatus();
        $this->assertTrue(is_array($result), 'fetch_tabicatora_status: result is not array');
        foreach ($opinions['status'] as $k => $v) {
            $this->assertTrue(isset($result, $k), 'fetch_tabicatora_status: missing key - '.$k);
            if ($k == 'list_recats' || $k == 'list_splits') {
                $this->assertTrue(is_array($result[$k]), 'fetch_tabicatora_status: not array - '.$k);
                foreach ($v[0] as $key => $val) {
                    $this->assertTrue(property_exists($result[$k][0], $key), 'fetch_tabicatora_status #'.$k.': missing key - '.$key);
                    $this->assertEquals($val, $result[$k][0]->{$key}, 'fetch_tabicatora_status #'.$k.': wrong '.$key);
                }
            } else {
                $this->assertEquals(1, count($result[$k]), 'fetch_tabicatora_status: wrong array count '.$k);
                $this->assertTrue(is_object($result[$k][0]), 'fetch_tabicatora_status: not object '.$k);
                $this->assertTrue(property_exists($result[$k][0], 'total'), 'fetch_tabicatora_status #'.$k.': missing key - total');
                $this->assertEquals($v, $result[$k][0]->total, 'fetch_tabicatora_status: wrong total for '.$k);
            }
        }
    }

    /** @test */
    public function update_tabicatora_devices()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $TabicatOra = new TabicatOra;
        $before = DB::select('SELECT id_ords, fault_type_id FROM devices_tabicat_ora');
        foreach ($before as $k => $v) {
            $this->assertEquals($v->fault_type_id, 0, 'update_tabicatora_devices: initial fault_type not 0: '.$v->fault_type_id);
        }
        $updated = $TabicatOra->updateDevices();
        $after = DB::select('SELECT id_ords, fault_type_id FROM devices_tabicat_ora');
        $this->assertEquals($updated, count($opinions['updates']), 'update_tabicatora_devices: wrong number of records updated: '.$updated);
        foreach ($after as $k => $v) {
            if (isset($opinions['updates'][$v->id_ords])) {
                $this->assertEquals($v->fault_type_id, $opinions['updates'][$v->id_ords], 'update_tabicatora_devices: updated fault_type is wrong: '.$v->id_ords.' => '.$v->fault_type_id);
            } else {
                $this->assertEquals($v->fault_type_id, 0, 'update_tabicatora_devices: fault_type should still be 0: '.$v->fault_type_id);
            }
        }
    }

    protected function _setup_devices()
    {
        $data = [
            [
                'id_ords' => 'anstiftung_1647',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Handy / Smartphone / Tablet ~ Tablet',
                'product_category' => 'Tablet',
                'brand' => '',
                'year_of_manufacture' => '',
                'repair_status' => 'Repairable',
                'event_date' => '2018-10-20',
                'problem' => 'startet nicht',
                'translation' => 'does not start',
                'language' => 'de',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'anstiftung_1657',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Handy / Smartphone / Tablet ~ Tablet',
                'product_category' => 'Tablet',
                'brand' => '',
                'year_of_manufacture' => '',
                'repair_status' => 'Repairable',
                'event_date' => '2018-10-20',
                'problem' => 'Akku immer leer',
                'translation' => 'Battery always empty',
                'language' => 'de',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'anstiftung_2577',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Handy / Smartphone / Tablet ~ Tablet',
                'product_category' => 'Tablet',
                'brand' => '',
                'year_of_manufacture' => '',
                'repair_status' => 'Fixed',
                'event_date' => '2019-02-23',
                'problem' => 'defekt',
                'translation' => 'malfunction',
                'language' => 'de',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'rcwales_1316',
                'data_provider' => 'Repair Cafe Wales',
                'country' => 'GB',
                'partner_product_category' => 'Electrical ~ acer tablet',
                'product_category' => 'Tablet',
                'brand' => 'acer tablet',
                'year_of_manufacture' => '????',
                'repair_status' => 'End of life',
                'event_date' => '2019-06-01',
                'problem' => 'acer tablet won\'t stay on',
                'translation' => 'acer tablet won\'t stay on',
                'language' => 'en',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'rcint_8454',
                'data_provider' => 'Repair Café International',
                'country' => 'NLD',
                'partner_product_category' => 'Computers/phones ~ Tablet',
                'product_category' => 'Tablet',
                'brand' => 'Nokia',
                'year_of_manufacture' => '1990',
                'repair_status' => 'Fixed',
                'event_date' => '2018-08-04',
                'problem' => 'Gaat niet aan na opladen',
                'translation' => 'Does not turn on after charging',
                'language' => 'nl',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'rcint_9462',
                'data_provider' => 'Repair Café International',
                'country' => 'NLD',
                'partner_product_category' => 'Computers/phones ~ Tablet',
                'product_category' => 'Tablet',
                'brand' => 'Apple',
                'year_of_manufacture' => '2013',
                'repair_status' => 'Repairable',
                'event_date' => '2018-09-29',
                'problem' => 'Netwerkstoornis',
                'translation' => 'network Disorder',
                'language' => 'nl',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'rcint_8243',
                'data_provider' => 'Repair Café International',
                'country' => 'GB',
                'partner_product_category' => 'Computers/phones ~ Tablet',
                'product_category' => 'Tablet',
                'brand' => 'Sony',
                'year_of_manufacture' => '2015',
                'repair_status' => 'Repairable',
                'event_date' => '2018-07-21',
                'problem' => 'broken screen ~ poorly maintained',
                'translation' => 'broken screen ~ poorly maintained',
                'language' => 'en',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'fixitclinic_141',
                'data_provider' => 'Fixit Clinic',
                'country' => 'USA',
                'partner_product_category' => 'tablet',
                'product_category' => 'Tablet',
                'brand' => 'android nexus',
                'year_of_manufacture' => '2014',
                'repair_status' => 'Repairable',
                'event_date' => '2018-07-01',
                'problem' => 'tablet ~ Broken screen',
                'translation' => 'tablet ~ Broken screen',
                'language' => 'en',
                'fault_type_id' => '0',
            ],
        ];
        foreach ($data as $k => $v) {
            DB::table('devices_tabicat_ora')->insert([
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
                'translation' => $v['translation'],
                'language' => $v['language'],
                'fault_type_id' => 0,
            ]);
            $this->assertDatabaseHas('devices_tabicat_ora', [
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

        // $data[0] : 3 opinions with consensus : recat
        $opinions[$data[0]['id_ords']][] = $this->_insert_opinion($data[0]['id_ords'], 2);
        $opinions[$data[0]['id_ords']][] = $this->_insert_opinion($data[0]['id_ords'], 2);
        $opinions[$data[0]['id_ords']][] = $this->_insert_opinion($data[0]['id_ords'], 2);
        $updates[$data[0]['id_ords']] = 2;

        // $data[1] : 3 opinions with majority : recat
        $opinions[$data[1]['id_ords']][] = $this->_insert_opinion($data[1]['id_ords'], 2);
        $opinions[$data[1]['id_ords']][] = $this->_insert_opinion($data[1]['id_ords'], 2);
        $opinions[$data[1]['id_ords']][] = $this->_insert_opinion($data[1]['id_ords'], 25);
        $updates[$data[1]['id_ords']] = 2;

        // $data[2] : 3 opinions split
        $opinions[$data[2]['id_ords']][] = $this->_insert_opinion($data[2]['id_ords'], 2);
        $opinions[$data[2]['id_ords']][] = $this->_insert_opinion($data[2]['id_ords'], 25);
        $opinions[$data[2]['id_ords']][] = $this->_insert_opinion($data[2]['id_ords'], 26);

        // $data[3] : 3 opinions adjudicated : recat
        $opinions[$data[3]['id_ords']][] = $this->_insert_opinion($data[3]['id_ords'], 2);
        $opinions[$data[3]['id_ords']][] = $this->_insert_opinion($data[3]['id_ords'], 25);
        $opinions[$data[3]['id_ords']][] = $this->_insert_opinion($data[3]['id_ords'], 26);
        DB::update("INSERT INTO devices_faults_tablets_ora_adjudicated SET id_ords = '".$data[3]['id_ords']."', fault_type_id=2");
        $updates[$data[3]['id_ords']] = 2;

        // $devs[4] : 2 opinions with majority : recat
        $opinions[$data[4]['id_ords']][] = $this->_insert_opinion($data[4]['id_ords'], 2);
        $opinions[$data[4]['id_ords']][] = $this->_insert_opinion($data[4]['id_ords'], 2);
        $updates[$data[4]['id_ords']] = 2;

        // $devs[5] : 2 opinions split
        $opinions[$data[5]['id_ords']][] = $this->_insert_opinion($data[5]['id_ords'], 2);
        $opinions[$data[5]['id_ords']][] = $this->_insert_opinion($data[5]['id_ords'], 25);

        // $devs[6] : 1 opinion
        $opinions[$data[6]['id_ords']][] = $this->_insert_opinion($data[6]['id_ords'], 26);

        $status = [
            'total_devices' => 8,
            'total_opinions_3' => 4,
            'total_opinions_2' => 2,
            'total_opinions_1' => 1,
            'total_opinions_0' => 1,
            'total_recats' => 4,
            'list_recats' => [
                0 => [
                    'winning_opinion' => 'Screen',
                    'total' => 4,
                ],
            ],
            'total_splits' => 1,
            'list_splits' => [
                0 => [
                    'id_ords' => $data[2]['id_ords'],
                    'all_crowd_opinions_count' => 3,
                    'opinions' => 'Other,Poor data,Screen',
                ],
            ],
            'progress' => 63,
        ];

        return [
            'status' => $status,
            'opinions' => $opinions,
            'updates' => $updates,
        ];
    }

    protected function _insert_opinion($id_ords, $fault_type_id)
    {
        $insert = [
            'id_ords' => $id_ords,
            'fault_type_id' => $fault_type_id,
        ];
        DB::table('devices_faults_tablets_ora_opinions')->insert($insert);
        $this->assertDatabaseHas('devices_faults_tablets_ora_opinions', [
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
            1 => [
                'id' => 1,
                'title' => 'Power/battery',
                'description' => '',
                'regex' => 'batter|power|start',
            ],
            2 => [
                'id' => 2,
                'title' => 'Screen',
                'description' => 'LCD/LED screen problem',
                'regex' => 'screen|display',
            ],
            3 => [
                'id' => 3,
                'title' => 'Stuck booting',
                'description' => 'Powers on but OS does not load/errors',
                'regex' => 'start|boot',
            ],
            25 => [
                'id' => 25,
                'title' => 'Poor data',
                'description' => 'Not enough info to determine the main fault',
                'regex' => '',
            ],
            26 => [
                'id' => 26,
                'title' => 'Other',
                'description' => 'Main fault is known but there is no option for it',
                'regex' => '',
            ],
        ];

        foreach ($fault_types as $row) {
            DB::table('fault_types_tablets')->insert($row);
            $this->assertDatabaseHas('fault_types_tablets', ['id' => $row['id']]);
        }

        return $fault_types;
    }
}
