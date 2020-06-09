<?php

namespace Tests\Unit;

use App\Device;
use App\Category;
use App\Misccat;
use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class MisccatTest extends TestCase {

    use RefreshDatabase;

    public function setUp() {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        Category::truncate();
        Misccat::truncate();
    }

    /** @test */
    public function fetch_misccat_record() {

        $devices = 5;
        $this->_insert_misccat_devices($devices);

        $iddevices = array_fill(1, $devices, 0);
        $Misccat = new Misccat;
        for ($i = 0; $i < 100; $i++) {
            $result = $Misccat->fetchMisc();
            $this->assertTrue(is_array($result));
            $this->assertEquals(count($result), 1);
            $this->assertGreaterThan(0, $result[0]->iddevices);
            $iddevices[$result[0]->iddevices] ++;
        }
        for ($i = 1; $i < $devices; $i++) {
            $this->assertGreaterThan(0, $iddevices[$i], 'fetch_misccat_record: 0 fetched for iddevices=' . $i);
        }

        $misccat = factory(Misccat::class, 3)->states('misc')->create([
            'iddevices' => 3,
        ]);

        $iddevices = array_fill(1, $devices, 0);
        $Misccat = new Misccat;
        for ($i = 0; $i < 100; $i++) {
            $result = $Misccat->fetchMisc();
            $this->assertTrue(is_array($result));
            $this->assertEquals(count($result), 1);
            $this->assertGreaterThan(0, $result[0]->iddevices);
            $iddevices[$result[0]->iddevices] ++;
        }
        $this->assertEquals($iddevices[3], 0, 'fetch_misccat_record: records fetched for iddevices=3');
    }

    /** @test */
    public function fetch_misccat_status() {

//WHEN -2 THEN 'Original category was not Misc'
// 1 device record, 0 misccat records
        $device = factory(Device::class, 1)->states('mobile')->create();
//        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 1,
            'category' => 25,
            'category_creation' => 25,
        ]);
        $this->assertDatabaseMissing('devices_misc_opinions', [
            'iddevices' => 1,
        ]);

//WHEN -1 THEN 'Category has been updated from Misc, thanks!'
// 1 device record = "Mobile", 2 misccat records = "Mobile"
        $device = factory(Device::class, 1)->states('mobile')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 2,
            'category' => 25,
            'category_creation' => 25,
        ]);
        $misccat = factory(Misccat::class, 2)->states('mobile')->create([
            'iddevices' => 2,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 1,
            'iddevices' => 2,
            'category' => 'Mobile',
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 2,
            'iddevices' => 2,
            'category' => 'Mobile',
        ]);

//WHEN 0 THEN 'Is Misc and has no opinions'        
// 1 device record = "Misc", 0 misccat records
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 3,
            'category' => 46,
            'category_creation' => 46,
        ]);
        $this->assertDatabaseMissing('devices_misc_opinions', [
            'iddevices' => 3,
        ]);

//WHEN 1 THEN 'Is Misc and has only one opinion'
// 1 device record = "Misc", 1 misccat record = "cat1"
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 4,
            'category' => 46,
            'category_creation' => 46,
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat1')->create([
            'iddevices' => 4,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 3,
            'iddevices' => 4,
            'category' => 'cat1',
        ]);

//WHEN 2 THEN 'Is Misc and needs just one more opinion'
// 1 device record = "Misc", 2 misccat records = "cat1"/"cat2"
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 5,
            'category' => 46,
            'category_creation' => 46,
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat1')->create([
            'iddevices' => 5,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 4,
            'iddevices' => 5,
            'category' => 'cat1',
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat2')->create([
            'iddevices' => 5,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 5,
            'iddevices' => 5,
            'category' => 'cat2',
        ]);

//WHEN 3 THEN 'Is Misc and opinions are split, adjudication needed'
// 1 device record = "Misc", 3 misccat records = "cat1"/"cat2"/"cat3"
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 6,
            'category' => 46,
            'category_creation' => 46,
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat1')->create([
            'iddevices' => 6,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 6,
            'iddevices' => 6,
            'category' => 'cat1',
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat2')->create([
            'iddevices' => 6,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 7,
            'iddevices' => 6,
            'category' => 'cat2',
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat3')->create([
            'iddevices' => 6,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 8,
            'iddevices' => 6,
            'category' => 'cat3',
        ]);        

//WHEN 4 THEN 'Is Misc and majority opinions agree it should remain as Misc, thanks!'
// 3 device records in total
// 1 device record = "Misc", 3 misccat records = "Misc"
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 7,
            'category' => 46,
            'category_creation' => 46,
        ]);
        // "Misc" category opinions - consensus
        $misccat = factory(Misccat::class, 3)->states('misc')->create([
            'iddevices' => 7,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 9,
            'iddevices' => 7,
            'category' => 'misc',
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 10,
            'iddevices' => 7,
            'category' => 'Misc',
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 11,
            'iddevices' => 7,
            'category' => 'Misc',
        ]);
