<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;

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
    protected $fillable = ['iddevices', 'fault_type', 'user_id', 'ip_address', 'session_id', 'country', 'age']; 

    /**
     * Fetch a single random computer device record that has less than 5
     * existing opinions and a non-empty problem.
     * 
     * Not the most efficient query
     * 
     * @return array
     */
    public function fetchFault() {
        return DB::select(DB::raw("SELECT
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
            LEFT OUTER JOIN `devices_faults` f ON f.`iddevices` = d.iddevices
            LEFT JOIN `categories` c ON c.`idcategories` = d.`category`
            LEFT JOIN `devices_faults_opinions` o ON o.`iddevices` = d.`iddevices`
            WHERE d.`category` IN (11,15,16,17,26)
            AND LENGTH(TRIM(d.`problem`)) > 0
            GROUP BY d.`iddevices`
            HAVING opinions_count < 5
            ORDER BY rand()
            LIMIT 1;"
        ));
    }

}
