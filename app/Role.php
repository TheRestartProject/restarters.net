<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class Role extends Model
{
    const ROOT = 1;
    const ADMIN = 2;
    const HOST = 3;
    const RESTARTER = 4;
    const GUSET = 5;

    protected $table = 'roles';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    //Table Relations


    // Setters


    //Getters
    /**
     * Extended to include connected permissions
     * and display all data (users too)
     * */
    public function findAll()
    {
        return DB::select(DB::raw('SELECT
                        `r`.`idroles` AS `id`,
                        `r`.`role` AS `role`,
                        GROUP_CONCAT(`p`.`permission` ORDER BY `p`.`permission` ASC SEPARATOR ", "  )  as `permissions_list`
                    FROM `' . $this->table . '` AS `r`
                    LEFT JOIN `roles_permissions` AS `rp` ON `r`.`idroles` = `rp`.`role`
                    LEFT JOIN `permissions` AS `p` ON `rp`.`permission` = `p`.`idpermissions`
                    GROUP BY `r`.`idroles`
                    ORDER BY `r`.`idroles` ASC'));
    }

    public function permissions()
    {
//Tested!
        return DB::select(DB::raw('SELECT * FROM `permissions` ORDER BY `idpermissions` ASC'));
    }

    public function rolePermissions($role)
    {
//Tested!
        return DB::select(DB::raw('SELECT * FROM `permissions`
                    INNER JOIN `roles_permissions` ON `roles_permissions`.`permission` = `permissions`.`idpermissions`
                    WHERE `roles_permissions`.`role` = :role
                    ORDER BY `idpermissions` ASC'), array('role' => $role));
    }

    public function edit($id, $data)
    {
//Tested!

      // delete permissions before updating references
        DB::delete(DB::raw('DELETE FROM roles_permissions WHERE role = :role'), array('role' => $id));

      // insert data here
        $sql = 'INSERT INTO roles_permissions(role, permission) VALUES (:role, :permission)';

        foreach ($data as &$p) {
            try {
                DB::insert(DB::raw($sql), array('role' => $id, 'permission' => $p));
            } catch (\Illuminate\Database\QueryException $e) {
                return false;
            }
        }

        return true;
    }
}