// 1 device record = "Misc", 2 misccat records = "Misc", 1 misccat records = "cat1"
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 8,
            'category' => 46,
            'category_creation' => 46,
        ]);
        // "Misc" category opinions - majority
        $misccat = factory(Misccat::class, 2)->states('misc')->create([
            'iddevices' => 8,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 12,
            'iddevices' => 8,
            'category' => 'Misc',
        ]);        
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 13,
            'iddevices' => 8,
            'category' => 'Misc',
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat1')->create([
            'iddevices' => 8,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 14,
            'iddevices' => 8,
            'category' => 'cat1',
        ]);
        
// 1 device record = "Misc", 3 misccat records = "cat1"/"cat2"/"Misc", 1 adjudication record = "Misc"
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 9,
            'category' => 46,
            'category_creation' => 46,
        ]);         
        $misccat = factory(Misccat::class, 1)->states('misc')->create([
            'iddevices' => 9,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 15,
            'iddevices' => 9,
            'category' => 'Misc',
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat1')->create([
            'iddevices' => 9,
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 16,
            'iddevices' => 9,
            'category' => 'cat1',
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat2')->create([
            'iddevices' => 9,
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 17,
            'iddevices' => 9,
            'category' => 'cat2',
        ]);
        DB::update("INSERT INTO devices_misc_adjudicated SET iddevices = 9, category = 'Misc'");
        $this->assertDatabaseHas('devices_misc_adjudicated', [
            'id' => 1,
            'iddevices' => 9,
            'category' => 'Misc',
        ]);         

//WHEN 5 THEN 'Is Misc and majority opinions say not Misc so it will be updated soon, thanks!'
// 2 device records in total
// 1 device record = "Misc", 3 misccat records = "cat1"
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 10,
            'category' => 46,
            'category_creation' => 46,
        ]);
        $misccat = factory(Misccat::class, 3)->states('cat1')->create([
            'iddevices' => 10,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 18,
            'iddevices' => 10,
            'category' => 'cat1',
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 19,
            'iddevices' => 10,
            'category' => 'cat1',
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 20,
            'iddevices' => 10,
            'category' => 'cat1',
        ]);
        
