<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

class User extends Authenticatable
{

    use Notifiable;
    use SoftDeletes;
    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'recovery', 'recovery_expires', 'location', 'age', 'gender'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    //Table Relations

    // public function userGroups() {
    //   return $this->hasMany('App\UserGroup', 'user', 'id');
    // }
    //
    // public function rolePermissions() {
    //   return $this->hasMany('App\RolePermissions', 'role', 'role');
    // }
    //
    public function role() {
      return $this->hasOne('App\Role', 'idroles', 'role');
    }
    //
    // public function sessions() {
    //   return $this->hasMany('App\Session', 'user', 'id');
    // }

    public function getRolePermissions($role){
        return DB::select(DB::raw('SELECT p.idpermissions, p.permission, r.idroles, r.role FROM permissions AS p
                INNER JOIN roles_permissions AS rp ON rp.permission = p.idpermissions
                INNER JOIN roles AS r ON rp.role= r.idroles
                WHERE r.role = :role'), array('role' => $role));
    }

    public function getUserGroups($user){
        return DB::select(DB::raw('SELECT * FROM `' . $this->table . '` AS `u`
                INNER JOIN `users_groups` AS `ug` ON `ug`.`user` = `u`.`id`
                INNER JOIN `groups` AS `g` ON `ug`.`group` = `g`.`idgroups`
                WHERE `u`.`id` = :id'), array('id' => $user));
    }

    // Setters
    public function setPassword($password) {
      $this->password = $password;
    }

    //Getters
    public static function getProfile($id) {//Tested!

      try {
        return User::where('users.id', '=', $id)
                ->leftJoin('images', function ($join) {
                  $join->join('xref', 'xref.object', '=', 'images.idimages')
                         ->where('xref.object_type', '=', 5)
                           ->where('xref.reference_type', '=', 1)
                             ->where('xref.reference', '=', 'users.id')
                               ->groupBy('images.path')
                                 ->select('images.*');
                })->first();
      } catch (\Illuminate\Database\QueryException $e) {
        return false;
      }

    }

    // public function find($params){//Laravel method called find already. Need Solution!!//Tested!
    //     $sql = 'SELECT * FROM ' . $this->table . '
    //             INNER JOIN roles ON roles.idroles = ' . $this->table . '.role
    //             WHERE ';
    //     $clauses = array();
    //     foreach($params as $field => $value) {
    //         $clauses[] = $field . ' = :' . $field;
    //
    //     $sql .= implode(' AND ', $clauses);
    //
    //     try {
    //       return DB::select(DB::raw($sql), $params);
    //     } catch (\Illuminate\Database\QueryException $e) {
    //       return false;
    //     }
    // }

    public function getUserList() {//Tested!

        $Users = DB::select(DB::raw('SELECT users.id AS id, users.name, users.email, roles.role, UNIX_TIMESTAMP(sessions.modified_at) AS modified_at FROM users
                INNER JOIN roles ON roles.idroles = users.role
                INNER JOIN sessions ON sessions.user = users.id
                ORDER BY users.id ASC'));

        if(is_array($Users)){

            $User = new User;
            foreach($Users as $key => $user) {

                $Users[$key]->permissions = $User->getRolePermissions($user->role);
            }
        }

        return $Users;

    }

    public function partyEligible(){//Tested!
      return DB::select(DB::raw('SELECT
                  users.id AS id,
                  users.name,
                  users.email,
                  roles.role
              FROM ' . $this->table . '
              INNER JOIN roles ON roles.idroles = users.role
              WHERE users.role > 1
              ORDER BY users.name ASC'));

    }

    public function inGroup($group){//Tested!
        return DB::select(DB::raw('SELECT
                    users.id AS id,
                    users.name,
                    users.email,
                    roles.role
                FROM ' . $this->table . '
                INNER JOIN roles ON roles.idroles = users.role
                WHERE users.role > 1
                    AND users.id IN
                        (SELECT `user` FROM users_groups WHERE `group` = :group)
                ORDER BY users.name ASC'), array('group' => $group));

    }

    //This create user function is already done by the RegisterController

    /** check if email is already in the database **/
    public function checkEmail($email){//Tested!

        $r = DB::select(DB::raw('SELECT COUNT(id) AS emails FROM ' . $this->table . ' WHERE email = :email'), array('email' => $email));
        return ($r[0]->emails > 0) ? false : true;

    }

}
