<?php

namespace Tests\Feature;

use App\PrintcatOra;
use DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PrintcatOraTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        PrintcatOra::truncate();
        DB::table('devices_printcat_ora')->truncate();
        DB::table('devices_faults_printers_ora_adjudicated')->truncate();
        DB::table('fault_types_printers')->truncate();
    }

    /** @test */
    public function fetch_printcatora_record()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $PrintcatOra = new PrintcatOra;

        // insert 8 records, no exclusions
        $result = $PrintcatOra->fetchFault();
        $this->assertTrue(is_array($result), 'fetch_printcatora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_printcatora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_printcatora_record: id_ords is null');

        // exclude 8 records
        $exclude = [];
        foreach ($data as $v) {
            $exclude[] = $v['id_ords'];
        }
        $result = $PrintcatOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_printcatora_record: result is not array');
        $this->assertEquals(0, count($result), 'fetch_printcatora_record: wrong result count');

        // exclude 7 records
        $exclude = [];
        foreach ($data as $v) {
            $exclude[] = $v['id_ords'];
        }
        $include = array_pop($exclude);
        $result = $PrintcatOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_printcatora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_printcatora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_printcatora_record: id_ords is null');
        $this->assertEquals($include, $result[0]->id_ords, 'fetch_printcatora_record: wrong value');
    }

    /** @test */
    public function printcat_should_redirect_to_status_page()
    {
        // PrintCat is closed - should redirect to status page.
        $response = $this->get('/printcat');
        $response->assertRedirect(url()->current().'/status');
    }

    /** @test */
    public function fetch_printcatora_status()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $PrintcatOra = new PrintcatOra;
        $result = $PrintcatOra->fetchStatus();
        $this->assertTrue(is_array($result));
        foreach ($opinions['status'] as $k => $v) {
            $this->assertTrue(isset($result, $k), 'fetch_printcatora_status: missing key - '.$k);
            if (! is_array($v)) {
                $this->assertEquals($v, $result[$k][0]->total, 'fetch_printcatora_status: wrong '.$k);
            } else {
                $this->assertTrue(is_array($result[$k]), 'fetch_printcatora_status: not array - '.$k);
                foreach ($v[0] as $key => $val) {
                    $this->assertTrue(property_exists($result[$k][0], $key), 'fetch_printcatora_status #'.$k.': missing key - '.$key);
                    $this->assertEquals($val, $result[$k][0]->{$key}, 'fetch_printcatora_status #'.$k.': wrong '.$key);
                }
            }
        }
    }

    /** @test */
    public function update_printcatora_devices()
    {
        $fault_types = $this->_setup_fault_types();
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $PrintcatOra = new PrintcatOra;
        $before = DB::select('SELECT id_ords, fault_type_id FROM devices_printcat_ora');
        foreach ($before as $k => $v) {
            $this->assertEquals($v->fault_type_id, 0, 'update_printcatora_devices: initial fault_type not 0: '.$v->fault_type_id);
        }
        $updated = $PrintcatOra->updateDevices();
        $after = DB::select('SELECT id_ords, fault_type_id FROM devices_printcat_ora');
        $this->assertEquals($updated, count($opinions['updates']), 'update_printcatora_devices: wrong number of records updated: '.$updated);
        foreach ($after as $k => $v) {
            if (isset($opinions['updates'][$v->id_ords])) {
                $this->assertEquals($v->fault_type_id, $opinions['updates'][$v->id_ords], 'update_printcatora_devices: updated fault_type is wrong: '.$v->id_ords.' => '.$v->fault_type_id);
            } else {
                $this->assertEquals($v->fault_type_id, 0, 'update_printcatora_devices: fault_type should still be 0: '.$v->fault_type_id);
            }
        }
    }

    protected function _setup_devices()
    {
        $data = [
            [
                'id_ords' => 'restart_2394',
                'data_provider' => 'The Restart Project',
                'country' => 'GB',
                'partner_product_category' => 'Printer/scanner',
                'product_category' => 'Printer/scanner',
                'brand' => 'Lexmark',
                'year_of_manufacture' => '????',
                'repair_status' => 'Repairable',
                'event_date' => '2016-03-12',
                'problem' => 'Printer - Not connecting, needs testing',
                'translation' => 'Printer - Not connecting, needs testing',
                'language' => 'en',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'rcwales_310',
                'data_provider' => 'Repair Cafe Wales',
                'country' => 'GB',
                'partner_product_category' => 'IT/Phone ~ Printer',
                'product_category' => 'Printer/scanner',
                'brand' => 'Unknown',
                 'year_of_manufacture' => '????',
                'repair_status' => 'End of life',
                'event_date' => '2018-03-01',
                'problem' => 'Printer Doesnt work- major mechanical fault',
                'translation' => 'Printer Doesnt work- major mechanical fault',
                'language' => 'en',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'rcint_24791',
                'data_provider' => 'Repair Café International',
                'country' => 'NLD', 'partner_product_category' => 'Computers/phones ~ Printer', 'product_category' => 'Printer/scanner',
                'brand' => 'Brother',
                'year_of_manufacture' => '????',
                'repair_status' => 'End of life',
                'event_date' => '2019-11-09',
                'problem' => 'Print niet meer, ondanks nieuwe cartridges.',
                'translation' => 'Print no more, despite new cartridges.',
                'language' => 'nl',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'anstiftung_819',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Elektro Sonstiges ~ Faxgerät',
                'product_category' => 'Printer/scanner',
                'brand' => 'Unknown',
                'year_of_manufacture' => '????',
                'repair_status' => 'Unknown',
                'event_date' => '2019-01-19',
                'problem' => 'Ankommede Rufe funktionieren nicht',
                'translation' => 'Ankommede calls do not work',
                'language' => 'de',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'anstiftung_2077',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'partner_product_category' => 'Computer ~ Drucker',
                'product_category' => 'Printer/scanner',
                'brand' => 'Canon',
                'year_of_manufacture' => '????',
                'repair_status' => 'Unknown',
                'event_date' => '2018-10-23',
                'problem' => 'Zieht Papier nicht ein. Fehler der Ansteuerung nicht gefunden',
                'translation' => 'Does not feed paper. Not Found error of control',
                'language' => 'de',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'rcint_26214',
                'data_provider' => 'Repair Café International',
                'country' => 'NLD',
                'partner_product_category' => 'Computers/phones ~ Printer',
                'product_category' => 'Printer/scanner',
                'brand' => 'HP',
                'year_of_manufacture' => '2005',
                'repair_status' => 'Repairable',
                'event_date' => '2019-12-07',
                'problem' => 'kliedert, en neemt meer bladen tegelijk',
                'translation' => 'feeds more sheets at once',
                'language' => 'nl', 'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'fixitclinic_118',
                'data_provider' => 'Fixit Clinic',
                'country' => 'USA',
                'partner_product_category' => 'printer',
                'product_category' => 'Printer/scanner',
                'brand' => 'HP',
                'year_of_manufacture' => '2010',
                'repair_status' => 'Repairable',
                'event_date' => '2018-04-26',
                'problem' => 'printer ~ paper jam error with no paper jammed. ',
                'translation' => 'printer ~ paper jam error with no paper jammed. ',
                'language' => 'en',
                'fault_type_id' => '0',
            ],
            [
                'id_ords' => 'rcwales_299',
                'data_provider' => 'Repair Cafe Wales',
                'country' => 'GB',
                'partner_product_category' => 'IT/Phone ~ Printer',
                'product_category' => 'Printer/scanner',
                'brand' => 'Unknown',
                'year_of_manufacture' => '????',
                'repair_status' => 'Fixed',
                'event_date' => '2017-12-01',
                'problem' => 'Printer Thinks it doesn\'t have any ink in it- doesn\'t print',
                'translation' => 'Printer Thinks it doesn\'t have any ink in it- doesn\'t print',
                'language' => 'en',
                'fault_type_id' => '0',
            ],
        ];

        foreach ($data as $k => $v) {
            DB::table('devices_printcat_ora')->insert([
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
                'fault_type_id' => $v['fault_type_id'],
            ]);
            $this->assertDatabaseHas('devices_printcat_ora', [
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
        DB::update("INSERT INTO devices_faults_printers_ora_adjudicated SET id_ords = '".$data[3]['id_ords']."', fault_type_id=2");
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
                    'winning_opinion' => 'Display panel',
                    'total' => 4,
                ],
            ],
            'total_splits' => 1,
            'list_splits' => [
                0 => [
                    'id_ords' => $data[2]['id_ords'],
                    'all_crowd_opinions_count' => 3,
                    'opinions' => 'Display panel,Other,Unknown',
                ],
            ],
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
        DB::table('devices_faults_printers_ora_opinions')->insert($insert);
        $this->assertDatabaseHas('devices_faults_printers_ora_opinions', [
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
                'title' => 'Display panel',
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
                'title' => 'Unknown',
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
            DB::table('fault_types_printers')->insert($row);
            $this->assertDatabaseHas('fault_types_printers', ['id' => $row['id']]);
        }

        return $fault_types;
    }
}
