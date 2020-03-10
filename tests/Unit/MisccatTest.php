<?php

namespace Tests\Unit;

use App\Device;
use App\Category;
use App\Misccat;
use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $this->insert_misccat_devices($devices);
        
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

        $devices = 5;
        $this->insert_misccat_devices($devices);

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

        $Misccat = new Misccat;
        $result = $Misccat->fetchStatus();

        $this->assertTrue(is_array($result));
        $this->assertTrue(array_key_exists('total_devices', $result), 'fetch_misccat_status: missing key - total_devices');
        $this->assertEquals($result['total_devices'][0]->total, 5);
        $this->assertTrue(array_key_exists('total_opinions_3', $result), 'fetch_misccat_status: missing key - total_opinions_3');
        $this->assertEquals($result['total_opinions_3'][0]->total, 2);
        $this->assertTrue(array_key_exists('total_opinions_2', $result), 'fetch_misccat_status: missing key - total_opinions_2');
        $this->assertEquals($result['total_opinions_2'][0]->total, 1);
        $this->assertTrue(array_key_exists('total_opinions_1', $result), 'fetch_misccat_status: missing key - total_opinions_1');
        $this->assertEquals($result['total_opinions_1'][0]->total, 1);
        $this->assertTrue(array_key_exists('total_opinions_0', $result), 'fetch_misccat_status: missing key - total_opinions_0');
        $this->assertEquals($result['total_opinions_0'][0]->total, 1);
        $this->assertTrue(array_key_exists('total_recats', $result), 'fetch_misccat_status: missing key - total_recats');
        $this->assertEquals($result['total_recats'][0]->total, 2);

        $this->assertEquals(count($result['list_recats']), 2, 'fetch_misccat_status: wrong count for list_recats');

        $this->assertEquals($result['list_recats'][0]->iddevices, 1, 'fetch_misccat_status: wrong iddevices for list_recats[0]');
        $this->assertEquals($result['list_recats'][0]->top_crowd_opinion, 'cat1', 'fetch_misccat_status: wrong top_crowd_opinion for list_recats[0]');
        $this->assertEquals($result['list_recats'][0]->top_crowd_opinion_percentage, 100, 'fetch_misccat_status: wrong top_crowd_opinion_percentage for list_recats[0]');
        $this->assertEquals($result['list_recats'][0]->all_crowd_opinions_count, 3, 'fetch_misccat_status: wrong all_crowd_opinions_count for list_recats[0]');
        $this->assertEquals($result['list_recats'][0]->opinions, 'cat1,cat1,cat1', 'fetch_misccat_status: wrong opinions for list_recats[0]');

        $this->assertEquals($result['list_recats'][1]->iddevices, 2, 'fetch_misccat_status: wrong iddevices for list_recats[1]');
        $this->assertEquals($result['list_recats'][1]->top_crowd_opinion, 'cat2', 'fetch_misccat_status: wrong top_crowd_opinion for list_recats[1]');
        $this->assertEquals($result['list_recats'][1]->top_crowd_opinion_percentage, 100, 'fetch_misccat_status: wrong top_crowd_opinion_percentage for list_recats[1]');
        $this->assertEquals($result['list_recats'][1]->all_crowd_opinions_count, 2, 'fetch_misccat_status: wrong all_crowd_opinions_count for list_recats[1]');
        $this->assertEquals($result['list_recats'][1]->opinions, 'cat2,cat2', 'fetch_misccat_status: wrong opinions for list_recats[1]');

        $this->assertTrue(array_key_exists('total_splits', $result), 'fetch_misccat_status: missing key - total_splits');
        $this->assertEquals($result['total_splits'][0]->total, 1);

        $this->assertEquals(count($result['list_splits']), 1, 'fetch_misccat_status: wrong count for list_splits');

        $this->assertEquals($result['list_splits'][0]->iddevices, 4, 'fetch_misccat_status: wrong iddevices for list_splits[0]');
        $this->assertEquals($result['list_splits'][0]->top_crowd_opinion_percentage, 33, 'fetch_misccat_status: wrong top_crowd_opinion_percentage for list_splits[0]');
        $this->assertEquals($result['list_splits'][0]->all_crowd_opinions_count, 3, 'fetch_misccat_status: wrong all_crowd_opinions_count for list_splits[0]');
        $this->assertEquals($result['list_splits'][0]->opinions, 'cat1,cat2,cat3', 'fetch_misccat_status: wrong opinions for list_splits[0]');
    }

    /** @test */
    public function update_misccat_devices() {

        $devices = 5;
        $this->insert_misccat_devices($devices);

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

    protected function insert_misccat_devices($num) {

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
