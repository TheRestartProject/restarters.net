<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Misccat extends Model
{
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
     * The list of excluded iddevices is because those records have
     * useless problem text:
     * "Like all data in Y1, this data is an estimate based on participation"
     *
     * @return array
     */
    public function fetchMisc()
    {
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
AND d.iddevices NOT IN (1850,1851,1852,1854,1855,1856,1857,1858,1859,1862,1863,1864,1866,1867,1868,1869,1870,1871,1872,1873,1874,1875,1876,1877,1878,1879)
GROUP BY d.`iddevices`
HAVING opinions_count < 3
ORDER BY rand()
LIMIT 1;"
        );
    }

    /**
     * @return mixed
     */
    public function fetchStatus()
    {
        $result = [];

        $status_query = "
SELECT 
d.iddevices as `iddevices`,
TRIM(COALESCE(d.`brand`,'')) as brand,
TRIM(COALESCE(d.`model`,'')) as model,
TRIM(d.`problem`) as problem,
GROUP_CONCAT(o.category ORDER BY o.category ASC) as opinions,
ANY_VALUE(a.category) as adjudication,            
CASE 
WHEN (d.category <> 46 AND COUNT(ANY_VALUE(o.category)) = 0) THEN -2
WHEN (d.category <> 46 AND COUNT(ANY_VALUE(o.category)) > 0) THEN -1
WHEN (d.category = 46 AND COUNT(ANY_VALUE(o.category)) = 0) THEN 0
WHEN (d.category = 46 AND COUNT(ANY_VALUE(o.category)) = 1) THEN 1
WHEN (d.category = 46 AND COUNT(ANY_VALUE(o.category)) = 2 AND COUNT(DISTINCT ANY_VALUE(o.category)) = 2) THEN 2
WHEN (d.category = 46 AND (COUNT(DISTINCT ANY_VALUE(o.category)) >= 3) AND ANY_VALUE(a.category) IS NULL) THEN 3
WHEN (d.category = 46 AND (SUM(IF(ANY_VALUE(o.category) = 'Misc', 1, 0)) > 1 OR ANY_VALUE(a.category) = 'Misc')) THEN 4 
WHEN (d.category = 46 AND (COUNT(DISTINCT ANY_VALUE(o.category)) >= 3) AND ANY_VALUE(a.category) IS NOT NULL AND ANY_VALUE(a.category) <> 'Misc') THEN 5
WHEN (d.category = 46 AND (SUM(IF(ANY_VALUE(o.category) <> 'Misc', 1, 0)) > 1)) THEN 5
ELSE 99
END as `code`
FROM devices d
LEFT OUTER JOIN devices_misc_opinions o ON o.iddevices = d.iddevices
LEFT OUTER JOIN devices_misc_adjudicated a ON a.iddevices = d.iddevices
GROUP BY d.iddevices";

        try {
            $result['status'] = DB::select("
SELECT COUNT(*) as total,
`code`,
CASE `code` 
WHEN -2 THEN 'Original category was not Misc'
WHEN -1 THEN 'Category has been updated from Misc, thanks!'
WHEN 0 THEN 'Is Misc and has no opinions' 
WHEN 1 THEN 'Is Misc and has only one opinion' 
WHEN 2 THEN 'Is Misc and needs just one more opinion'
WHEN 3 THEN 'Is Misc and opinions are split, adjudication needed'
WHEN 4 THEN 'Is Misc and majority opinions agree it should remain as Misc, thanks!'
WHEN 5 THEN 'Is Misc and majority opinions say not Misc so it will be updated soon, thanks!'
ELSE '?'
END as `status` FROM ($status_query) AS `status_query`
GROUP BY `code`
");
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }

        try {
            $result['list_recats'] = DB::select("
SELECT
COUNT(d.iddevices) as items,
COALESCE(
(SELECT a.category FROM devices_misc_adjudicated a WHERE a.iddevices = d.iddevices),
(SELECT o.category FROM devices_misc_opinions o WHERE o.iddevices = d.iddevices 
GROUP BY o.category HAVING COUNT(o.category) > 1 
ORDER BY COUNT(o.category) DESC LIMIT 1)
) AS top_opinion
FROM devices d
GROUP BY top_opinion
HAVING top_opinion != '' AND top_opinion != 'Misc'
ORDER BY items DESC, top_opinion ASC
");
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }

        try {
            $result['list_splits'] = DB::select("$status_query HAVING `code` = 3");
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }

        $result['total_eee'] = DB::select('
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE o.eee = 1
');
        $result['total_non_eee'] = DB::select('
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE o.eee = 0
');

        $result['total_not_sure'] = DB::select('
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_misc_opinions o
WHERE o.eee = 2
');

        return $result;
    }

    /**
     * Write the winning opinions to `devices`.`category`.
     * NOTE: need to convert strings to idcategories and handle new categories
     *
     * @return mixed
     */
    public function updateDevices()
    {
        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS `devices_misc_temporary` AS (
SELECT
d.iddevices,
COALESCE(ANY_VALUE(a.category),(SELECT o1.category FROM devices_misc_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.category ORDER BY COUNT(o1.category) DESC LIMIT 1)) AS winning_opinion,
ANY_VALUE(a.category) AS adjudicated_opinion,
ROUND((SELECT COUNT(o2.category) as top_crowd_opinion_count FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.category ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.category) as all_votes FROM devices_misc_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.category) AS all_crowd_opinions_count,
0 AS idcategories
FROM devices d
LEFT OUTER JOIN devices_misc_opinions o ON o.iddevices = d.iddevices
LEFT OUTER JOIN devices_misc_adjudicated a ON a.iddevices = d.iddevices
WHERE d.category IN (46)
AND LENGTH(d.problem) > 0
GROUP BY d.iddevices
HAVING
adjudicated_opinion IS NOT NULL
OR (
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 50)
AND (winning_opinion != 'Misc')
)
ORDER BY NULL);");

        DB::statement('ALTER TABLE `devices_misc_temporary` ADD PRIMARY KEY(`iddevices`);');

        DB::update('UPDATE devices_misc_temporary t, categories c 
SET t.idcategories = c.idcategories
WHERE t.winning_opinion = c.`name`;');

        $result = DB::update('UPDATE devices d, devices_misc_temporary t 
SET d.category = t.idcategories
WHERE d.iddevices = t.iddevices AND t.idcategories > 0;');

        DB::statement('DROP TEMPORARY TABLE IF EXISTS `devices_misc_temporary`;');

        return $result;
    }
}