// 1 device record = "Misc", 3 misccat records = "cat1"/"cat2"/"Misc", 1 adjudication record = "cat1"
        $device = factory(Device::class, 1)->states('misc')->create();
        //Log::info($device);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 11,
            'category' => 46,
            'category_creation' => 46,
        ]);         
        $misccat = factory(Misccat::class, 1)->states('misc')->create([
            'iddevices' => 11,
        ]);
        //Log::info($misccat);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 21,
            'iddevices' => 11,
            'category' => 'Misc',
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat1')->create([
            'iddevices' => 11,
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 22,
            'iddevices' => 11,
            'category' => 'cat1',
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat2')->create([
            'iddevices' => 11,
        ]);
        $this->assertDatabaseHas('devices_misc_opinions', [
            'id' => 23,
            'iddevices' => 11,
            'category' => 'cat2',
        ]);
        DB::update("INSERT INTO devices_misc_adjudicated SET iddevices = 11, category = 'cat1'");
        $this->assertDatabaseHas('devices_misc_adjudicated', [
            'id' => 2,
            'iddevices' => 11,
            'category' => 'cat1',
        ]);

        $Misccat = new Misccat;
        $result = $Misccat->fetchStatus();
        Log::info($result);

        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('status', $result), 'fetch_misccat_status: missing key - status');
        $this->assertEquals(count($result['status']), 8, 'fetch_misccat_status: result array wrong number of elements');
                
        $this->assertEquals($result['status'][0]->code, -2, 'fetch_misccat_status: wrong code');
        $this->assertEquals($result['status'][0]->total, 1, 'fetch_misccat_status: wrong total');
                
        $this->assertEquals($result['status'][1]->code, -1, 'fetch_misccat_status: wrong code');
        $this->assertEquals($result['status'][1]->total, 1, 'fetch_misccat_status: wrong total');
                
        $this->assertEquals($result['status'][2]->code, 0, 'fetch_misccat_status: wrong code');
        $this->assertEquals($result['status'][2]->total, 1, 'fetch_misccat_status: wrong total');
                
        $this->assertEquals($result['status'][3]->code, 1, 'fetch_misccat_status: wrong code');
        $this->assertEquals($result['status'][3]->total, 1, 'fetch_misccat_status: wrong total');
                
        $this->assertEquals($result['status'][4]->code, 2, 'fetch_misccat_status: wrong code');
        $this->assertEquals($result['status'][4]->total, 1, 'fetch_misccat_status: wrong total');
                
        $this->assertEquals($result['status'][5]->code, 3, 'fetch_misccat_status: wrong code');
        $this->assertEquals($result['status'][5]->total, 1, 'fetch_misccat_status: wrong total');
                
        $this->assertEquals($result['status'][6]->code, 4, 'fetch_misccat_status: wrong code');
        $this->assertEquals($result['status'][6]->total, 3, 'fetch_misccat_status: wrong total');
                
        $this->assertEquals($result['status'][7]->code, 5, 'fetch_misccat_status: wrong code');
        $this->assertEquals($result['status'][7]->total, 2, 'fetch_misccat_status: wrong total');
        
        
        $this->assertEquals(count($result['list_recats']), 2, 'fetch_misccat_status: wrong count for list_recats');
        
        $this->assertEquals($result['list_recats'][0]->items, 2, 'fetch_misccat_status: wrong total for list_recats[0]');        
        $this->assertEquals($result['list_recats'][0]->top_opinion, 'cat1', 'fetch_misccat_status: wrong top_opinion for list_recats[0]');

        $this->assertEquals($result['list_recats'][1]->items, 1, 'fetch_misccat_status: wrong total for list_recats[1]');        
        $this->assertEquals($result['list_recats'][1]->top_opinion, 'Mobile', 'fetch_misccat_status: wrong top_opinion for list_recats[1]');
        
        $this->assertEquals(count($result['list_splits']), 1, 'fetch_misccat_status: wrong count for list_splits');

        $this->assertEquals($result['list_splits'][0]->iddevices, 6, 'fetch_misccat_status: wrong iddevices for list_splits[0]');
        $this->assertEquals($result['list_splits'][0]->code, '3', 'fetch_misccat_status: wrong code for list_splits[0]');
        $this->assertEquals($result['list_splits'][0]->adjudication, NULL, 'fetch_misccat_status: wrong adjudication for list_splits[0]');
        $this->assertEquals($result['list_splits'][0]->opinions, 'cat1,cat2,cat3', 'fetch_misccat_status: wrong opinions for list_splits[0]');
    }

    /** @test */
    public function update_misccat_devices() {

        $devices = 5;
        $this->_insert_misccat_devices($devices);

        factory(Category::class, 1)->states('misc')->create();
        factory(Category::class, 1)->states('cat1')->create();
        factory(Category::class, 1)->states('cat2')->create();
        factory(Category::class, 1)->states('cat3')->create();

        // 3 opinions with consensus for cat1
        $misccat = factory(Misccat::class, 3)->states('cat1')->create([
            'iddevices' => 1,
        ]);

        // 2 opinions with majority for cat2
        $misccat = factory(Misccat::class, 2)->states('cat2')->create([
            'iddevices' => 2,
        ]);

        // 1 opinion
        $misccat = factory(Misccat::class, 1)->states('cat3')->create([
            'iddevices' => 3,
        ]);

        // 3 opinions split
        $misccat = factory(Misccat::class, 1)->states('cat1')->create([
            'iddevices' => 4,
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat2')->create([
            'iddevices' => 4,
        ]);
        $misccat = factory(Misccat::class, 1)->states('cat3')->create([
            'iddevices' => 4,
        ]);

        // "new" category opinions
        $misccat = factory(Misccat::class, 2)->create([
            'iddevices' => 5,
            'category' => 'foo',
        ]);
        $misccat = factory(Misccat::class, 1)->create([
            'iddevices' => 5,
            'category' => 'bar',
        ]);

        $Misccat = new Misccat;
        $result = $Misccat->updateDevices();
        $this->assertDatabaseHas('devices', [
            'iddevices' => 1,
            'category' => 1,
            'category_creation' => 46,
        ]);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 2,
            'category' => 2,
            'category_creation' => 46,
        ]);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 3,
            'category' => 46,
            'category_creation' => 46,
        ]);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 4,
            'category' => 46,
            'category_creation' => 46,
        ]);
        $this->assertDatabaseHas('devices', [
            'iddevices' => 5,
            'category' => 46,
            'category_creation' => 46,
        ]);
    }

    protected function _insert_misccat_devices($num) {

        $device = factory(Device::class, $num)->states('misccat')->create();

        for ($i = 1; $i <= $num; $i++) {
            $this->assertDatabaseHas('devices', [
                'iddevices' => $i,
                'category' => 46,
                'category_creation' => 46,
            ]);
        }
    }

}
