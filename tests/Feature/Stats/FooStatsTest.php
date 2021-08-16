<?php

namespace Tests\Feature;

use DB;
use App\Group;
use App\Party;
use App\Device;
use App\DeviceBarrier;
use App\Category;
use Tests\TestCase;

/**
 * NOT SO MUCH A TEST
 * AS A MEANS OF DETERMINING EMISSION RATIOS
 * GIVEN DATASETS BASED ON FIXOMETER DEVICES DATA
 * SEE LARAVEL.LOG FOR OUTPUT
 *
 *
 * Query for finding group events with both
 * powered and powered items
SELECT
g.idgroups,
e.idevents,
e.wordpress_post_id
FROM (
SELECT
d.event
FROM devices d
JOIN categories c ON c.idcategories = d.category
WHERE d.event > 2100
AND d.repair_status = 1
GROUP BY d.event
HAVING COUNT(DISTINCT c.powered) = 2
) t1
JOIN events e ON e.idevents = t1.`event`
JOIN groups g ON g.idgroups = e.`group`
WHERE e.wordpress_post_id IS NOT NULL
 *
 */
class FooStatsTest extends TestCase
{
    private $_records = 10000;
    private $_iterations = 1;
    private $_data = [];

    protected function setUp(): void
    {
        parent::setUp();
        DB::statement("SET foreign_key_checks=0");
        Party::truncate();
        Group::truncate();
        Device::truncate();
        DeviceBarrier::truncate();
        Category::truncate();
        DB::statement("SET foreign_key_checks=1");

        $this->_setupCategories();
        $this->event = factory(Party::class)->create();
    }

    public function emission_ratios()
    {
        $results = ['powered' => [], 'unpowered' => []];
        logger("**** ITERATIONS: $this->_iterations ****");
        for ($i = 1; $i <= $this->_iterations; $i++) {
            $this->_clearDeviceRecords();
            $this->_insertDevicesDistrib($this->_records, 'powered');
            $this->_insertDevicesDistrib($this->_records, 'unpowered');
            $result = \App\Helpers\LcaStats::calculatetEmissionRatioPowered();
            $results['powered'][] = $result;
            $result = \App\Helpers\LcaStats::calculatetEmissionRatioUnpowered();
            $results['unpowered'][] = $result;
        }
        logger("**** POWERED EMISSION RATIO ****");
        sort($results['powered']);
        logger($results['powered'][0]);

        logger("**** UNPOWERED EMISSION RATIO ****");
        sort($results['unpowered']);
        logger($results['unpowered'][0]);
        logger("**** DATA ****");
        logger(print_r($this->_data,1));
    }

    private function _clearDeviceRecords()
    {
        DB::statement("SET foreign_key_checks=0");
        Device::truncate();
        DB::statement("SET foreign_key_checks=1");
    }

    private function _insertDevicesDistrib($number, $type)
    {
        $distrib = $this->_getCategoryDistribution();
        $st = "INSERT INTO `devices` (`iddevices`, `event`, `category`, `category_creation`, `estimate`, `repair_status`, `problem`, `created_at`) VALUES ";
        foreach ($distrib[$type] as $cat => $dist) {
            $n = round(($number * $dist[1])/100);
            $this->_data[$cat]['name'] = $dist[0];
            $this->_data[$cat][$type . '_total'] = $number;
            $this->_data[$cat]['records'] = $n;
            $this->_data[$cat]['percent'] = $dist[1];
            $sql = $st;
            for ($i = 0; $i < $n; $i++) {
                $sql .= "\n(NULL, '1', $cat, $cat, NULL, '1', '', DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(3660 + RAND()*(1 - 3660 + 1)) DAY)),";
            }
            DB::statement(DB::raw(rtrim($sql, ',')));
        }
    }

