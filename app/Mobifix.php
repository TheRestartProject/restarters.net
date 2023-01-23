<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Mobifix extends Model
{
    use HasFactory;

    protected $table = 'devices_faults_mobiles_opinions';
    protected $dateFormat = 'Y-m-d H:i';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['iddevices', 'fault_type', 'user_id', 'ip_address', 'session_id'];

    /**
     * Fetch a single random computer device record that has less than 3
     * existing opinions and a non-empty problem.
     *
     * Not the most efficient query
     *
     * @return array
     */
    public function fetchFault()
    {
        return DB::select("SELECT
d.`iddevices` as iddevices,
TRIM(d.`brand`) as brand,
TRIM(d.`model`) as model,
IF(d.`repair_status`=1, 'Fixed',IF(d.`repair_status`=2, 'Repairable',
IF(d.`repair_status`=3, 'End of life', 'Unknown'))) as repair_status,
TRIM(d.`problem`) as problem,
COUNT(o.`iddevices`) as opinions_count
FROM `devices` d
LEFT JOIN `devices_faults_mobiles_opinions` o ON o.`iddevices` = d.`iddevices`
WHERE d.`category` = 25
AND LENGTH(TRIM(d.`problem`)) > 0
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

        $result['total_devices'] = DB::select('
SELECT COUNT(DISTINCT d.iddevices) AS total
FROM devices d
WHERE d.category = 25
AND LENGTH(TRIM(d.problem)) > 0
');

        $result['total_opinions_3'] = DB::select('
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_faults_mobiles_opinions o
WHERE (SELECT COUNT(o2.iddevices) FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.iddevices) = 3
');

        $result['total_opinions_2'] = DB::select('
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_faults_mobiles_opinions o
WHERE (SELECT COUNT(o2.iddevices) FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.iddevices) = 2
');

        $result['total_opinions_1'] = DB::select('
SELECT COUNT(DISTINCT o.iddevices) AS total
FROM devices_faults_mobiles_opinions o
WHERE (SELECT COUNT(o2.iddevices) FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.iddevices) = 1
');

        $result['total_opinions_0'] = DB::select('
SELECT COUNT(d.iddevices) AS total
FROM devices d
LEFT JOIN devices_faults_mobiles_opinions o ON o.iddevices = d.iddevices
WHERE d.category = 25
AND LENGTH(TRIM(d.problem)) > 0
AND o.iddevices IS NULL
');

        $result['total_recats'] = DB::select("
SELECT COUNT(DISTINCT results.iddevices) as total FROM
(SELECT
o.iddevices,
(SELECT o1.fault_type FROM devices_faults_mobiles_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.fault_type ORDER BY COUNT(o1.fault_type) DESC, o1.fault_type ASC LIMIT 1) AS winning_opinion,
ROUND((SELECT COUNT(o2.fault_type) as top_crowd_opinion_count FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.fault_type ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.fault_type) as all_votes FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type) AS all_crowd_opinions_count
FROM devices_faults_mobiles_opinions o
GROUP BY o.iddevices
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
UNION
SELECT
a.iddevices,
a.fault_type AS winning_opinion,
'adj' AS top_crowd_opinion_percentage,
'adj' AS all_crowd_opinions_count
FROM devices_faults_mobiles_adjudicated a
) AS results;
");

        $result['list_recats'] = DB::select("
SELECT results.winning_opinion, COUNT(*) AS total FROM
(SELECT
o.iddevices,
(SELECT o1.fault_type FROM devices_faults_mobiles_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.fault_type ORDER BY COUNT(o1.fault_type) DESC, o1.fault_type ASC LIMIT 1) AS winning_opinion,
ROUND((SELECT COUNT(o2.fault_type) as top_crowd_opinion_count FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.fault_type ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.fault_type) as all_votes FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type) AS all_crowd_opinions_count
FROM devices_faults_mobiles_opinions o
GROUP BY o.iddevices
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
UNION
SELECT
a.iddevices,
a.fault_type AS winning_opinion,
'adj' AS top_crowd_opinion_percentage,
'adj' AS all_crowd_opinions_count
FROM devices_faults_mobiles_adjudicated a
WHERE a.fault_type != 'Misc'
) AS results
GROUP BY winning_opinion
ORDER BY total DESC
");

        $result['list_splits'] = DB::select("
SELECT
d.iddevices,
(SELECT o1.fault_type FROM devices_faults_mobiles_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.fault_type ORDER BY COUNT(o1.fault_type) DESC, o1.fault_type ASC LIMIT 1) AS top_crowd_opinion,
ROUND((SELECT COUNT(o2.fault_type) as top_crowd_opinion_count FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.fault_type ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.fault_type) as all_votes FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type) AS all_crowd_opinions_count,
GROUP_CONCAT(o.fault_type ORDER BY o.fault_type) as opinions,
TRIM(COALESCE(d.`brand`,'')) as brand,
TRIM(COALESCE(d.`model`,'')) as model,
TRIM(d.`problem`) as problem
FROM devices d
LEFT OUTER JOIN devices_faults_mobiles_opinions o ON o.iddevices = d.iddevices
WHERE (SELECT a.iddevices FROM devices_faults_mobiles_adjudicated a WHERE a.iddevices = d.iddevices) IS NULL
GROUP BY d.iddevices
HAVING
(all_crowd_opinions_count = 3 AND top_crowd_opinion_percentage < 60)
");

        $result['total_splits'] = [json_decode(json_encode(['total' => count($result['list_splits'])]), false)];

        return $result;
    }

    /**
     * Write the winning opinions to `devices`.`fault_type`.
     *
     * @return mixed
     */
    public function updateDevices()
    {
        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS `devices_faults_mobiles_temporary` AS
SELECT
o.iddevices,
(SELECT o1.fault_type FROM devices_faults_mobiles_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.fault_type ORDER BY COUNT(o1.fault_type) DESC, o1.fault_type ASC LIMIT 1) AS winning_opinion,
ROUND((SELECT COUNT(o2.fault_type) as top_crowd_opinion_count FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.fault_type ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.fault_type) as all_votes FROM devices_faults_mobiles_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type) AS all_crowd_opinions_count
FROM devices_faults_mobiles_opinions o
GROUP BY o.iddevices
HAVING
(all_crowd_opinions_count > 1 AND top_crowd_opinion_percentage > 60)
UNION
SELECT
a.iddevices,
a.fault_type AS winning_opinion,
'adj' AS top_crowd_opinion_percentage,
'adj' AS all_crowd_opinions_count
FROM devices_faults_mobiles_adjudicated a
");
        DB::statement('ALTER TABLE `devices_faults_mobiles_temporary` ADD PRIMARY KEY(`iddevices`);');

        $result = DB::update('UPDATE devices d, `devices_faults_mobiles_temporary` t
SET d.fault_type = t.winning_opinion
WHERE d.iddevices = t.iddevices;');

        DB::statement('DROP TEMPORARY TABLE IF EXISTS `devices_faults_mobiles_temporary`');

        return $result;
    }

    /**
     * Write "Unknown" to `devices`.`fault_type` for records with empty problem.
     *
     * @return mixed
     */
    public function updateDevicesWithEmptyProblem()
    {
        return DB::update("UPDATE devices d
SET d.fault_type = 'Unknown'
WHERE d.category = 25
AND LENGTH(d.problem) = 0
");
    }
}
