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
        'name', 'email', 'password', 'role', 'recovery', 'recovery_expires', 'location', 'age', 'gender', 'country', 'newsletter', 'invites', 'biography', 'consent_future_data', 'consent_past_data', 'consent_gdpr', 'number_of_logins', 'latitude', 'longitude'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    public function role() {
      return $this->hasOne('App\Role', 'idroles', 'role');
    }

    public function userSkills() {
      return $this->hasMany('App\UsersSkills', 'user', 'id');
    }

    public function skills() {
      return $this->belongsToMany('App\UsersSkills', 'users_skills', 'user', 'skill');
    }

    public function groups() {
      return $this->belongsToMany('App\Group', 'users_groups', 'user', 'group');
    }

    public function preferences() {
      return $this->belongsToMany('App\User', 'users_preferences', 'user_id', 'preference_id');
    }

    public function permissions() {
      return $this->belongsToMany('App\User', 'users_permissions', 'user_id', 'permission_id');
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
                ->leftJoin('images', function ($join) use ($id) {
                  $join->join('xref', 'xref.object', '=', 'images.idimages')
                         ->where('xref.object_type', '=', 5)
                           ->where('xref.reference_type', '=', 1)
                             ->where('xref.reference', '=', $id)
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

    public function getUserList($eloquent = false) {//Tested!

        if (!$eloquent) {
          $Users = DB::select(DB::raw('SELECT users.id AS id, users.name, users.email, roles.role FROM users
                  INNER JOIN roles ON roles.idroles = users.role WHERE users.deleted_at IS NULL
                  ORDER BY users.id ASC')); //INNER JOIN sessions ON sessions.user = users.id, UNIX_TIMESTAMP(sessions.modified_at) AS modified_at

          if(is_array($Users)){

              $User = new User;
              foreach($Users as $key => $user) {

                  $Users[$key]->permissions = $User->getRolePermissions($user->role);
              }
          }
        } else {
          $Users = User::join('roles', 'users.role', '=', 'roles.idroles');
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

    /*
    *
    * This allows us to check whether consent has been provided - couples with custom middleware
    *
    */
    public function hasUserGivenConsent(){

      if( is_null($this->consent_future_data) )
        return false;

      //Past data is only required for users who created their account prior to the Laravel app launch
      if( is_null($this->consent_past_data) && strtotime($this->created_at) <= strtotime( date('2018-06-26') ) )
        return false;

      if( is_null($this->consent_gdpr) )
        return false;

      return true;

    }

    /**
     * TODO: updated_at acting as a proxy for last login time for now.  We amend the
     * user row on every login so it'll do for now.  However, we should probably add
     * a dedicated column.
     *
     * @return Date when the user last logged in
     */
    public function lastLogin()
    {
        return $this->updated_at;
    }

    /**
     * Anonymise user details before soft deletion.
     */
    public function anonymise()
    {
        $this->name = "Deleted User";
        $this->email = $this->id . "@deleted.com";
        $this->username = $this->id . '-deleted';

        // TODO: country, city, gender, age, also required?
    }


    /**
     * Attempt to get first name from full name.
     */
    public function getFirstName()
    {
        if ($this->name == '')
            return '';

        $nameParts = explode(' ', $this->name);

        return $nameParts[0];
    }
}
