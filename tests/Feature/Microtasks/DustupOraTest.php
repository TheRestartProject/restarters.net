<?php

namespace Tests\Feature;

use App\DustupOra;
use DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DustupOraTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        DustupOra::truncate();
        DB::table('devices_dustup_ora')->truncate();
        DB::table('devices_faults_vacuums_ora_adjudicated')->truncate();
        DB::table('fault_types_vacuums')->truncate();
    }

    /** @test */
    public function fetch_dustupora_record()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $DustupOra = new DustupOra;

        // insert 8 records, no exclusions
        // expects any record
        $result = $DustupOra->fetchFault();
        $this->assertTrue(is_array($result), 'fetch_dustupora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_dustupora_record: wrong result count');
        $this->assertGreaterThan(0, !is_null($result[0]->id_ords), 'fetch_dustupora_record: id_ords is null');

        // use locale, no exclusions
        // expects a record with language matching locale
        // except for invalid language where any record returned
        $exclude = [];
        $locales = ['de', 'nl', 'en', 'foo'];
        foreach ($locales as $locale) {
            $result = $DustupOra->fetchFault($exclude, $locale);
            $this->assertTrue(is_array($result), 'fetch_dustupora_record: result is not array');
            $this->assertEquals(1, count($result), 'fetch_dustupora_record: wrong result count');
            $this->assertGreaterThan(0, !is_null($result[0]->id_ords), 'fetch_dustupora_record: id_ords is null');
            if ($locale !== 'foo') {
                $this->assertEquals($locale, $result[0]->language, 'fetch_dustupora_record: wrong language');
            }
        }

        // exclude 8 records
        // expects no records returned
        foreach ($data as $v) {
            $exclude[] = $v['id_ords'];
        }
        $result = $DustupOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_dustupora_record: result is not array');
        $this->assertEquals(0, count($result), 'fetch_dustupora_record: wrong result count');

        // exclude 7 records
        // expects only the leftover/included record
        $include = array_pop($exclude);
        $result = $DustupOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_dustupora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_dustupora_record: wrong result count');
        $this->assertGreaterThan(0, !is_null($result[0]->id_ords), 'fetch_dustupora_record: id_ords is null');
        $this->assertEquals($include, $result[0]->id_ords, 'fetch_dustupora_record: wrong value');

        // use locale, exclude 7 records
        // expects the same record regardless of locale
        foreach ($locales as $locale) {
            $result = $DustupOra->fetchFault($exclude, $locale);
            $this->assertTrue(is_array($result), 'fetch_dustupora_record: result is not array');
            $this->assertEquals(1, count($result), 'fetch_dustupora_record: wrong result count');
            $this->assertGreaterThan(0, !is_null($result[0]->id_ords), 'fetch_dustupora_record: id_ords is null');
            $this->assertEquals($include, $result[0]->id_ords, 'fetch_dustupora_record: wrong value');
        }

        // no exclusions, opinions exist
        // expects 1 of the 3 records with not enough opinions
        $exclude = [];
        $opinions = $this->_setup_opinions($data);
        $expect = ['rcwales_3036','rcint_22039','rcwales_4125']; //['rcint_8243', 'rcint_9462', 'fixitclinic_141'];
        $result = $DustupOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_dustupora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_dustupora_record: wrong result count');
        $this->assertGreaterThan(0, !is_null($result[0]->id_ords), 'fetch_dustupora_record: id_ords is null');
        $this->assertTrue(in_array($result[0]->id_ords, $expect), 'fetch_dustupora_record: invalid id_ords');
    }

    /** @test */
    public function dustupora_should_redirect_to_status_page()
    {
        // DustUp is closed - should redirect to status page.
        $response = $this->get('/tabicat');
        $response->assertRedirect(url()->current() . '/status');
    }


    /** @test */
    public function fetch_dustupora_status()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $BattcatOra = new DustupOra;
        $result = $BattcatOra->fetchStatus();
        $this->assertTrue(is_array($result), 'fetch_dustupora_status: result is not array');
        foreach ($opinions['status'] as $k => $v) {
            $this->assertTrue(isset($result, $k), 'fetch_dustupora_status: missing key - '.$k);
            if ($k == 'list_recats') {
                $this->assertTrue(is_array($result[$k]), 'fetch_dustupora_status: not array - '.$k);
                foreach ($v[0] as $key => $val) {
                    $this->assertTrue(property_exists($result[$k][0], $key), 'fetch_dustupora_status #'.$k.': missing key - '.$key);
                    $this->assertEquals($val, $result[$k][0]->{$key}, 'fetch_dustupora_status #'.$k.': wrong '.$key);
                }
            } else {
                $this->assertEquals(1, count($result[$k]), 'fetch_dustupora_status: wrong array count '.$k);
                $this->assertTrue(is_object($result[$k][0]), 'fetch_dustupora_status: not object '.$k);
                $this->assertTrue(property_exists($result[$k][0], 'total'), 'fetch_dustupora_status #'.$k.': missing key - total');
                $this->assertEquals($v, $result[$k][0]->total, 'fetch_dustupora_status: wrong total for '.$k);
            }
        }
    }

    public function fetch_dustupora_statusX()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $DustupOra = new DustupOra;
        $result = $DustupOra->fetchStatus();
        $this->assertTrue(is_array($result), 'fetch_dustupora_status: result is not array');
        foreach ($opinions['status'] as $k => $v) {
            $this->assertTrue(isset($result, $k), 'fetch_dustupora_status: missing key - ' . $k);
            if ($k == 'list_recats' || $k == 'list_splits') {
                $this->assertTrue(is_array($result[$k]), 'fetch_dustupora_status: not array - ' . $k);
                foreach ($v[0] as $key => $val) {
                    $this->assertTrue(property_exists($result[$k][0], $key), 'fetch_dustupora_status #' . $k . ': missing key - ' . $key);
                    $this->assertEquals($val, $result[$k][0]->{$key}, 'fetch_dustupora_status #' . $k . ': wrong ' . $key);
                }
            } else {
                $this->assertEquals(1, count($result[$k]), 'fetch_dustupora_status: wrong array count ' . $k);
                $this->assertTrue(is_object($result[$k][0]), 'fetch_dustupora_status: not object ' . $k);
                $this->assertTrue(property_exists($result[$k][0], 'total'), 'fetch_dustupora_status #' . $k . ': missing key - total');
                $this->assertEquals($v, $result[$k][0]->total, 'fetch_dustupora_status: wrong total for ' . $k);
            }
        }
    }

    /** @test */
    public function update_dustupora_devices()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $DustupOra = new DustupOra;
        $before = DB::select('SELECT id_ords, fault_type_id FROM devices_dustup_ora');
        foreach ($before as $k => $v) {
            $this->assertEquals($v->fault_type_id, 0, 'update_dustupora_devices: initial fault_type not 0: ' . $v->fault_type_id);
        }
        $updated = $DustupOra->updateDevices();
        $after = DB::select('SELECT id_ords, fault_type_id FROM devices_dustup_ora');
        $this->assertEquals($updated, count($opinions['updates']), 'update_dustupora_devices: wrong number of records updated: ' . $updated);
        foreach ($after as $k => $v) {
            if (isset($opinions['updates'][$v->id_ords])) {
                $this->assertEquals($v->fault_type_id, $opinions['updates'][$v->id_ords], 'update_dustupora_devices: updated fault_type is wrong: ' . $v->id_ords . ' => ' . $v->fault_type_id);
            } else {
                $this->assertEquals($v->fault_type_id, 0, 'update_dustupora_devices: fault_type should still be 0: ' . $v->fault_type_id);
            }
        }
    }

    protected function _setup_devices()
    {

        $data = [
            [
                'id_ords' => 'anstiftung_3408',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Haushaltsgeräte ~ Staubsauger',
                'product_category' => 'Vacuum',
                'brand' => 'Clean Maxx',
                'year_of_manufacture' => '',
                'product_age' => '',
                'repair_status' => 'Unknown',
                'event_date' => '2019-07-17',
                'problem' => 'Geht nicht an.',
                'googletrans' => 'It does not begin.',
                'language' => 'de',
                'fault_type_id' => '0',
                'en' => '',
                'de' => '',
                'nl' => '',
                'fr' => '',
                'it' => '',
                'es' => ''
            ],
            [
                'id_ords' => 'anstiftung_6593',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Elektro Sonstiges ~ Saugroboter',
                'product_category' => 'Vacuum',
                'brand' => 'Unknown',
                'year_of_manufacture' => '',
                'product_age' => '',
                'repair_status' => 'End of life',
                'event_date' => '2021-07-03',
                'problem' => 'wischt und saugt nicht mehr',
                'googletrans' => 'Wipes and does not suck anymore',
                'language' => 'de',
                'fault_type_id' => '0',
                'en' => '',
                'de' => '',
                'nl' => '',
                'fr' => '',
                'it' => '',
                'es' => ''
            ],
            [
                'id_ords' => 'restart_8563',
                'data_provider' => 'The Restart Project',
                'country' => 'GBR',
                'partner_product_category' => 'Vacuum',
                'product_category' => 'Vacuum',
                'brand' => 'Dyson',
                'year_of_manufacture' => '',
                'product_age' => '',
                'repair_status' => 'End of life',
                'event_date' => '2018-06-05',
                'problem' => 'Internal parts burnt.',
                'googletrans' => 'Internal parts burnt.',
                'language' => 'en',
                'fault_type_id' => '0',
                'en' => '',
                'de' => '',
                'nl' => '',
                'fr' => '',
                'it' => '',
                'es' => ''
            ],
            [
                'id_ords' => 'restart_3292',
                'data_provider' => 'The Restart Project',
                'country' => 'ITA',
                'partner_product_category' => 'Vacuum',
                'product_category' => 'Vacuum',
                'brand' => 'Unknown',
                'year_of_manufacture' => '',
                'product_age' => '',
                'repair_status' => 'Fixed',
                'event_date' => '2016-09-17',
                'problem' => 'Problems in the handle fixed with Sugru',
                'googletrans' => 'Problems in the handle fixed with Sugru',
                'language' => 'en',
                'fault_type_id' => '0',
                'en' => '',
                'de' => '',
                'nl' => '',
                'fr' => '',
                'it' => '',
                'es' => ''
            ],
            [
                'id_ords' => 'rcint_28833',
                'data_provider' => 'Repair Café International',
                'country' => 'NLD',
                'partner_product_category' => 'Household appliances electric ~ Vacuum cleaner',
                'product_category' => 'Vacuum',
                'brand' => 'Dirt Devil',
                'year_of_manufacture' => '',
                'product_age' => '',
                'repair_status' => 'Fixed',
                'event_date' => '2020-01-30',
                'problem' => 'snoer kapot. reparatie snoer.',
                'googletrans' => 'Cord broken. Repair cord.',
                'language' => 'nl',
                'fault_type_id' => '0',
                'en' => '',
                'de' => '',
                'nl' => '',
                'fr' => '',
                'it' => '',
                'es' => ''
            ],
            [
                'id_ords' => 'rcint_22039',
                'data_provider' => 'Repair Café International',
                'country' => 'NLD',
                'partner_product_category' => 'Household appliances electric ~ Handheld vacuum cleaner',
                'product_category' => 'Vacuum',
                'brand' => 'Black & Decker',
                'year_of_manufacture' => '',
                'product_age' => '',
                'repair_status' => 'End of life',
                'event_date' => '2019-09-26',
                'problem' => 'batterijpack defect.',
                'googletrans' => 'batterijpack defect.',
                'language' => 'en',
                'fault_type_id' => '0',
                'en' => '',
                'de' => '',
                'nl' => '',
                'fr' => '',
                'it' => '',
                'es' => ''
            ],
            [
                'id_ords' => 'rcwales_4125',
                'data_provider' => 'Repair Cafe Wales',
                'country' => 'GBR',
                'partner_product_category' => 'Electrical ~ hoover',
                'product_category' => 'Vacuum',
                'brand' => 'Unknown',
                'year_of_manufacture' => '',
                'product_age' => '',
                'repair_status' => 'Fixed',
                'event_date' => '2020-02-01',
                'problem' => 'hoover intermittent power supply',
                'googletrans' => 'hoover intermittent power supply',
                'language' => 'en',
                'fault_type_id' => '0',
                'en' => '',
                'de' => '',
                'nl' => '',
                'fr' => '',
                'it' => '',
                'es' => '',
            ],
            [
                'id_ords' => 'rcwales_3036',
                'data_provider' => 'Repair Cafe Wales',
                'country' => 'GBR',
                'partner_product_category' => 'Electrical ~ vacuum cleaner',
                'product_category' => 'Vacuum',
                'brand' => 'Dyson',
                'year_of_manufacture' => '',
                'product_age' => '',
                'repair_status' => 'Unknown',
                'event_date' => '2019-11-01',
                'problem' => 'vacuum cleaner falls apart',
                'googletrans' => 'vacuum cleaner falls apart',
                'language' => 'en',
                'fault_type_id' => '0',
                'en' => '',
                'de' => '',
                'nl' => '',
                'fr' => '',
                'it' => '',
                'es' => ''
            ],
        ];
        foreach ($data as $k => $v) {
            DB::table('devices_dustup_ora')->insert([
                'id_ords' => $v['id_ords'],
                'data_provider' => $v['data_provider'],
                'country' => $v['country'],
                'partner_product_category' => $v['partner_product_category'],
                'product_category' => $v['product_category'],
                'brand' => $v['brand'],
                'year_of_manufacture' => $v['year_of_manufacture'],
                'product_age' => $v['product_age'],
                'repair_status' => $v['repair_status'],
                'event_date' => $v['event_date'],
                'problem' => $v['problem'],
                'googletrans' => $v['googletrans'],
                'language' => $v['language'],
                'fault_type_id' => 0,
            ]);
            $this->assertDatabaseHas('devices_dustup_ora', [
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
        DB::update("INSERT INTO devices_faults_vacuums_ora_adjudicated SET id_ords = '" . $data[3]['id_ords'] . "', fault_type_id=2");
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
            'total_opinions' => 17,
            'total_recats' => 4,
            'list_recats' => [
                0 => [
                    'winning_opinion' => 'Motor',
                    'total' => 2,
                ],
            ],
            'progress' => 50,
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
        DB::table('devices_faults_vacuums_ora_opinions')->insert($insert);
        $this->assertDatabaseHas('devices_faults_vacuums_ora_opinions', [
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
                'title' => 'Motor',
                'description' => 'Motor problem',
                'regex' => 'motor|engine',
            ],
            3 => [
                'id' => 3,
                'title' => 'Performance',
                'description' => 'Slow, poor suction, noisy',
                'regex' => 'noise|slow',
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
            DB::table('fault_types_vacuums')->insert($row);
            $this->assertDatabaseHas('fault_types_vacuums', ['id' => $row['id']]);
        }

        return $fault_types;
    }
}
