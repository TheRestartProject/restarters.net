<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class Group extends Model
{

    protected $table = 'groups';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'website', 'area', 'location', 'latitude', 'latitude', 'longitude', 'free_text'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations


    // Setters


    //Getters
    public function findAll() {
      try {
        return DB::select(DB::raw('SELECT
                    `g`.`idgroups` AS `id`,
                    `g`.`name` AS `name`,
                    `g`.`location` AS `location`,
                    `g`.`latitude` AS `latitude`,
                    `g`.`longitude` AS `longitude`,
                    `g`.`free_text` AS `free_text`,
                    `g`.`area` AS `area`,
                    `g`.`frequency` AS `frequency`,
                    GROUP_CONCAT(`u`.`name` ORDER BY `u`.`name` ASC SEPARATOR ", "  )  AS `user_list`
                FROM `' . $this->table . '` AS `g`
                LEFT JOIN `users_groups` AS `ug` ON `g`.`idgroups` = `ug`.`group`
                LEFT JOIN `users` AS `u` ON `ug`.`user` = `u`.`id`
                GROUP BY `g`.`idgroups`
                ORDER BY `g`.`idgroups` ASC'));
      } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
      }
    }

    public function findList() {
      try {
        return DB::select(DB::raw('SELECT
                `g`.`idgroups` AS `id`,
                `g`.`name` AS `name`,
                `g`.`location` AS `location`,
                `g`.`area` AS `area`,
                `xi`.`path` AS `path`

            FROM `' . $this->table . '` AS `g`

            LEFT JOIN (
                SELECT * FROM `images`
                    INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                    WHERE `xref`.`object_type` = 5
                    AND `xref`.`reference_type` = 2
                    GROUP BY `images`.`path`
            ) AS `xi`
            ON `xi`.`reference` = `g`.`idgroups`

            GROUP BY `g`.`idgroups`

            ORDER BY `g`.`name` ASC'));
      } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
      }
    }

    public function findOne($id){//Took out GROUP BY `images`.`path` NB:Error message -> 'fixometer_laravel.images.idimages' isn't in GROUP BY
      try {
        $group = DB::select(DB::raw('SELECT * FROM `' . $this->table . '` AS `g`
                LEFT JOIN (
                    SELECT * FROM `images`
                        INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                        WHERE `xref`.`object_type` = 5
                        AND `xref`.`reference_type` = ' . env('TBL_GROUPS') . '
                        GROUP BY `images`.`path`
                ) AS `xi`
                ON `xi`.`reference` = `g`.`idgroups`
                WHERE `id' . $this->table . '` = :id'), array('id' => $id));
      } catch (\Illuminate\Database\QueryException $e) {
          dd($e);
      }

      if (!empty($group)) {
        return $group[0];
      }

    }

    public function findHost($id){
        return DB::select(DB::raw('SELECT *,
                    `g`.`name` AS `groupname`,
                    `u`.`name` AS `hostname`
                FROM `' . $this->table . '` AS `g`
                INNER JOIN `users_groups` AS `ug`
                    ON `ug`.`group` = `g`.`idgroups`
                INNER JOIN `users` AS `u`
                    ON `u`.`id` = `ug`.`user`
                LEFT JOIN (
                    SELECT * FROM `images`
                        INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                        WHERE `xref`.`object_type` = 5
                        AND `xref`.`reference_type` = ' . env('TBL_USERS') . '
                        GROUP BY `images`.`path`
                ) AS `xi`
                ON `xi`.`reference` = `u`.`id`

                WHERE `g`.`idgroups` = :id
                AND `u`.`role` = 3'), array('id' => $id));
    }

    public function ofThisUser($id){
        return DB::select(DB::raw('SELECT * FROM `' . $this->table . '` AS `g`
                INNER JOIN `users_groups` AS `ug`
                    ON `ug`.`group` = `g`.`idgroups`

                LEFT JOIN (
                    SELECT * FROM `images`
                        INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                        WHERE `xref`.`object_type` = 5
                        AND `xref`.`reference_type` = ' . env('TBL_GROUPS') . '
                        GROUP BY `images`.`path`
                ) AS `xi`
                ON `xi`.`reference` = `g`.`idgroups`

                WHERE `ug`.`user` = :id
                ORDER BY `g`.`name` ASC'), array('id' => $id));
    }


}
