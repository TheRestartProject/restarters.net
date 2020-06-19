<?php

namespace App;

use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Network;
use App\UserGroups;
use App\UsersPermissions;

use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use OwenIt\Auditing\Contracts\Auditable;

class WikiSyncStatus {
    const DoNotCreate = 0;
    const CreateAtLogin = 1;
    const Created = 2;
}

class User extends Authenticatable implements Auditable
{
    use Notifiable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'recovery', 'recovery_expires', 'language', 'repair_network', 'location', 'age', 'gender', 'country', 'newsletter', 'drip_subscriber_id', 'invites', 'biography', 'consent_future_data', 'consent_past_data', 'consent_gdpr', 'number_of_logins', 'latitude', 'longitude', 'last_login_at', 'api_token', 'access_group_tag_id', 'calendar_hash'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Attributes to exclude from the Audit.
     *
     * @var array
     */
    protected $auditExclude = [
        'number_of_logins',
        'last_login_at',
        'remember_token',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'updated' => UserUpdated::class,
        'deleted' => UserDeleted::class,
    ];

    public function role()
    {
        return $this->hasOne('App\Role', 'idroles', 'role');
    }

    public function userSkills()
    {
        return $this->hasMany('App\UsersSkills', 'user', 'id');
    }

    public function skills()
    {
        return $this->belongsToMany('App\UsersSkills', 'users_skills', 'user', 'skill');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Group', 'users_groups', 'user', 'group');
    }

