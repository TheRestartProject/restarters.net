<?php

namespace App;

use App\Device;
use Illuminate\Database\Eloquent\Model;

use DB;

class Party extends Model
{

    protected $table = 'events';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['devices', 'co2', 'ewaste', 'fixed_devices', 'repairable_devices', 'dead_devices', 'created_at', 'updated_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations


    // Setters


    //Getters
    public function findAll() {//Tested
        return DB::select(DB::raw('SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`pax`,
                    `e`.`free_text`,
                    `e`.`hours`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`
                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                ORDER BY `e`.`start` DESC'));
    }

    public function findAllSearchable() {//Tested
        return DB::select(DB::raw('SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`pax`,
                    `e`.`free_text`,
                    `e`.`hours`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`
                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                WHERE `event_date` <= NOW()
                ORDER BY `e`.`event_date` DESC'));
    }

    public function findThis($id, $devices = false) {//Tested however with devices = true doesn't work
        $sql = 'SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_date` ,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`end`) ) AS `event_end_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`group`,
                    `e`.`pax`,
                    `e`.`volunteers`,
                    `e`.`hours`,
                    `e`.`free_text`,
                    `e`.`wordpress_post_id`,
                    `g`.`name` AS `group_name`

                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                WHERE `e`.`idevents` = :id
                ORDER BY `e`.`start` DESC';

        $party =  DB::select(DB::raw($sql), array('id' => $id));

        if($devices){
            $devices = new Device;
            $party[0]->devices = $devices->ofThisEvent($party[0]->id);
        }

        return $party;
    }

    public function createUserList($party, $users){
        /** reset user list **/
        if(!self::deleteUserList($party)){
            return false;
        }
        $sql = 'INSERT INTO `events_users`(`event`, `user`) VALUES (:party, :user)';
        foreach($users as $k => &$user){

            try {
              DB::insert(DB::raw($sql), array('party' => $party, 'user' => $user));
            } catch (\Illuminate\Database\QueryException $e) {
              dd($e);
            }

        }
    }

    public function deleteUserList($party){
        return DB::delete(DB::raw('DELETE FROM `events_users` WHERE `event` = :party'), array('party' => $party));
    }

    public function ofThisUser($id, $only_past = false, $devices = false){//Tested
        $sql = 'SELECT *, `e`.`venue` AS `venue`, `e`.`location` as `location`, UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`
                FROM `' . $this->table . '` AS `e`
                INNER JOIN `events_users` AS `eu` ON `eu`.`event` = `e`.`idevents`
                INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`
                LEFT JOIN (
                    SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                    FROM `devices` AS `dv`
                    GROUP BY  `dv`.`event`
                ) AS `d` ON `d`.`event` = `e`.`idevents`
                WHERE `eu`.`user` = :id';
        if($only_past == true){
            $sql .= ' AND `e`.`event_date` < NOW()';
        }
        $sql .= ' ORDER BY `e`.`event_date` DESC';

        try {
          $parties = DB::select(DB::raw($sql), array('id' => $id));
        } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
        }

        if($devices){
            $devices = new Device;
            foreach($parties as $i => $party){
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }

        }

        return $parties;

    }

    public function ofThisGroup2($group = 'admin', $only_past = false, $devices = false){//Tested
        $sql = 'SELECT
                    *,
	`e`.`venue` AS `venue`, `e`.`location` as `location`,
                    `g`.`name` AS group_name,


                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`

                FROM `' . $this->table . '` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        //UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) )
        if(is_numeric($group) && $group != 'admin' ){
            $sql .= ' WHERE `e`.`group` = :id ';
        }

        if($only_past == true){
            $sql .= ' AND TIMESTAMP(`e`.`event_date`, `e`.`start`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_date` DESC';

        if(is_numeric($group) && $group != 'admin' ){
          try {
            $parties = DB::select(DB::raw($sql), array('id' => $group));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        } else {
          try {
            $parties = DB::select(DB::raw($sql));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        }

        if($devices){
            $devices = new Device;
            foreach($parties as $i => $party){
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }

        }

        return $parties;
    }

    public function ofTheseGroups($groups = 'admin', $only_past = false, $devices = false){//Tested
        $sql = 'SELECT
                    *,
	`e`.`venue` AS `venue`, `e`.`location` as `location`,
                    `g`.`name` AS group_name,


                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`

                FROM `' . $this->table . '` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        //UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) )
        if(is_array($groups) && $groups != 'admin' ){
            $sql .= ' WHERE `e`.`group` IN (' . implode(', ', $groups) . ') ';
        }

        if($only_past == true){
            $sql .= ' AND TIMESTAMP(`e`.`event_date`, `e`.`start`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_date` DESC';

        try {
          $parties = DB::select(DB::raw($sql));
        } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
        }

        if($devices){
            $devices = new Device;
            foreach($parties as $i => $party){
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }

        }

        return $parties;
    }

    public function ofThisGroup($group = 'admin', $only_past = false, $devices = false){//Tested
        $sql = 'SELECT
                    *,
	`e`.`venue` AS `venue`, `e`.`location` as `location`,


                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`

                FROM `' . $this->table . '` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        //UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) )
        if(is_numeric($group) && $group != 'admin' ){
            $sql .= ' WHERE `e`.`group` = :id ';
        }

        if($only_past == true){
            $sql .= ' AND TIMESTAMP(`e`.`event_date`, `e`.`start`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_date` DESC';

        if(is_numeric($group) && $group != 'admin' ){
          try {
            $parties = DB::select(DB::raw($sql), array('id' => $group));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        } else {
          try {
            $parties = DB::select(DB::raw($sql));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        }

        if($devices){
            $devices = new Device;
            foreach($parties as $i => $party){
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }

        }

        return $parties;

    }

    public function findNextParties($group = null) {//Tested
        $sql = 'SELECT
                    `e`.`idevents`,
                    `e`.`venue`,
                    `e`.`location`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`,
                    `e`.`event_date` AS `plain_date`,
                    NOW() AS `this_moment`,
                    `e`.`start`,
                    `e`.`end`,
                    `e`.`latitude`,
                    `e`.`longitude`
                FROM `' . $this->table . '` AS `e`

                WHERE TIMESTAMP(`e`.`event_date`, `e`.`start`) >= NOW() '; // added one day to make sure it only gets moved to the past the next day

        if(!is_null($group)){
            $sql .= ' AND `e`.`group` = :group ';
        }

        $sql .= ' ORDER BY `e`.`event_date` ASC
                LIMIT 10';

        if(!is_null($group)){
            try {
              return DB::select(DB::raw($sql), array('group' => $group));
            } catch (\Illuminate\Database\QueryException $e) {
              dd($e);
            }
        } else {
          try {
            return DB::select(DB::raw($sql));
          } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
          }
        }

    }

    public function findLatest($limit = 10) {
        return DB::select(DB::raw('SELECT
                    `e`.`idevents`,
                    `e`.`venue`,
                    `e`.`location`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_date`,
                    `e`.`start`,
                    `e`.`end`,
                    `e`.`latitude`,
                    `e`.`longitude`
                FROM `' . $this->table . '` AS `e`
                ORDER BY `e`.`event_date` DESC
                LIMIT :limit'), array('limit' => $limit));
    }

    public function attendees(){//Tested
        return DB::select(DB::raw('SELECT SUM(pax) AS pax FROM ' . $this->table));
    }

}
