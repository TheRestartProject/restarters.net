<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use DB;
use Illuminate\Database\Eloquent\Model;

class Misccat extends Model
{
    use HasFactory;

    protected $table = 'devices_misc_opinions';
    protected $dateFormat = 'Y-m-d H:i';
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
     */
    public function fetchMisc(): array
    {
        return DB::select(
            "
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

        $select_splits = "
SELECT
o.iddevices as `iddevices`,
TRIM(COALESCE(d.`brand`,'')) as brand,
TRIM(COALESCE(d.`model`,'')) as model,
TRIM(d.`problem`) as problem,
COUNT(o.category) as opinions_count,
COUNT(DISTINCT o.category) as opinions_count_distinct,
IF(COUNT(DISTINCT o.category)=3,
(SELECT a.category FROM devices_misc_adjudicated a WHERE a.iddevices = o.iddevices),
GROUP_CONCAT(DISTINCT o.category ORDER BY o.category ASC)) as opinions_distinct,
GROUP_CONCAT(o.category ORDER BY o.category ASC) as opinions
FROM devices_misc_opinions o
JOIN devices d ON d.iddevices = o.iddevices
GROUP BY o.iddevices
HAVING opinions_count = 3 AND opinions_distinct IS NULL
";

        try {
            $result['list_splits'] = DB::select("$select_splits");
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
     * NOTE: need to convert strings to idcategories
     *
     * @return mixed
     */
    public function updateDevices()
    {
        $t2 = "
SELECT
o.iddevices,
COUNT(o.category) as opinions_count,
COUNT(DISTINCT o.category) as opinions_count_distinct,
GROUP_CONCAT(DISTINCT o.category ORDER BY o.category ASC) as opinions_distinct,
GROUP_CONCAT(o.category ORDER BY o.category ASC) as opinions,
(   SELECT a.category
        FROM devices_misc_adjudicated a
        WHERE a.iddevices = o.iddevices
) as `adjudicated`,
(
    SELECT category FROM (
        SELECT  iddevices, category
        FROM    (
                SELECT  @iddevices <> iddevices AS _new,
                        @iddevices := iddevices AS iddevices,
                        category, COUNT(*) AS cnt
                FROM    (
                        SELECT  @iddevices := ''
                        ) vars,
                        devices_misc_opinions
                GROUP BY
                iddevices, category
                ORDER BY
                category, cnt DESC
                ) q
        WHERE   _new
        ORDER BY iddevices
        ) t1
        WHERE t1.iddevices = o.iddevices
) as top_opinion
FROM devices_misc_opinions o
GROUP BY o.iddevices
";

        $t1 = "
SELECT t2.*,
0 as idcategories,
COALESCE(t2.adjudicated, t2.top_opinion) as winning_opinion
FROM ($t2) t2
WHERE
(t2.opinions_count >= 2 AND t2.opinions_count_distinct = 1)
OR
(t2.opinions_count = 3 AND t2.adjudicated IS NOT NULL AND t2.adjudicated <> 'Misc')
OR
(t2.opinions_count = 3 AND t2.opinions_count_distinct = 2 AND t2.top_opinion IS NOT NULL AND t2.top_opinion <> 'Misc')
";

        // logger($t1);
        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS `devices_misc_temporary` AS $t1;");
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