    private function _getCategoryDistribution()
    {
        // category,records,percent
        $result['unpowered'] = [
            49 => ['Clothing/textile',73.8019],
            48 => ['Bicycle',18.5304],
            47 => ['Furniture',7.6677],
        ];

        $result['powered'] = [
            16 => ['Laptop medium',14.7751],
            42 => ['Small kitchen item',9.9842],
            25 => ['Mobile',9.0820],
            40 => ['Lamp',9.0820],
            32 => ['Portable radio',5.6077],
            30 => ['Hi-Fi separates',4.5959],
            45 => ['Vacuum',4.4374],
            41 => ['Power tool',4.1083],
            26 => ['Tablet',3.4378],
            24 => ['Headphones',3.3281],
            38 => ['Hair & Beauty item',3.1086],
            44 => ['Toy',2.9867],
            34 => ['TV and gaming-related accessories',2.5235],
            43 => ['Toaster',2.4503],
            19 => ['PC Accessory',2.2797],
            36 => ['Decorative or safety lights',2.2553],
            11 => ['Desktop computer',1.8286],
            29 => ['Hi-Fi integrated',1.8042],
            20 => ['Printer/scanner',1.5970],
            23 => ['Handheld entertainment device',1.5848],
            39 => ['Kettle',1.5604],
            37 => ['Fan',1.2800],
            17 => ['Laptop small',1.0728],
            15 => ['Laptop large',1.0484],
            31 => ['Musical instrument',0.9143],
            18 => ['Paper shredder',0.8290],
            21 => ['Digital Compact Camera',0.6949],
            35 => ['Aircon/Dehumidifier',0.4511],
            33 => ['Projector',0.3170],
            28 => ['Flat screen 32-37"',0.2316],
            22 => ['DLSR / Video Camera',0.2194],
            27 => ['Flat screen 26-30"',0.1829],
            12 => ['Flat screen 15-17"',0.1585],
            13 => ['Flat screen 19-20"',0.1097],
            14 => ['Flat screen 22-24"',0.0731],

        ];
        return $result;
    }

