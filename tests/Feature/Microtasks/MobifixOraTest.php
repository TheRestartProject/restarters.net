<?php

namespace Tests\Feature;

use App\MobifixOra;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class MobifixOraTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        MobifixOra::truncate();
        DB::table('devices_mobifix_ora')->truncate();
        DB::table('devices_faults_mobiles_ora_adjudicated')->truncate();
    }

    /** @test */
    public function fetch_mobifixora_record()
    {
        $data = $this->_setup_devices();
        $MobifixOra = new MobifixOra;

        $result = $MobifixOra->fetchFault();
        $this->assertTrue(is_array($result), 'fetch_mobifixora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_mobifixora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_mobifixora_record: id_ords is null');

        // leave only 1 record
        $exclude = [];
        foreach ($data as $v) {
            $exclude[] = $v['id'];
        }
        $include = array_pop($exclude);
        $result = $MobifixOra->fetchFault($exclude);
        $this->assertTrue(is_array($result), 'fetch_mobifixora_record: result is not array');
        $this->assertEquals(1, count($result), 'fetch_mobifixora_record: wrong result count');
        $this->assertGreaterThan(0, ! is_null($result[0]->id_ords), 'fetch_mobifixora_record: id_ords is null');
        $this->assertEquals($include, $result[0]->id_ords, 'fetch_mobifixora_record: wrong value');

        // exclude all records for one partner
        $exclude = [];
        foreach ($data as $k => $v) {
            if ($v['data_provider'] == 'anstiftung') {
                $exclude[] = $v['id'];
            }
        }
        $result = $MobifixOra->fetchFault($exclude, 'anstiftung');
        $this->assertTrue(empty($result), 'fetch_mobifixora_record: result is not false');
    }

    /** @test */
    public function fetch_mobifixora_page()
    {
        $data = $this->_setup_devices();
        $this->withSession([]);
        $this->_bypass_cta();
        for ($i = 1; $i <= count($data); $i++) {
            // Illuminate\Testing\TestResponse
            $response = $this->get('/mobifixora');
            $seshids = $this->app['session']->get('mobifixora.exclusions');
            $this->assertEquals($i, count($seshids), 'mobifixora.exclusions wrong length');
            $response->assertSuccessful();
            $response->assertViewIs('mobifixora.index');
        }
        // No more records for this user
        $response = $this->get('/mobifixora');
        $response->assertSessionHas('mobifixora.exclusions');
        $response->assertRedirect();
        $response->assertRedirect(url()->current().'/status');
    }

    /** @test */
    public function fetch_mobifixora_status()
    {
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $MobifixOra = new MobifixOra;
        $result = $MobifixOra->fetchStatus();
        $this->assertTrue(is_array($result));
        foreach ($opinions['status'] as $k => $v) {
            $this->assertTrue(isset($result, $k), 'fetch_mobifixora_status: missing key - '.$k);
            if (! is_array($v)) {
                $this->assertEquals($v, $result[$k][0]->total, 'fetch_mobifixora_status: wrong '.$k);
            } else {
                $this->assertTrue(is_array($result[$k]), 'fetch_mobifixora_status: not array - '.$k);
                foreach ($v[0] as $key => $val) {
                    $this->assertTrue(property_exists($result[$k][0], $key), 'fetch_mobifixora_status #'.$k.': missing key - '.$key);
                    $this->assertEquals($val, $result[$k][0]->{$key}, 'fetch_mobifixora_status #'.$k.': wrong '.$key);
                }
            }
        }
    }

    /** @test */
    public function update_mobifixora_devices()
    {
        $data = $this->_setup_devices();
        $opinions = $this->_setup_opinions($data);
        $MobifixOra = new MobifixOra;
        $before = DB::select('SELECT id_ords, fault_type_id FROM devices_mobifix_ora');
        foreach ($before as $k => $v) {
            $this->assertEquals($v->fault_type_id, 0, 'update_mobifixora_devices: initial fault_type not 0: '.$v->fault_type_id);
        }
        $updated = $MobifixOra->updateDevices();
        $after = DB::select('SELECT id_ords, fault_type_id FROM devices_mobifix_ora');
        $this->assertEquals($updated, count($opinions['updates']), 'update_mobifixora_devices: wrong number of records updated: '.$updated);
        foreach ($after as $k => $v) {
            if (isset($opinions['updates'][$v->id_ords])) {
                $this->assertEquals($v->fault_type_id, $opinions['updates'][$v->id_ords], 'update_mobifixora_devices: updated fault_type is wrong: '.$v->id_ords.' => '.$v->fault_type_id);
            } else {
                $this->assertEquals($v->fault_type_id, 0, 'update_mobifixora_devices: fault_type should still be 0: '.$v->fault_type_id);
            }
        }
    }

    protected function _setup_devices()
    {
        $data = [
            [
                'id' => 'anstiftung_1647',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'product_category' => 'Mobile',
                'brand' => '',
                'model' => 'Smartphone',
                'year_of_manufacture' => '',
                'repair_status' => 'Repairable',
                'group_identifier' => '15886',
                'date' => '2018-10-20',
                'problem' => 'startet nicht',
                'translation' => 'does not start',
                'language' => 'de',
            ],
            [
                'id' => 'anstiftung_1657',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'product_category' => 'Mobile',
                'brand' => '',
                'model' => 'Smartphone',
                'year_of_manufacture' => '',
                'repair_status' => 'Repairable',
                'group_identifier' => '15886',
                'date' => '2018-10-20',
                'problem' => 'Akku immer leer',
                'translation' => 'Battery always empty',
                'language' => 'de',
            ],
            [
                'id' => 'anstiftung_1673',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'product_category' => 'Mobile',
                'brand' => '',
                'model' => 'Smartphone',
                'year_of_manufacture' => '',
                'repair_status' => 'Fixed',
                'group_identifier' => '15886',
                'date' => '2018-10-20',
                'problem' => 'Lädt nicht auf',
                'translation' => 'does not charge',
                'language' => 'de',
            ],
            ['id' => 'anstiftung_2577',
                'data_provider' => 'anstiftung',
                'country' => 'DEU',
                'product_category' => 'Mobile',
                'brand' => '',
                'model' => 'Handy',
                'year_of_manufacture' => '',
                'repair_status' => 'Fixed',
                'group_identifier' => '5457',
                'date' => '2019-02-23',
                'problem' => 'defekt',
                'translation' => 'malfunction',
                'language' => 'de',
            ],
            [
                'id' => 'repaircafe_8389',
                'data_provider' => 'repaircafe',
                'country' => 'NLD',
                'product_category' => 'Mobile',
                'brand' => '',
                'model' => '',
                'year_of_manufacture' => '2017',
                'repair_status' => 'Fixed',
                'group_identifier' => '0043',
                'date' => '2018-08-03',
                'problem' => 'instellingen onjuist ~ geen toegang tot dropbox',
                'translation' => 'incorrect settings ~ access to dropbox',
                'language' => 'nl',
            ],
            [
                'id' => 'repaircafe_8454',
                'data_provider' => 'repaircafe',
                'country' => 'NLD',
                'product_category' => 'Mobile',
                'brand' => 'Nokia',
                'model' => '',
                'year_of_manufacture' => '1990',
                'repair_status' => 'Fixed',
                'group_identifier' => '0024',
                'date' => '2018-08-04',
                'problem' => 'Gaat niet aan na opladen',
                'translation' => 'Does not turn on after charging',
                'language' => 'nl',
            ],
            [
                'id' => 'repaircafe_9462',
                'data_provider' => 'repaircafe',
                'country' => 'NLD',
                'product_category' => 'Mobile',
                'brand' => 'Apple',
                'model' => '',
                'year_of_manufacture' => '2013',
                'repair_status' => 'Repairable',
                'group_identifier' => '0017',
                'date' => '2018-09-29',
                'problem' => 'Netwerkstoornis',
                'translation' => 'network Disorder',
                'language' => 'nl',
            ],
            ['id' => 'repaircafe_8243',
                'data_provider' => 'repaircafe',
                'country' => 'GBR',
                'product_category' => 'Mobile',
                'brand' => 'Sony',
                'model' => 'Xperia XA',
                'year_of_manufacture' => '2015',
                'repair_status' => 'Repairable',
                'group_identifier' => '0042',
                'date' => '2018-07-21',
                'problem' => 'broken screen ~ poorly maintained',
                'translation' => 'broken screen ~ poorly maintained',
                'language' => 'en', ],
        ];
        foreach ($data as $k => $v) {
            DB::table('devices_mobifix_ora')->insert([
                'id_ords' => $v['id'],
                'data_provider' => $v['data_provider'],
                'country' => $v['country'],
                'product_category' => $v['product_category'],
                'brand' => $v['brand'],
                'model' => $v['model'],
                'year_of_manufacture' => $v['year_of_manufacture'],
                'repair_status' => $v['repair_status'],
                'group_identifier' => $v['group_identifier'],
                'date' => $v['date'],
                'problem' => $v['problem'],
                'translation' => $v['translation'],
                'language' => $v['language'],
                'fault_type_id' => 0,
            ]);
            $this->assertDatabaseHas('devices_mobifix_ora', [
                'id_ords' => $v['id'],
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
        $opinions[$data[0]['id']][] = $this->_insert_opinion($data[0]['id'], 2);
        $opinions[$data[0]['id']][] = $this->_insert_opinion($data[0]['id'], 2);
        $opinions[$data[0]['id']][] = $this->_insert_opinion($data[0]['id'], 2);
        $updates[$data[0]['id']] = 2;

        // $data[1] : 3 opinions with majority : recat
        $opinions[$data[1]['id']][] = $this->_insert_opinion($data[1]['id'], 2);
        $opinions[$data[1]['id']][] = $this->_insert_opinion($data[1]['id'], 2);
        $opinions[$data[1]['id']][] = $this->_insert_opinion($data[1]['id'], 25);
        $updates[$data[1]['id']] = 2;

        // $data[2] : 3 opinions split
        $opinions[$data[2]['id']][] = $this->_insert_opinion($data[2]['id'], 2);
        $opinions[$data[2]['id']][] = $this->_insert_opinion($data[2]['id'], 25);
        $opinions[$data[2]['id']][] = $this->_insert_opinion($data[2]['id'], 26);

        // $data[3] : 3 opinions adjudicated : recat
        $opinions[$data[3]['id']][] = $this->_insert_opinion($data[3]['id'], 2);
        $opinions[$data[3]['id']][] = $this->_insert_opinion($data[3]['id'], 25);
        $opinions[$data[3]['id']][] = $this->_insert_opinion($data[3]['id'], 26);
        DB::update("INSERT INTO devices_faults_mobiles_ora_adjudicated SET id_ords = '".$data[3]['id']."', fault_type_id=2");
        $updates[$data[3]['id']] = 2;

        // $devs[4] : 2 opinions with majority : recat
        $opinions[$data[4]['id']][] = $this->_insert_opinion($data[4]['id'], 2);
        $opinions[$data[4]['id']][] = $this->_insert_opinion($data[4]['id'], 2);
        $updates[$data[4]['id']] = 2;

        // $devs[5] : 2 opinions split
        $opinions[$data[5]['id']][] = $this->_insert_opinion($data[5]['id'], 2);
        $opinions[$data[5]['id']][] = $this->_insert_opinion($data[5]['id'], 25);

        // $devs[6] : 1 opinion
        $opinions[$data[6]['id']][] = $this->_insert_opinion($data[6]['id'], 26);

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
                    'id_ords' => $data[2]['id'],
                    'all_crowd_opinions_count' => 3,
                    'opinions' => 'Other,Screen,Unknown',
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
        DB::table('devices_faults_mobiles_ora_opinions')->insert($insert);
        $this->assertDatabaseHas('devices_faults_mobiles_ora_opinions', [
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
}
