<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class Faultcat extends Model {

    protected $table = 'devices_faults_opinions';
    protected $dateFormat = 'Y-m-d H:i';
    protected $dates = ['created_at', 'updated_at'];
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['iddevices', 'fault_type', 'user_id', 'ip_address', 'session_id']; 

    /**
     * Fetch a single random computer device record that has less than 5
     * existing opinions and a non-empty problem.
     * 
     * Not the most efficient query
     * 
     * @return array
     */
    public function fetchFault() {
        return DB::select("SELECT
d.`iddevices` as iddevices,
TRIM(c.`name`) as category,
TRIM(d.`brand`) as brand,
TRIM(d.`model`) as model,
IF(d.`repair_status`=1, 'Fixed',IF(d.`repair_status`=2, 'Repairable', 
IF(d.`repair_status`=3, 'End of life', 'Unknown'))) as repair_status,
TRIM(COALESCE(f.`fault_type`,'Unknown')) as fault_type,
TRIM(d.`problem`) as problem,
COUNT(o.`iddevices`) as opinions_count
FROM `devices` d
LEFT OUTER JOIN `devices_faults_events` f ON f.`iddevices` = d.iddevices
LEFT JOIN `categories` c ON c.`idcategories` = d.`category`
LEFT JOIN `devices_faults_opinions` o ON o.`iddevices` = d.`iddevices`
WHERE d.`category` IN (11,15,16,17,26)
AND LENGTH(TRIM(d.`problem`)) > 0
GROUP BY d.`iddevices`
HAVING opinions_count < 5
ORDER BY rand()
LIMIT 1;"
        );
    }
    
    /**
     * Write the winning opinions to `devices`.`fault_type`.
     *
     * @return mixed
     */
    public function updateDevices() {

        DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS `devices_faults_temporary` AS (
SELECT
d.iddevices,
COALESCE(ANY_VALUE(a.fault_type),(SELECT o1.fault_type FROM devices_faults_opinions o1 WHERE o1.iddevices = o.iddevices GROUP BY o1.fault_type ORDER BY COUNT(o1.fault_type) DESC LIMIT 1)) AS winning_opinion,
ANY_VALUE(a.fault_type) AS adjudicated_opinion,
ROUND((SELECT COUNT(o2.fault_type) as top_crowd_opinion_count FROM devices_faults_opinions o2 WHERE o2.iddevices = o.iddevices GROUP BY o2.fault_type ORDER BY top_crowd_opinion_count DESC LIMIT 1) /
(SELECT COUNT(o2.fault_type) as all_votes FROM devices_faults_opinions o2 WHERE o2.iddevices = o.iddevices) * 100) AS top_crowd_opinion_percentage,
COUNT(o.fault_type) AS all_crowd_opinions_count
FROM devices d
LEFT OUTER JOIN devices_faults_opinions o ON o.iddevices = d.iddevices
LEFT OUTER JOIN devices_faults_adjudicated a ON a.iddevices = d.iddevices
WHERE d.category IN (11,15,16,17,26)
AND LENGTH(d.problem) > 0
GROUP BY d.iddevices
HAVING
(all_crowd_opinions_count > 2 AND top_crowd_opinion_percentage = 100)
OR
(all_crowd_opinions_count > 3 AND top_crowd_opinion_percentage >= 75)
OR
(all_crowd_opinions_count > 4 AND top_crowd_opinion_percentage >= 60)
OR adjudicated_opinion IS NOT NULL
ORDER BY NULL);");

        DB::statement("ALTER TABLE `devices_faults_temporary` ADD PRIMARY KEY(`iddevices`);");

        $result = DB::update("UPDATE devices d, devices_faults_opinions o 
SET d.fault_type = o.fault_type
WHERE d.iddevices = o.iddevices;");

        DB::statement("DROP TEMPORARY TABLE IF EXISTS `devices_faults_temporary`");

        return $result;
    }

}