    private function _setupCategories()
    {
        $sql = "INSERT INTO `categories` (`idcategories`, `name`, `powered`, `weight`, `footprint`, `footprint_reliability`, `lifecycle`, `lifecycle_reliability`, `extendend_lifecycle`, `extendend_lifecycle_reliability`, `revision`, `cluster`, `aggregate`) VALUES
(11, 'Desktop computer', 1, 9.15, 398.4, 5, NULL, NULL, NULL, NULL, 1, 1, 0),
(12, 'Flat screen 15-17\"', 1, 2.7, 72.4, 2, NULL, NULL, NULL, NULL, 1, 1, 0),
(13, 'Flat screen 19-20\"', 1, 3.72, 102.93, 5, NULL, NULL, NULL, NULL, 1, 1, 0),
(14, 'Flat screen 22-24\"', 1, 5, 167.8, 5, NULL, NULL, NULL, NULL, 1, 1, 0),
(15, 'Laptop large', 1, 2.755, 322.79, 5, NULL, NULL, NULL, NULL, 1, 1, 0),
(16, 'Laptop medium', 1, 2.26, 258.25, 5, NULL, NULL, NULL, NULL, 1, 1, 0),
(17, 'Laptop small', 1, 2.14, 142.18, 4, NULL, NULL, NULL, NULL, 1, 1, 0),
(18, 'Paper shredder', 1, 7, 47.7, 2, NULL, NULL, NULL, NULL, 1, 1, 0),
(19, 'PC Accessory', 1, 1.185, 18.87, 4, NULL, NULL, NULL, NULL, 1, 1, 1),
(20, 'Printer/scanner', 1, 7.05, 47.7, 4, NULL, NULL, NULL, NULL, 1, 1, 0),
(21, 'Digital Compact Camera', 1, 0.113, 6.13, 4, NULL, NULL, NULL, NULL, 1, 2, 0),
(22, 'DLSR / Video Camera', 1, 0.27, 4.05, 4, NULL, NULL, NULL, NULL, 1, 2, 0),
(23, 'Handheld entertainment device', 1, 0.149, 13, 4, NULL, NULL, NULL, NULL, 1, 2, 1),
(24, 'Headphones', 1, 0.26, 4.05, 3, NULL, NULL, NULL, NULL, 1, 2, 0),
(25, 'Mobile', 1, 0.14, 35.82, 4, NULL, NULL, NULL, NULL, 1, 2, 0),
(26, 'Tablet', 1, 0.51, 107.76, 5, NULL, NULL, NULL, NULL, 1, 2, 0),
(27, 'Flat screen 26-30\"', 1, 10.6, 284.25, 1, NULL, NULL, NULL, NULL, 1, 3, 0),
(28, 'Flat screen 32-37\"', 1, 18.7, 359.4, 3, NULL, NULL, NULL, NULL, 1, 3, 0),
(29, 'Hi-Fi integrated', 1, 10.9, 109.5, 3, NULL, NULL, NULL, NULL, 1, 3, 1),
(30, 'Hi-Fi separates', 1, 10.9, 109.5, 4, NULL, NULL, NULL, NULL, 1, 3, 1),
(31, 'Musical instrument', 1, 10.9, 109.5, 3, NULL, NULL, NULL, NULL, 1, 3, 1),
(32, 'Portable radio', 1, 2.5, 66, 2, NULL, NULL, NULL, NULL, 1, 3, 0),
(33, 'Projector', 1, 2.35, 46, 4, NULL, NULL, NULL, NULL, 1, 3, 0),
(34, 'TV and gaming-related accessories', 1, 1, 25, 4, NULL, NULL, NULL, NULL, 1, 3, 1),
(35, 'Aircon/Dehumidifier', 1, 18.5, 109.53, 2, NULL, NULL, NULL, NULL, 1, 4, 0),
(36, 'Decorative or safety lights', 1, 0.015, 13.43, 4, NULL, NULL, NULL, NULL, 1, 4, 1),
(37, 'Fan', 1, 0.88, 4.52, 2, NULL, NULL, NULL, NULL, 1, 4, 0),
(38, 'Hair & Beauty item', 1, 0.69, 6, 4, NULL, NULL, NULL, NULL, 1, 4, 1),
(39, 'Kettle', 1, 1.4, 17.1, 4, NULL, NULL, NULL, NULL, 1, 4, 0),
(40, 'Lamp', 1, 0.703, 4.62, 2, NULL, NULL, NULL, NULL, 1, 4, 0),
(41, 'Power tool', 1, 2.84, 26.6, 3, NULL, NULL, NULL, NULL, 1, 4, 1),
(42, 'Small kitchen item', 1, 2.7, 15.8, 4, NULL, NULL, NULL, NULL, 1, 4, 1),
(43, 'Toaster', 1, 1.04, 5, 2, NULL, NULL, NULL, NULL, 1, 4, 0),
(44, 'Toy', 1, 1.27, 15, 4, NULL, NULL, NULL, NULL, 1, 4, 1),
(45, 'Vacuum', 1, 7.78, 41, 4, NULL, NULL, NULL, NULL, 1, 4, 0),
(46, 'Misc', 1, 0, 0, NULL, NULL, NULL, NULL, NULL, 1, NULL, 1),
(47, 'Furniture', 0, 29.81, 67.13, NULL, NULL, NULL, NULL, NULL, 1, 5, 0),
(48, 'Bicycle', 0, 15.1, 149.6, NULL, NULL, NULL, NULL, NULL, 1, 5, 0),
(49, 'Clothing/textile', 0, 0.75, 20.32, NULL, NULL, NULL, NULL, NULL, 1, 5, 0),
(50, 'Misc', 0, 0, 0, NULL, NULL, NULL, NULL, NULL, 1, 5, 1)";

        DB::statement(DB::raw($sql));
    }

    private function _logDevices()
    {
        $query = "SELECT c.`name` as category, COUNT(*) as records FROM devices d JOIN categories c ON c.idcategories = d.category GROUP BY d.category";
        $result = DB::select(DB::raw($query));
        if (empty($this->_data)) {
            foreach ($result as $v) {
                $this->_data[$v->category] = [];
            }
        }
        foreach ($result as $v) {
            $this->_data[$v->category][] = $v->records;
        }
    }

