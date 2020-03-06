<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class Misccat extends Model {

    protected $table = 'devices_misc_opinions';
    protected $dateFormat = 'Y-m-d H:i';
    protected $dates = ['created_at', 'updated_at'];
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['iddevices', 'category', 'eee', 'user_id', 'ip_address', 'session_id'];

    /**
     * Fetch a single random computer device record that has less than 3
     * existing opinions and a "Misc" category.
     * 
     * Not the most efficient query
     * 
     * @return array
     */
    public function fetchMisc() {
        return DB::select("
SELECT
d.`iddevices` as iddevices,
TRIM(c.`name`) as category,
TRIM(COALESCE(d.`brand`,'')) as brand,
TRIM(COALESCE(d.`model`,'')) as model,
TRIM(d.`problem`) as problem,
COUNT(o.`iddevices`) as opinions_count
FROM `devices` d
LEFT JOIN `categories` c ON c.`idcategories` = d.`category`
LEFT JOIN `devices_misc_opinions` o ON o.`iddevices` = d.`iddevices`
WHERE d.`category` = 46
AND LENGTH(TRIM(d.`problem`)) > 0
GROUP BY d.`iddevices`
HAVING opinions_count < 3
ORDER BY rand()
LIMIT 1;"
        );
    }

    /**
     * 
     *
     * @return mixed
     */
    public function fetchStatus() {

        $result = [];
        
        $result['total_devices'] = DB::select("
SELECT COUNT(DISTINCT d.iddevices) AS total
FROM devices d
WHERE d.category IN (46)
AND LENGTH(TRIM(d.problem)) > 0
");

        $result['total_opinions_3'] = DB::select("
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE (SELECT COUNT(o2.iddevices) FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.iddevices) = 3
");

        $result['total_opinions_2'] = DB::select("
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE (SELECT COUNT(o2.iddevices) FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.iddevices) = 2
");

        $result['total_opinions_1'] = DB::select("
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE (SELECT COUNT(o2.iddevices) FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.iddevices) = 1
");

        $result['total_opinions_0'] = DB::select("
SELECT COUNT(d.iddevices) AS total
FROM devices d
LEFT JOIN devices_misc_opinions o ON o.iddevices = d.iddevices
WHERE d.category IN (46)
AND LENGTH(TRIM(d.problem)) > 0
AND o.iddevices IS NULL
");

        $result['total_recats'] = DB::select("
SELECT COUNT(DISTINCT items) as total FROM
(SELECT
o.iddevices AS items,
(SELECT o1.category FROM devices_misc_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.category ORDER BY COUNT(o1.category) DESC LIMIT 1) AS top_crowd_opinion,
ROUND((SELECT COUNT(o2.category) as top_crowd_opinion_count FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.category ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.category) as all_votes FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.category) AS all_crowd_opinions_count
FROM devices_misc_opinions o
GROUP BY o.iddevices
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 50)
AND (top_crowd_opinion != 'Misc')
) AS results
");
        
        $result['list_recats'] = DB::select("
SELECT
d.iddevices,
(SELECT o1.category FROM devices_misc_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.category ORDER BY COUNT(o1.category) DESC LIMIT 1) AS top_crowd_opinion,
ROUND((SELECT COUNT(o2.category) as top_crowd_opinion_count FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.category ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.category) as all_votes FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.category) AS all_crowd_opinions_count,
GROUP_CONCAT(o.category ORDER BY o.category) as opinions
FROM devices_misc_opinions o
JOIN devices d ON o.iddevices = d.iddevices
GROUP BY d.iddevices
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 50)
AND (top_crowd_opinion != 'Misc')
ORDER BY all_crowd_opinions_count DESC, d.iddevices DESC
");
  
                $result['total_splits'] = DB::select("
SELECT COUNT(DISTINCT items) as total FROM
(SELECT
o.iddevices AS items,
ROUND((SELECT COUNT(o2.category) as top_crowd_opinion_count FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.category ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.category) as all_votes FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.category) AS all_crowd_opinions_count
FROM devices_misc_opinions o
GROUP BY o.iddevices
HAVING
(all_crowd_opinions_count = 3 AND top_crowd_opinion_percentage < 40)
) AS results
");
        
                $result['list_splits'] = DB::select("
SELECT
d.iddevices,
(SELECT o1.category FROM devices_misc_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.category ORDER BY COUNT(o1.category) DESC LIMIT 1) AS top_crowd_opinion,
ROUND((SELECT COUNT(o2.category) as top_crowd_opinion_count FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.category ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.category) as all_votes FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.category) AS all_crowd_opinions_count,
GROUP_CONCAT(o.category ORDER BY o.category) as opinions,
TRIM(COALESCE(d.`brand`,'')) as brand,
TRIM(COALESCE(d.`model`,'')) as model,
TRIM(d.`problem`) as problem
FROM devices_misc_opinions o
JOIN devices d ON o.iddevices = d.iddevices
GROUP BY d.iddevices
HAVING
(all_crowd_opinions_count = 3 AND top_crowd_opinion_percentage < 40)
ORDER BY all_crowd_opinions_count DESC, d.iddevices DESC
");
        
                $result['total_eee'] = DB::select("
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE o.eee = 1
");                
                                $result['total_non_eee'] = DB::select("
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE o.eee = 0
");
                                
                                $result['total_not_sure'] = DB::select("
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE o.eee = 2
");                

        return $result;
    }

    /**
     * Write the winning opinions to `devices`.`category`.
     * NOTE: need to convert strings to idcategories and handle new categories
     *
     * @return mixed
     */
    public function updateDevices() {

//        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS `devices_misc_temporary` AS (
//SELECT
//d.iddevices,
//COALESCE(ANY_VALUE(a.category),(SELECT o1.category FROM devices_misc_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.category ORDER BY COUNT(o1.category) DESC LIMIT 1)) AS winning_opinion,
//ANY_VALUE(a.category) AS adjudicated_opinion,
//ROUND((SELECT COUNT(o2.category) as top_crowd_opinion_count FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.category ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
//(SELECT COUNT(o2.category) as all_votes FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
//COUNT(o.category) AS all_crowd_opinions_count
//FROM devices d
//LEFT OUTER JOIN devices_misc_opinions o ON o.iddevices = d.iddevices
//LEFT OUTER JOIN devices_misc_adjudicated a ON a.iddevices = d.iddevices
//WHERE d.category IN (46)
//AND LENGTH(d.problem) > 0
//GROUP BY d.iddevices
//HAVING
//(all_crowd_opinions_count >= 2 AND top_crowd_opinion_percentage >= 60)
//OR adjudicated_opinion IS NOT NULL
//ORDER BY NULL
//        );");
//
//        DB::statement("ALTER TABLE `devices_misc_temporary` ADD PRIMARY KEY(`iddevices`);");
//
//        $result = DB::update("UPDATE devices d, devices_misc_opinions o 
//SET d.category = o.category
//WHERE d.iddevices = o.iddevices;");
//
//        DB::statement("DROP TEMPORARY TABLE IF EXISTS `devices_misc_temporary`");
        $result = 0;
        return $result;
    }

}