    /**
     * Return a list of repair groups near the user.
     *
     * @param int $searchRadiusInMiles How far to search for groups
     * @param int $numberOfGroups How many groups to return
     * @param array $idsOfGroupsToIgnore Any groups that should be excluded from the result
     */
    public function groupsNearby($searchRadiusInMiles = 150, $numberOfGroups = 10, $idsOfGroupsToIgnore = null)
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            return null;
        }

        $groupsNearbyQuery = Group::select(
            DB::raw('*, ( 6371 * acos( cos( radians('.$this->latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$this->longitude.') ) + sin( radians('.$this->latitude.') ) * sin( radians( latitude ) ) ) ) AS distance')
        )->having('distance', '<=', $searchRadiusInMiles);

        if (! is_null($idsOfGroupsToIgnore)) {
            $groupsNearbyQuery->whereNotIn('idgroups', $idsOfGroupsToIgnore);
        }

        $groupsNearby = $groupsNearbyQuery->orderBy('distance', 'ASC')
            ->take($numberOfGroups)
            ->get();

        return $groupsNearby;
    }

    public function preferences()
    {
        return $this->belongsToMany('App\User', 'users_preferences', 'user_id', 'preference_id');
    }

    public function permissions()
    {
        return $this->belongsToMany('App\User', 'users_permissions', 'user_id', 'permission_id');
    }

    public function addPreference($slug)
    {
        /** @var Preferences $preference */
        $preference = Preferences::where(['slug' => $slug])->first();
        UsersPreferences::create([
            'user_id' => $this->getKey(),
            'preference_id' => $preference->getKey()
        ]);
    }

    //
    // public function sessions() {
    //   return $this->hasMany('App\Session', 'user', 'id');
    // }

    public function getRolePermissions($role)
    {
        return DB::select(DB::raw('SELECT p.idpermissions, p.permission, r.idroles, r.role FROM permissions AS p
                INNER JOIN roles_permissions AS rp ON rp.permission = p.idpermissions
                INNER JOIN roles AS r ON rp.role= r.idroles
                WHERE r.role = :role'), array('role' => $role));
    }

    public function getUserGroups($user)
    {
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` AS `u`
                INNER JOIN `users_groups` AS `ug` ON `ug`.`user` = `u`.`id`
                INNER JOIN `groups` AS `g` ON `ug`.`group` = `g`.`idgroups`
                WHERE `u`.`id` = :id'), array('id' => $user));
    }

    // Setters
    public function setPassword($password)
    {
        $this->password = $password;
    }

    //Getters
    public static function getProfile($id)
    {
        //Tested!

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

    public function getUserList($eloquent = false)
    {
        //Tested!

        if ( ! $eloquent) {
            $Users = DB::select(DB::raw('SELECT users.id AS id, users.name, users.email, roles.role FROM users
                  INNER JOIN roles ON roles.idroles = users.role WHERE users.deleted_at IS NULL
                  ORDER BY users.id ASC')); //INNER JOIN sessions ON sessions.user = users.id, UNIX_TIMESTAMP(sessions.modified_at) AS modified_at

            if (is_array($Users)) {
                $User = new User;
                foreach ($Users as $key => $user) {
                    $Users[$key]->permissions = $User->getRolePermissions($user->role);
                }
            }
        } else {
            $Users = User::join('roles', 'users.role', '=', 'roles.idroles');
        }

        return $Users;
    }

    public function partyEligible()
    {
        //Tested!
        return DB::select(DB::raw('SELECT
                  users.id AS id,
                  users.name,
                  users.email,
                  roles.role
              FROM '.$this->table.'
              INNER JOIN roles ON roles.idroles = users.role
              WHERE users.role > 1
              ORDER BY users.name ASC'));
    }

    public function inGroup($group)
    {
        //Tested!
        return DB::select(DB::raw('SELECT
                    users.id AS id,
                    users.name,
                    users.email,
                    roles.role
                FROM '.$this->table.'
                INNER JOIN roles ON roles.idroles = users.role
                WHERE users.role > 1
                    AND users.id IN
                        (SELECT `user` FROM users_groups WHERE `group` = :group)
                ORDER BY users.name ASC'), array('group' => $group));
    }


    public function isInGroup($groupId)
    {
        return UserGroups::where('user', $this->id)
            ->where('group', $groupId)
            ->exists();
    }

    //This create user function is already done by the RegisterController

    /** check if email is already in the database **/
    public function checkEmail($email)
    {
        //Tested!

        $r = DB::select(DB::raw('SELECT COUNT(id) AS emails FROM '.$this->table.' WHERE email = :email'), array('email' => $email));

        return ($r[0]->emails > 0) ? false : true;
    }

    public function scopeNearbyRestarters($query, $latitude, $longitude, $radius = 20)
    {
        return $query->select(DB::raw('*, ( 6371 * acos( cos( radians('.$latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$longitude.') ) + sin( radians('.$latitude.') ) * sin( radians( latitude ) ) ) ) AS distance'))
                        ->whereNotNull('location')
                          ->whereNotNull('latitude')
                            ->whereNotNull('longitude')
                              ->having('distance', '<=', $radius);
    }

    /*
    *
    * This allows us to check whether consent has been provided - couples with custom middleware
    *
    */
    public function hasUserGivenConsent()
    {
        if (is_null($this->consent_future_data)) {
            return false;
        }

        //Past data is only required for users who created their account prior to the Laravel app launch
        if (is_null($this->consent_past_data) && strtotime($this->created_at) <= strtotime(date('2018-06-26'))) {
            return false;
        }

        if (is_null($this->consent_gdpr)) {
            return false;
        }

        return true;
    }

    /**
     * @return Date when the user last logged in
     */
    public function lastLogin()
    {
        return new \Carbon\Carbon($this->last_login_at);
    }

    /**
     * Anonymise user details before soft deletion.
     */
    public function anonymise()
    {
        $this->name = 'Deleted User';
        $this->email = $this->id.'@deleted.invalid';
        $this->username = $this->id.'-deleted';

        // TODO: country, city, gender, age, also required?
        return $this;
    }

    /**
     * Attempt to get first name from full name.
     */
    public function getFirstName()
    {
        if ($this->name == '') {
            return '';
        }

        $nameParts = explode(' ', $this->name);

        return $nameParts[0];
    }


    public function existsOnDiscourse()
    {
        try {
            $client = app('discourse-client');
            $emailToSearchFor = trim($this->email);
            $endpoint = sprintf('/admin/users/list/all.json?email=%s', $emailToSearchFor);
            $response = $client->request('GET', $endpoint);
            $discourseResult = json_decode($response->getBody());

            return count($discourseResult) >= 1;
        } catch (\Exception $ex) {
            return false;
        }
    }


    /**
     * Convert the user's role to be a Host.
     *
     * Currently, the only role that should be convertible to a Host is a Restarter.  Admins and NetworkCoordinators should not be downgraded, and if already a Host, no need to change it.
     */
    public function convertToHost()
    {
        if ($this->role == Role::RESTARTER) {
            $this->role = Role::HOST;
            $this->save();
        }
    }

    /**
     * Getter to display the user's repair network with options to provide the network name/slug
     *
     * NOT IN USE, decided on a more straightforward approach could be useful in the future
     * @author Dean Appleton-Claydon
     * @date   2019-03-22
     * @param  boolean $string
     * @param  boolean $slug
     * @return mixed either string or int
     */
    public function getRepairNetwork($string = false, $slug = false)
    {
        if ($string == true) {
            switch ($this->repair_network) {
                case 2:
                    $network = 'Repair Share';

                    break;
                default:
                    $network = 'Restarters';
            }

            if ($slug == true) {
                return str_slug($network);
            }

            return $network;
        }

        return $this->repair_network;
    }


    public function groupTag()
    {
        return $this->hasOne(GroupTags::class, 'id', 'access_group_tag_id');
    }


    /**
     * Generate a username based on the user's name, and set it against this user.
     *
     * Attempts to mimic the same logic as Discourse username generation.
     */
    public function generateAndSetUsername()
    {
        if (empty($this->name)) {
            throw new \Exception("Name is empty");
        }

        $name = $this->name;
        $name = trim($name);
        $name = transliterator_transliterate('Any-Latin;Latin-ASCII;', $name);

        $name_parts = explode(' ', $name);

        $desired_username = implode("_", $name_parts);

        if (!(User::where('username', '=', $desired_username)->exists())) {
            $username = $desired_username;
        } else { // someone already has the desired username
            $username = $desired_username.'_'.$this->id;
        }

        $this->username = $username;
    }

    public function isDripSubscriber()
    {
      return ! is_null($this->drip_subscriber_id);
    }

    public function hasRole($roleName)
    {
        $usersRole = $this->role()->first()->role;

        // Root assumed to have all available roles.
        if ($usersRole == 'Root') {
            return true;
        }

        if ($usersRole == $roleName) {
            return true;
        }

        return false;
    }

    public function hasPermission($permissionName)
    {
        $has_permission = UsersPermissions::join('permissions', 'permissions.idpermissions', '=', 'users_permissions.permission_id')
                        ->where('users_permissions.user_id', $this->id)
                        ->where('permissions.slug', $permissionName)
                        ->first();

        if (empty($has_permission)) {
            return false;
        }

        return true;
    }

    public function getTalkProfileUrl()
    {
        return env('DISCOURSE_URL').'/u/'.$this->username;
    }

    // If just one of the networks that the group is a member of
    // should push to Wordpress, then we should push.
    public function changesShouldPushToZapier()
    {
        $network = Network::find($this->repair_network);

        return ($network->include_in_zapier == true);
    }

    public function networks()
    {
        return $this->belongsToMany(Network::class, 'user_network', 'user_id', 'network_id');
    }

    public function isCoordinatorOf($network)
    {
        return $this->networks->contains($network);
    }

    public function isCoordinatorForGroup($group)
    {
        foreach ($group->networks as $groupNetwork) {
            foreach ($this->networks as $userNetwork) {
                if ($groupNetwork->name == $userNetwork->name) {
                    return true;
                }
            }
        }

        return false;
    }

    public function groupsInChargeOf()
    {
        $groupsUserIsInChargeOf = collect([]);

        if ($this->hasRole('Host')) {
            $groupsUserIsInChargeOf = Group::join('users_groups', 'groups.idgroups', '=', 'users_groups.group')
                                    ->where('user', $this->id)
                                    ->where('role', 3)
                                    ->get();
        } else if ($this->hasRole('NetworkCoordinator')) {
            foreach ($this->networks as $network) {
                foreach ($network->groups as $group) {
                    $groupsUserIsInChargeOf->push($group);
                }
            }
        } else if ($this->hasRole('Administrator')) {
            $groupsUserIsInChargeOf = Group::all();
        }

        return $groupsUserIsInChargeOf;
    }
}