    private function _findMean(&$a, $n)
    {
        $sum = 0;
        for ($i = 0; $i < $n; $i++)
            $sum += $a[$i];

        return (float)$sum /
            (float)$n;
    }

    private function _findMedian(&$a, $n)
    {
        sort($a);
        if ($n % 2 != 0)
            return (float)$a[$n / 2];

        return (float)($a[($n - 1) / 2] +
            $a[$n / 2]) / 2.0;
    }

    private function _insertDevicesPoweredRandom($number, $misc = null)
    {
        $st = "INSERT INTO `devices` (`iddevices`, `event`, `category`, `category_creation`, `estimate`, `repair_status`, `problem`, `created_at`) VALUES ";
        if (!$misc) {
            $sql = $st;
            for ($i = 0; $i < $number; $i++) {
                $sql .= "\n(NULL, '1', FLOOR(46 + RAND()*(10 - 46 + 1)), 46, NULL, '1', '', DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(3660 + RAND()*(1 - 3660 + 1)) DAY)),";
            }
            DB::statement(DB::raw(rtrim($sql, ',')));
        } else {
            // Powered Misc with estimate >= 1 -- Fixo ranges from 0.02 to 20 with a handful of outliers > 20
            $sql = $st;
            for ($i = 0; $i < $number / 2; $i++) {
                $sql .= "\n(NULL, '1', 46, 46, ROUND(FLOOR(20 + RAND()*(1 - 20 + 1)) + RAND(),3), '1', '', DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(3660 + RAND()*(1 - 3660 + 1)) DAY)),";
            }
            DB::statement(DB::raw(rtrim($sql, ',')));

            // Powered Misc with estimate < 1 -- Fixo ranges from 0.02 to 20 with a handful of outliers > 20
            $sql = $st;
            for ($i = 0; $i < $number / 2; $i++) {
                $sql .= "\n(NULL, '1', 46, 46, ROUND(RAND(),3), '1', '', DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(3660 + RAND()*(1 - 3660 + 1)) DAY)),";
            }
            DB::statement(DB::raw(rtrim($sql, ',')));
        }
    }

    private function _insertDevicesUnpoweredRandom($number, $misc = null)
    {
        $st = "INSERT INTO `devices` (`iddevices`, `event`, `category`, `category_creation`, `estimate`, `repair_status`, `problem`, `created_at`) VALUES ";
        if (!$misc) {
            $sql = $st;
            for ($i = 0; $i < $number; $i++) {
                $sql .= "\n(NULL, '1', FLOOR(50 + RAND()*(45 - 49 + 1)), 50, NULL, '1', '', DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(3660 + RAND()*(1 - 3660 + 1)) DAY)),";
            }
            DB::statement(DB::raw(rtrim($sql, ',')));
        } else {
            // Unpowered Misc with estimate >= 1 -- Fixo ranges from 0.02 to 7
            $sql = $st;
            for ($i = 0; $i < $number / 2; $i++) {
                $sql .= "\n(NULL, '1', 50, 50, ROUND(FLOOR(7 + RAND()*(1 - 7 + 1)) + RAND(),3), '1', '', DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(3660 + RAND()*(1 - 3660 + 1)) DAY)),";
            }
            DB::statement(DB::raw(rtrim($sql, ',')));

            // Unpowered Misc with estimate < 1 -- Fixo ranges from 0.02 to 7
            $sql = $st;
            for ($i = 0; $i < $number / 2; $i++) {
                $sql .= "\n(NULL, '1', 50, 50, ROUND(RAND(),3), '1', '', DATE_SUB(CURRENT_TIMESTAMP, INTERVAL FLOOR(3660 + RAND()*(1 - 3660 + 1)) DAY)),";
            }
            DB::statement(DB::raw(rtrim($sql, ',')));
        }
    }
}
