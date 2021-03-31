<?php

namespace Tests\Feature;

use App\Device;
use App\Category;
use App\Group;
use DB;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExampleTest extends TestCase {

    public function setUp() {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Group::truncate();
        Device::truncate();
    }

    /** @test */
    public function test_this_1() {

        $data = $this->_setup_data();

        $sql = "SELECT
d.iddevices,
d.repair_status
FROM devices d
GROUP BY d.iddevices
HAVING
(repair_status = 1)
";
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS $sql");

        DB::statement("ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);");

        $result = DB::select("SELECT * FROM `test_temporary`");

        logger(print_r($result, 1));
        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");
    }

    /** @test */
    public function test_this_1a() {

        $data = $this->_setup_data();

        $sql = "SELECT
d.iddevices,
d.repair_status
FROM devices d
GROUP BY d.iddevices
HAVING
(repair_status = 1)
";
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS ($sql)");

        DB::statement("ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);");

        $result = DB::select("SELECT * FROM `test_temporary`");

        logger(print_r($result, 1));
        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");
    }

    /** @test */
    public function test_this_2() {

        $data = $this->_setup_data();

        $sql = "SELECT
d.iddevices,
d.repair_status
FROM devices d
GROUP BY d.iddevices
HAVING
(repair_status = 1)
UNION
SELECT
d.iddevices,
d.repair_status
FROM devices d
GROUP BY d.iddevices
HAVING
(repair_status = 2)
";
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS $sql");

        DB::statement("ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);");

        $result = DB::select("SELECT * FROM `test_temporary`");

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");
    }

    /** @test */
    public function test_this_2a() {

        $data = $this->_setup_data();

        $sql = "SELECT
d.iddevices,
d.repair_status
FROM devices d
GROUP BY d.iddevices
HAVING
(repair_status = 1)
UNION
SELECT
d.iddevices,
d.repair_status
FROM devices d
GROUP BY d.iddevices
HAVING
(repair_status = 2)
";
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS ($sql)");

        DB::statement("ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);");

        $result = DB::select("SELECT * FROM `test_temporary`");

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");
    }

    /** @test */
    public function test_this_3() {

        $data = $this->_setup_data();

        $sql = "
SELECT
r2.iddevices,
r2.repair_status
FROM
(SELECT
r1.iddevices,
r1.repair_status
FROM
(SELECT
d.iddevices,
d.repair_status
FROM devices d
) AS r1
GROUP BY r1.iddevices
HAVING
(r1.repair_status < 3)
) AS r2
GROUP BY r2.iddevices
HAVING
(r2.repair_status < 2)
";
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS $sql");

        DB::statement("ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);");

        $result = DB::select("SELECT * FROM `test_temporary`");

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");
    }

    /** @test */
    public function test_this_3a() {

        $data = $this->_setup_data();

        $sql = "
SELECT
r2.iddevices,
r2.repair_status
FROM
(SELECT
r1.iddevices,
r1.repair_status
FROM
(SELECT
d.iddevices,
d.repair_status
FROM devices d
) AS r1
GROUP BY r1.iddevices
HAVING
(r1.repair_status < 3)
) AS r2
GROUP BY r2.iddevices
HAVING
(r2.repair_status < 2)
";
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS ($sql)");

        DB::statement("ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);");

        $result = DB::select("SELECT * FROM `test_temporary`");

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement("DROP TABLE IF EXISTS `test_temporary`");
    }

    /**
     *
     * @return array
     */
    protected function _setup_data() {
        $cats = [
            'desktop' => 11,
            'laptop large' => 15,
            'laptop medium' => 16,
            'laptop small' => 17,
            'tablet' => 26,
        ];
        $devs = [];
        $i = 1;
        foreach ($cats as $cat => $id) {
            $devs[$i++] = $this->_insert_device($cat, $id, Str::random(40), 1);
            $devs[$i++] = $this->_insert_device($cat, $id, Str::random(40), 2);
            $devs[$i++] = $this->_insert_device($cat, $id, Str::random(40), 3);
        }
    }

    protected function _insert_device($cat, $id, $problem, $repair_status) {
        $device = factory(Device::class, 1)->states($cat)->create(
                [
                    'problem' => $problem,
                    'repair_status' => $repair_status
                ]
        );
        return $device->toArray()[0];
    }

}
