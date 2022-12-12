<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

class PrintcatOra extends Model
{
    protected $table = 'devices_faults_printers_ora_opinions';
    protected $dateFormat = 'Y-m-d H:i';
    protected $primaryKey = 'id';

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
        $records = DB::select('SELECT COUNT(*) as total FROM `devices_printcat_ora`');
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
d.`id_ords` as id_ords,
d.`data_provider` as partner,
TRIM(d.`brand`) as brand,
d.`repair_status` as repair_status,
TRIM(d.`problem`) as problem,
d.`language` as language,
TRIM(d.`translation`) as translation,
COUNT(o.`id_ords`) as opinions_count
FROM `devices_printcat_ora` d
LEFT JOIN `devices_faults_printers_ora_opinions` o ON o.`id_ords` = d.`id_ords`
WHERE 1 %s
GROUP BY d.`id_ords`
HAVING opinions_count < 3
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
     * Fetch all fault_type_ids for printcat
     *
     * @return array
     */
    public function fetchFaultTypes()
    {
        return DB::select('SELECT * FROM `fault_types_printers`');
    }

    /**
     * @return mixed
     */
    public function fetchStatus($partner = null)
    {
        $result = [];

        $result['total_devices'] = DB::select('
SELECT COUNT(*) AS total
FROM `devices_printcat_ora` d
');

        $result['total_opinions_3'] = DB::select('
SELECT COUNT(DISTINCT o.id_ords) AS total
FROM devices_faults_printers_ora_opinions o
WHERE (SELECT COUNT(o2.id_ords) FROM devices_faults_printers_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.id_ords) = 3
');

        $result['total_opinions_2'] = DB::select('
SELECT COUNT(DISTINCT o.id_ords) AS total
FROM devices_faults_printers_ora_opinions o
WHERE (SELECT COUNT(o2.id_ords) FROM devices_faults_printers_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.id_ords) = 2
');

        $result['total_opinions_1'] = DB::select('
SELECT COUNT(DISTINCT o.id_ords) AS total
FROM devices_faults_printers_ora_opinions o
WHERE (SELECT COUNT(o2.id_ords) FROM devices_faults_printers_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.id_ords) = 1
');

        $result['total_opinions_0'] = DB::select('
SELECT COUNT(d.id_ords) AS total
FROM devices_printcat_ora d
LEFT JOIN devices_faults_printers_ora_opinions o ON o.id_ords = d.id_ords
WHERE o.id_ords IS NULL
');

        $result['list_recats'] = DB::select('
SELECT result.winning_opinion_id, fta.title as winning_opinion, COUNT(*) AS total FROM (
SELECT
o.id_ords,
(SELECT o1.fault_type_id FROM devices_faults_printers_ora_opinions o1 WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1) AS winning_opinion_id,
ROUND((SELECT COUNT(o3.fault_type_id) as top_crowd_opinion_count FROM devices_faults_printers_ora_opinions o3 WHERE o3.id_ords = o.id_ords GROUP BY o3.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o4.fault_type_id) as all_votes FROM devices_faults_printers_ora_opinions o4 WHERE o4.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count
FROM devices_faults_printers_ora_opinions o
GROUP BY o.id_ords
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
UNION
SELECT
a.id_ords,
a.fault_type_id AS winning_opinion_id,
100 AS top_crowd_opinion_percentage,
3 AS all_crowd_opinions_count
FROM devices_faults_printers_ora_adjudicated a
) AS result
LEFT JOIN fault_types_printers fta ON fta.id = result.winning_opinion_id
GROUP BY winning_opinion_id
ORDER BY total DESC
');

        $result['total_recats'] = [new \stdClass()];
        $result['total_recats'][0]->total = 0;
        foreach ($result['list_recats'] as $v) {
            $result['total_recats'][0]->total += $v->total;
        }

        $result['list_splits'] = DB::select('
SELECT
d.id_ords,
(SELECT o1.fault_type_id FROM devices_faults_printers_ora_opinions o1 WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1) AS winning_opinion_id,
ROUND((SELECT COUNT(o3.fault_type_id) as top_crowd_opinion_count FROM devices_faults_printers_ora_opinions o3 WHERE o3.id_ords = o.id_ords GROUP BY o3.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o4.fault_type_id) as all_votes FROM devices_faults_printers_ora_opinions o4 WHERE o4.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count,
GROUP_CONCAT(ft.title ORDER BY ft.title) as opinions,
d.brand as brand,
d.problem as problem
FROM `devices_printcat_ora` d
LEFT OUTER JOIN devices_faults_printers_ora_opinions o ON o.id_ords = d.id_ords
LEFT OUTER JOIN fault_types_printers ft ON ft.id = o.fault_type_id
WHERE (SELECT a.id_ords FROM devices_faults_printers_ora_adjudicated a WHERE a.id_ords = d.id_ords) IS NULL
GROUP BY d.id_ords
HAVING
(all_crowd_opinions_count = 3 AND top_crowd_opinion_percentage < 60)
');
        $result['total_splits'] = [json_decode(json_encode(['total' => count($result['list_splits'])]), false)];

        return $result;
    }

    /**
     * Write the winning opinions to `devices_printcat_ora`.`fault_type`.
     *
     * @return mixed
     */
    public function updateDevices()
    {
        DB::statement('CREATE TEMPORARY TABLE IF NOT EXISTS `devices_faults_printers_ora_temporary` AS
SELECT *
FROM
(SELECT
r2.id_ords,
CASE
        WHEN (r2.opinions<2) OR (r2.opinions=2 AND r2.opinions_distinct=2) THEN NULL
        WHEN (r2.opinions_distinct=1 AND r2.opinions>1) THEN r2.faultnames
        WHEN (r2.opinions_distinct=3) THEN ((SELECT a.fault_type_id FROM devices_faults_printers_ora_adjudicated a WHERE a.id_ords = r2.id_ords))
        ELSE (SELECT o.fault_type_id FROM devices_faults_printers_ora_opinions o WHERE o.id_ords = r2.id_ords GROUP BY fault_type_id ORDER BY COUNT(*) DESC LIMIT 1)
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
FROM devices_faults_printers_ora_opinions o
) AS r1
GROUP BY r1.id_ords
) AS r2
) AS r3
WHERE r3.winning_opinion_id IS NOT NULL
');
        DB::statement('ALTER TABLE `devices_faults_printers_ora_temporary` ADD PRIMARY KEY(`id_ords`);');

        $result = DB::update('UPDATE devices_printcat_ora d, devices_faults_printers_ora_temporary t
SET d.fault_type_id = t.winning_opinion_id
WHERE d.id_ords = t.id_ords;');

        DB::statement('DROP TEMPORARY TABLE IF EXISTS `devices_faults_printers_ora_temporary`');

        return $result;
    }
}
