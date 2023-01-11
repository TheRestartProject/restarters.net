<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class BattcatOra extends Model
{
    protected $table = 'devices_faults_batteries_ora_opinions';
    protected $dateFormat = 'Y-m-d H:i';
    protected $primaryKey = 'id';

    protected $sql;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id_ords', 'fault_type_id', 'user_id', 'ip_address', 'session_id'];

    /**
     * Fetch a single random computer device record that has less than 3
     * existing opinions and a non-empty problem.
     *
     * Not the most efficient query
     *
     * @return array
     */
    public function fetchFault($exclusions = [], $locale = null)
    {
        $result = [];
        $records = DB::select('SELECT COUNT(*) as total FROM `devices_battcat_ora`');
        if ($records[0]->total > count($exclusions)) {
            // try once with locale, even if it is NULL
            $sql = $this->_getSQL($exclusions, $locale);
            $result = DB::select($sql);
            if (! $result) {
                // if no user-lang recs left, get one without locale
                $sql = $this->_getSQL($exclusions);
                $result = DB::select($sql);
            }
        }

        return $result;
    }

    protected function _getSQL($exclusions = [], $locale = null)
    {
        $sql = 'SELECT
d.`id_ords` as `id_ords`,
d.`data_provider` as `partner`,
d.`product_category` as `product_category`,
TRIM(d.`brand`) as `brand`,
d.`repair_status` as `repair_status`,
TRIM(d.`problem`) as `problem`,
d.`language` as `language`,
TRIM(d.`translation`) as `translation`,
COUNT(o.`id_ords`) as `opinions`,
COUNT(DISTINCT o.fault_type_id) as `faults`
FROM `devices_battcat_ora` d
LEFT JOIN `devices_faults_batteries_ora_opinions` o ON o.`id_ords` = d.`id_ords`
WHERE 1 %s
GROUP BY d.`id_ords`
HAVING (`opinions` = 2 AND `faults` = 2)
OR (`opinions` < 2)
ORDER BY rand()
LIMIT 1;
';
        $and = '';
        if (! empty($exclusions)) {
            $ids = implode("','", $exclusions);
            $and .= "\nAND d.`id_ords` NOT IN ('$ids')";
        }
        if (! is_null($locale)) {
            $and .= "\nAND (d.`language` = '$locale')";
        }
        $sql = sprintf($sql, $and);

        return $sql;
    }

    /**
     * Fetch all fault_type_ids for battcat
     *
     * @return array
     */
    public function fetchFaultTypes($repair_status)
    {
        $sql = "SELECT * FROM `fault_types_batteries` WHERE `repair_status` = '$repair_status'";

        return DB::select($sql);
    }

    /**
     * @return array
     */
    public function fetchProgress()
    {
        $result = DB::select('
SELECT
ROUND((r2.opinions/r2.batteries)*100,1) as total
FROM (
SELECT
COUNT(*) AS opinions,
(SELECT COUNT(*) FROM devices_battcat_ora) as batteries
FROM (
SELECT
o.id_ords,
(SELECT a.fault_type_id FROM devices_faults_batteries_ora_adjudicated a WHERE a.id_ords = o.id_ords) AS adjudicated_opinion_id,
(SELECT o1.fault_type_id FROM devices_faults_batteries_ora_opinions o1 WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1) AS winning_opinion_id,
ROUND((SELECT COUNT(o3.fault_type_id) as top_crowd_opinion_count FROM devices_faults_batteries_ora_opinions o3 WHERE o3.id_ords = o.id_ords GROUP BY o3.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o4.fault_type_id) as all_votes FROM devices_faults_batteries_ora_opinions o4 WHERE o4.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count
FROM devices_faults_batteries_ora_opinions o
GROUP BY o.id_ords
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
OR
(all_crowd_opinions_count = 3 AND top_crowd_opinion_percentage < 60 AND adjudicated_opinion_id IS NOT NULL)
) AS r1
) AS r2
');

        return $result;
    }

    /**
     * @return array
     */
    public function fetchStatus()
    {
        $result = [];

        $result['total_devices'] = DB::select('
SELECT COUNT(DISTINCT d.id_ords) AS total
FROM `devices_battcat_ora` d
');

        $result['total_opinions'] = DB::select('
SELECT COUNT(*) AS total
FROM devices_faults_batteries_ora_opinions o
');

        $result['progress'] = $this->fetchProgress();

        $result['list_recats'] = DB::select('
SELECT
result.winning_opinion_id,
fta.title as winning_opinion,
result.repair_status,
COUNT(*) AS total
FROM (
SELECT
o.id_ords,
d.repair_status,
(SELECT o1.fault_type_id FROM devices_faults_batteries_ora_opinions o1 WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1) AS winning_opinion_id,
ROUND((SELECT COUNT(o3.fault_type_id) as top_crowd_opinion_count FROM devices_faults_batteries_ora_opinions o3 WHERE o3.id_ords = o.id_ords GROUP BY o3.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o4.fault_type_id) as all_votes FROM devices_faults_batteries_ora_opinions o4 WHERE o4.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count
FROM devices_faults_batteries_ora_opinions o
JOIN devices_battcat_ora d ON d.id_ords = o.id_ords
GROUP BY o.id_ords, d.repair_status
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
UNION
SELECT
a.id_ords,
d.repair_status,
a.fault_type_id AS winning_opinion_id,
100 AS top_crowd_opinion_percentage,
3 AS all_crowd_opinions_count
FROM devices_faults_batteries_ora_adjudicated a
JOIN devices_battcat_ora d ON d.id_ords = a.id_ords
) AS result
JOIN fault_types_batteries fta ON fta.id = result.winning_opinion_id
GROUP BY result.repair_status, result.winning_opinion_id
ORDER BY total DESC
');

        $result['total_recats'] = [new \stdClass()];
        $result['total_recats'][0]->total = 0;
        foreach ($result['list_recats'] as $v) {
            $result['total_recats'][0]->total += $v->total;
        }

        return $result;
    }

    /**
     * Write the winning opinions to `devices_battcat_ora`.`fault_type`.
     *
     * @return mixed
     */
    public function updateDevices()
    {
        DB::statement('CREATE TEMPORARY TABLE IF NOT EXISTS `devices_faults_batteries_ora_temporary` AS
SELECT *
FROM
(SELECT
r2.id_ords,
CASE
        WHEN (r2.opinions<2) OR (r2.opinions=2 AND r2.opinions_distinct=2) THEN NULL
        WHEN (r2.opinions_distinct=1 AND r2.opinions>1) THEN r2.faultnames
        WHEN (r2.opinions_distinct=3) THEN ((SELECT a.fault_type_id FROM devices_faults_batteries_ora_adjudicated a WHERE a.id_ords = r2.id_ords))
        ELSE (SELECT o.fault_type_id FROM devices_faults_batteries_ora_opinions o WHERE o.id_ords = r2.id_ords GROUP BY fault_type_id ORDER BY COUNT(*) DESC LIMIT 1)
END AS winning_opinion_id
FROM
(SELECT
r1.id_ords,
COUNT(r1.fault_type_id) as opinions,
COUNT(DISTINCT r1.fault_type_id) as opinions_distinct,
GROUP_CONCAT(DISTINCT r1.fault_type_id) as faultnames
FROM
(SELECT
o.id_ords,
o.fault_type_id
FROM devices_faults_batteries_ora_opinions o
) AS r1
GROUP BY r1.id_ords
) AS r2
) AS r3
WHERE r3.winning_opinion_id IS NOT NULL
');
        DB::statement('ALTER TABLE `devices_faults_batteries_ora_temporary` ADD PRIMARY KEY(`id_ords`);');

        $result = DB::update('UPDATE devices_battcat_ora d, devices_faults_batteries_ora_temporary t
SET d.fault_type_id = t.winning_opinion_id
WHERE d.id_ords = t.id_ords;');

        DB::statement('DROP TEMPORARY TABLE IF EXISTS `devices_faults_batteries_ora_temporary`');

        return $result;
    }
}
