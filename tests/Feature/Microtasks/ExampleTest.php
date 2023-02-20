<?php

namespace Tests\Feature;

use App\Device;
use App\Group;
use App\Party;
use DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Miscellaneous routines for checking stuff
 * Do not run in suite as some are designed to fail
 */
class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('SET foreign_key_checks=0');
        Party::truncate();
        Group::truncate();
        Device::truncate();
    }

    public function sql_not_in()
    {
        // testing execution time with enormous "NOT IN" expression
        DB::table('groups')->insert([
            'name' => Str::random(40),
        ]);
        DB::table('events')->insert([
            'group' => 1,
        ]);
        for ($i = 1; $i <= 1000; $i++) {
            DB::table('devices')->insert([
                'event' => 1,
                'category' => rand(1, 46),
                'problem' => Str::random(40),
                'repair_status' => rand(1, 3),
            ]);
        }
        $x = array_keys(array_fill(1, 1000, 0));
        shuffle($x);
        $t = rand(1, 999);
        // logger($t);
        $r = str_replace("$t,", '', implode(',', $x));
        $sql = "SELECT iddevices FROM devices WHERE iddevices NOT IN ($r) ORDER BY rand() LIMIT 1";
        // logger($sql);
        for ($i = 1; $i <= 10; $i++) {
            $foo = DB::select($sql); // (0.03 seconds)
            // logger(print_r($foo, 1));
        }
    }

    /**
     * The following prove an issue with Laravel DB::statement under MySQL 5.7
     * when brackets enclose a complex SELECT statement in CREATE TABLE AS (SELECT...)
     * create_table_2a will always fail under MySQL 5.7 but does not fail under MySQL 8.x
     * create_table_3a is intended to fail under MySQL 5.7 but needs more complex query
     * e.g. see MobifixOra->updateDevices();
     */
    public function create_table_1()
    {
        $data = $this->_setup_data();

        $sql = 'SELECT
d.iddevices,
d.repair_status
FROM devices d
GROUP BY d.iddevices
HAVING
(repair_status = 1)
';
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS $sql");

        DB::statement('ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);');

        $result = DB::select('SELECT * FROM `test_temporary`');

        logger(print_r($result, 1));
        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');
    }

    public function create_table_1a()
    {
        $data = $this->_setup_data();

        $sql = 'SELECT
d.iddevices,
d.repair_status
FROM devices d
GROUP BY d.iddevices
HAVING
(repair_status = 1)
';
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS ($sql)");

        DB::statement('ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);');

        $result = DB::select('SELECT * FROM `test_temporary`');

        logger(print_r($result, 1));
        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');
    }

    public function create_table_2()
    {
        $data = $this->_setup_data();

        $sql = 'SELECT
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
';
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS $sql");

        DB::statement('ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);');

        $result = DB::select('SELECT * FROM `test_temporary`');

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');
    }

    public function create_table_2a()
    {
        $data = $this->_setup_data();

        $sql = 'SELECT
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
';
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS ($sql)");

        DB::statement('ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);');

        $result = DB::select('SELECT * FROM `test_temporary`');

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');
    }

    public function create_table_3()
    {
        $data = $this->_setup_data();

        $sql = '
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
';
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS $sql");

        DB::statement('ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);');

        $result = DB::select('SELECT * FROM `test_temporary`');

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');
    }

    public function create_table_3a()
    {
        $data = $this->_setup_data();

        $sql = '
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
';
        $result = DB::select($sql);

        logger(print_r($result, 1));

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');

        DB::statement("CREATE TABLE IF NOT EXISTS `test_temporary` AS ($sql)");

        DB::statement('ALTER TABLE `test_temporary` ADD PRIMARY KEY(`iddevices`);');

        $result = DB::select('SELECT * FROM `test_temporary`');

        $this->assertTrue(is_array($result));

        DB::statement('DROP TABLE IF EXISTS `test_temporary`');
    }

    /**
     * @return array
     */
    protected function _setup_data()
    {
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
            $devs[$i++] = $this->_insert_device($cat, Str::random(40), 1);
            $devs[$i++] = $this->_insert_device($cat, Str::random(40), 2);
            $devs[$i++] = $this->_insert_device($cat, Str::random(40), 3);
        }
    }

    protected function _insert_device($cat, $problem, $repair_status)
    {
        $device = Device::factory()->count(1)->cat()->create(
                [
                    'problem' => $problem,
                    'repair_status' => $repair_status,
                ]
        );

        return $device->toArray()[0];
    }
}
