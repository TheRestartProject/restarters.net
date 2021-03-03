<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MobifixOra extends Model {

    protected $table = 'devices_faults_mobiles_ora_opinions';
    protected $dateFormat = 'Y-m-d H:i';
    protected $dates = ['created_at', 'updated_at'];
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
    public function fetchFault($exclusions = [], $partner = NULL) {
        $sql = $this->_getSQL($exclusions, $partner);
        return DB::select($sql);
    }

    protected function _getSQL($exclusions = [], $partner = NULL) {
        $sql = "SELECT
d.`id_ords` as id_ords,
IF(d.`data_provider`='repaircafe', 'Repair Café', d.`data_provider`) as partner,
TRIM(d.`brand`) as brand,
TRIM(d.`model`) as model,
d.`repair_status` as repair_status,
TRIM(d.`problem`) as problem,
TRIM(d.`translation`) as translation,
COUNT(o.`id_ords`) as opinions_count
FROM `devices_mobifix_ora` d
LEFT JOIN `devices_faults_mobiles_ora_opinions` o ON o.`id_ords` = d.`id_ords`
WHERE 1 %s
GROUP BY d.`id_ords`
HAVING opinions_count < 3
ORDER BY rand()
LIMIT 1;
";
        $and = '';
        if (!is_null($partner)) {
            $and .= "\nAND d.`data_provider` = '$partner'";
        }
        if (!empty($exclusions)) {
            $ids = implode("','", $exclusions);
            $and .= "\nAND d.`id_ords` NOT IN ('$ids')";
        }

        return sprintf($sql, $and);
    }

    /**
     * Fetch all fault_type_ids fro mobifix
     *
     * @return array
     */
    public function fetchFaultTypes() {
        return DB::select("SELECT * FROM `fault_types_mobiles`");
    }

    /**
     *
     *
     * @return mixed
     */
    public function fetchStatus($partner = NULL) {

        $result = [];

        $result['total_devices'] = DB::select("
SELECT COUNT(DISTINCT d.id_ords) AS total
FROM `devices_mobifix_ora` d
");

        $result['total_opinions_3'] = DB::select("
SELECT COUNT(DISTINCT o.id_ords) AS total
FROM devices_faults_mobiles_ora_opinions o
WHERE (SELECT COUNT(o2.id_ords) FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.id_ords) = 3
");

        $result['total_opinions_2'] = DB::select("
SELECT COUNT(DISTINCT o.id_ords) AS total
FROM devices_faults_mobiles_ora_opinions o
WHERE (SELECT COUNT(o2.id_ords) FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.id_ords) = 2
");

        $result['total_opinions_1'] = DB::select("
SELECT COUNT(DISTINCT o.id_ords) AS total
FROM devices_faults_mobiles_ora_opinions o
WHERE (SELECT COUNT(o2.id_ords) FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.id_ords) = 1
");

        $result['total_opinions_0'] = DB::select("
SELECT COUNT(d.id_ords) AS total
FROM devices_mobifix_ora d
LEFT JOIN devices_faults_mobiles_ora_opinions o ON o.id_ords = d.id_ords
WHERE o.id_ords IS NULL
");

        $result['total_recats'] = DB::select("
SELECT COUNT(DISTINCT items) as total FROM
(SELECT
d.id_ords AS items,
COALESCE(ANY_VALUE(a.fault_type_id),(SELECT o1.fault_type_id FROM devices_faults_mobiles_ora_opinions o1 WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1)) AS winning_opinion,
ANY_VALUE(a.fault_type_id) AS adjudicated_opinion_id,
(SELECT o2.fault_type_id FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.fault_type_id ORDER BY COUNT(o2.fault_type_id) DESC LIMIT 1) AS top_crowd_opinion,
ROUND((SELECT COUNT(o3.fault_type_id) as top_crowd_opinion_count FROM devices_faults_mobiles_ora_opinions o3 WHERE o3.id_ords = o.id_ords GROUP BY o3.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o4.fault_type_id) as all_votes FROM devices_faults_mobiles_ora_opinions o4 WHERE o4.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count
FROM `devices_mobifix_ora` d
LEFT OUTER JOIN devices_faults_mobiles_ora_opinions o ON o.id_ords = d.id_ords
LEFT OUTER JOIN devices_faults_mobiles_ora_adjudicated a ON a.id_ords = d.id_ords
GROUP BY d.id_ords
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
OR adjudicated_opinion_id IS NOT NULL
) AS results
");

        $result['list_recats'] = DB::select("
SELECT winning_opinion_id, winning_opinion, COUNT(winning_opinion) AS total FROM
(SELECT
d.id_ords,
COALESCE(ANY_VALUE(a.fault_type_id),(SELECT o1.fault_type_id FROM devices_faults_mobiles_ora_opinions o1 WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1)) AS winning_opinion_id,
COALESCE(ANY_VALUE(fta.title),(SELECT fto.title FROM devices_faults_mobiles_ora_opinions o1 JOIN fault_types_mobiles fto ON fto.id = o1.fault_type_id WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1)) AS winning_opinion,
ANY_VALUE(a.fault_type_id) AS adjudicated_opinion_id,
ANY_VALUE(fta.title) AS adjudicated_opinion,
(SELECT o2.fault_type_id FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.fault_type_id ORDER BY COUNT(o2.fault_type_id) DESC LIMIT 1) AS top_crowd_opinion,
ROUND((SELECT COUNT(o3.fault_type_id) as top_crowd_opinion_count FROM devices_faults_mobiles_ora_opinions o3 WHERE o3.id_ords = o.id_ords GROUP BY o3.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o4.fault_type_id) as all_votes FROM devices_faults_mobiles_ora_opinions o4 WHERE o4.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count
FROM `devices_mobifix_ora` d
LEFT OUTER JOIN devices_faults_mobiles_ora_opinions o ON o.id_ords = d.id_ords
LEFT OUTER JOIN devices_faults_mobiles_ora_adjudicated a ON a.id_ords = d.id_ords
LEFT OUTER JOIN fault_types_mobiles fta ON fta.id = a.fault_type_id
GROUP BY d.id_ords
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
OR adjudicated_opinion_id IS NOT NULL
) AS results
GROUP BY winning_opinion_id
ORDER BY total DESC
");

        $result['total_splits'] = DB::select("
SELECT COUNT(DISTINCT items) as total FROM
(SELECT
d.id_ords AS items,
ANY_VALUE(a.fault_type_id) AS adjudicated_opinion_id,
ROUND((SELECT COUNT(o2.fault_type_id) as top_crowd_opinion_count FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.fault_type_id) as all_votes FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count
FROM `devices_mobifix_ora` d
LEFT OUTER JOIN devices_faults_mobiles_ora_opinions o ON o.id_ords = d.id_ords
LEFT OUTER JOIN devices_faults_mobiles_ora_adjudicated a ON a.id_ords = d.id_ords
GROUP BY d.id_ords
HAVING
(all_crowd_opinions_count = 3 AND top_crowd_opinion_percentage < 60)
AND adjudicated_opinion_id IS NULL
) AS results
");

        $result['list_splits'] = DB::select("
SELECT
d.id_ords,
ANY_VALUE(a.fault_type_id) AS adjudicated_opinion_id,
(SELECT o1.fault_type_id FROM devices_faults_mobiles_ora_opinions o1 WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1) AS top_crowd_opinion,
ROUND((SELECT COUNT(o2.fault_type_id) as top_crowd_opinion_count FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.fault_type_id) as all_votes FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count,
GROUP_CONCAT(ft.title) as opinions,
TRIM(COALESCE(d.`brand`,'')) as brand,
TRIM(COALESCE(d.`model`,'')) as model,
TRIM(d.`problem`) as problem
FROM `devices_mobifix_ora` d
LEFT OUTER JOIN devices_faults_mobiles_ora_opinions o ON o.id_ords = d.id_ords
LEFT OUTER JOIN devices_faults_mobiles_ora_adjudicated a ON a.id_ords = d.id_ords
LEFT OUTER JOIN fault_types_mobiles ft ON ft.id = o.fault_type_id
GROUP BY d.id_ords
HAVING
(all_crowd_opinions_count = 3 AND top_crowd_opinion_percentage < 60)
AND adjudicated_opinion_id IS NULL
");
        return $result;
    }

    /**
     * Write the winning opinions to `devices_mobifix_ora`.`fault_type`.
     *
     * @return mixed
     */
    public function updateDevices() {

        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS `devices_faults_mobiles_ora_temporary` AS (
SELECT
d.id_ords,
COALESCE(ANY_VALUE(a.fault_type_id),(SELECT o1.fault_type_id FROM devices_faults_mobiles_ora_opinions o1 WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1)) AS winning_opinion_id,
COALESCE(ANY_VALUE(fta.title),(SELECT fto.title FROM devices_faults_mobiles_ora_opinions o1 JOIN fault_types_mobiles fto ON fto.id = o1.fault_type_id WHERE o1.id_ords = o.id_ords GROUP BY o1.fault_type_id ORDER BY COUNT(o1.fault_type_id) DESC LIMIT 1)) AS winning_opinion,
ANY_VALUE(a.fault_type_id) AS adjudicated_opinion_id,
ANY_VALUE(fta.title) AS adjudicated_opinion,
(SELECT o2.fault_type_id FROM devices_faults_mobiles_ora_opinions o2 WHERE o2.id_ords = o.id_ords GROUP BY o2.fault_type_id ORDER BY COUNT(o2.fault_type_id) DESC LIMIT 1) AS top_crowd_opinion,
ROUND((SELECT COUNT(o3.fault_type_id) as top_crowd_opinion_count FROM devices_faults_mobiles_ora_opinions o3 WHERE o3.id_ords = o.id_ords GROUP BY o3.fault_type_id ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o4.fault_type_id) as all_votes FROM devices_faults_mobiles_ora_opinions o4 WHERE o4.id_ords = o.id_ords) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type_id) AS all_crowd_opinions_count
FROM `devices_mobifix_ora` d
LEFT OUTER JOIN devices_faults_mobiles_ora_opinions o ON o.id_ords = d.id_ords
LEFT OUTER JOIN devices_faults_mobiles_ora_adjudicated a ON a.id_ords = d.id_ords
LEFT OUTER JOIN fault_types_mobiles fta ON fta.id = a.fault_type_id
GROUP BY d.id_ords
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
OR adjudicated_opinion_id IS NOT NULL
ORDER BY NULL);");

        DB::statement("ALTER TABLE `devices_faults_mobiles_ora_temporary` ADD PRIMARY KEY(`id_ords`);");

        $result = DB::update("UPDATE devices_mobifix_ora d, devices_faults_mobiles_ora_temporary t
SET d.fault_type_id = t.winning_opinion_id
WHERE d.id_ords = t.id_ords;");

        DB::statement("DROP TEMPORARY TABLE IF EXISTS `devices_faults_mobiles_ora_temporary`");

        return $result;
    }

}

