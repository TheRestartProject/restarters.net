<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Helpers\Fixometer;
use App\Network;
use App\UserGroups;
use App\UsersPermissions;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Contracts\Translation\HasLocalePreference;

class User extends Authenticatable implements Auditable, HasLocalePreference
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    // Use the Authorizable trait so that we can call can() on a user to evaluation policies.
    use \Illuminate\Foundation\Auth\Access\Authorizable;

    // Radius within which a group has to be for it to be considered nearby.
    const NEARBY_KM = 50;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'recovery', 'recovery_expires', 'language', 'repair_network', 'location', 'age', 'gender', 'country_code', 'newsletter', 'drip_subscriber_id', 'invites', 'biography', 'consent_future_data', 'consent_past_data', 'consent_gdpr', 'number_of_logins', 'latitude', 'longitude', 'last_login_at', 'api_token', 'access_group_tag_id', 'calendar_hash', 'repairdir_role', 'mediawiki', 'username',
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
        return $this->hasOne(\App\Role::class, 'idroles', 'role');
    }

    public function repairdir_role()
    {
        // Make sure we don't return a null value.  The client select would struggle with null values.
        return $this->repairdir_role ? $this->repairdir_role : Role::REPAIR_DIRECTORY_NONE;
    }

    public function userSkills()
    {
        return $this->hasMany(\App\UsersSkills::class, 'user', 'id');
    }

    public function skills()
    {
        return $this->belongsToMany(\App\Skills::class, 'users_skills', 'user', 'skill');
    }

    // This is an incorrect relationship, but leaving it here for now as it is used in a strange way in two legacy places and apparently working in those instances somehow.
    // Use skills() for correct belongsToMany relationship.
    public function skillsold()
    {
        return $this->belongsToMany(\App\UsersSkills::class, 'users_skills', 'user', 'skill');
    }

    public function hasSkill($skill)
    {
        return $this->skills->contains($skill->id);
    }

    public function assignSkill($skill)
    {
        if (! $this->hasSkill($skill->id)) {
            $this->skills()->attach($skill->id);
        }
    }

    public function groups()
    {
        return $this->belongsToMany(\App\Group::class, 'users_groups', 'user', 'group');
    }

    /**
     * Return a list of repair groups near the user that they are not already a member of.
     *
     * @param int $numberOfGroups How many groups to return
     * @param string String of minimum creation date
     */
    public function groupsNearby($numberOfGroups = 10, $createdSince = null, $nearby = self::NEARBY_KM)
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            return [];
        }

        $groupsNearbyQuery = Group::select(
            DB::raw('*, ( 6371 * acos( cos( radians('.$this->latitude.') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians('.$this->longitude.') ) + sin( radians('.$this->latitude.') ) * sin( radians( latitude ) ) ) ) AS dist')
        )->where(function ($q) {
            $q->whereNull('archived_at');

            // Only show approved groups.
            $q->where('approved', true);
        })->having('dist', '<=', $nearby)
            ->groupBy('idgroups');

        if ($createdSince) {
            $groupsNearbyQuery->whereDate('created_at', '>=', date('Y-m-d', strtotime($createdSince)));
        }

        $groups = $groupsNearbyQuery->orderBy('dist', 'ASC')
            ->take($numberOfGroups)
            ->get();

        // Expand the image
        $groupsNearby = [];

        if ($groups) {
            foreach ($groups as $group) {
                $group_image = $group->groupImage;
                if (is_object($group_image) && is_object($group_image->image)) {
                    $group_image->image->path;
                }

                // Store for later retrieval.  This is unusual because the value is not stored in the table.
                $group->setDistanceAttribute($group->dist);

                $groupsNearby[] = $group;
            }
        }

        return $groupsNearby;
    }

    public function preferences()
    {
        return $this->belongsToMany(self::class, 'users_preferences', 'user_id', 'preference_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(self::class, 'users_permissions', 'user_id', 'permission_id');
    }

    public function addPreference($slug)
    {
        /** @var Preferences $preference */
        $preference = Preferences::where(['slug' => $slug])->first();
        UsersPreferences::create([
            'user_id' => $this->getKey(),
            'preference_id' => $preference->getKey(),
        ]);
    }

    public function getRolePermissions($role)
    {
        return DB::select(DB::raw('SELECT p.idpermissions, p.permission, r.idroles, r.role FROM permissions AS p
                INNER JOIN roles_permissions AS rp ON rp.permission = p.idpermissions
                INNER JOIN roles AS r ON rp.role= r.idroles
                WHERE r.role = :role'), ['role' => $role]);
    }

    public function getUserGroups($user)
    {
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` AS `u`
                INNER JOIN `users_groups` AS `ug` ON `ug`.`user` = `u`.`id`
                INNER JOIN `groups` AS `g` ON `ug`.`group` = `g`.`idgroups`
                WHERE `u`.`id` = :id'), ['id' => $user]);
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
            return self::where('users.id', '=', $id)
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

        if (! $eloquent) {
            $Users = DB::select(DB::raw('SELECT users.id AS id, users.name, users.email, roles.role FROM users
                  INNER JOIN roles ON roles.idroles = users.role WHERE users.deleted_at IS NULL
                  ORDER BY users.id ASC')); //INNER JOIN sessions ON sessions.user = users.id, UNIX_TIMESTAMP(sessions.modified_at) AS modified_at

            if (is_array($Users)) {
                $User = new self;
                foreach ($Users as $key => $user) {
                    $Users[$key]->permissions = $User->getRolePermissions($user->role);
                }
            }
        } else {
            $Users = self::join('roles', 'users.role', '=', 'roles.idroles');
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
                ORDER BY users.name ASC'), ['group' => $group]);
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

        $r = DB::select(DB::raw('SELECT COUNT(id) AS emails FROM '.$this->table.' WHERE email = :email'), ['email' => $email]);

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
        if (! config('restarters.features.discourse_integration')) {
            return false;
        }

        return $this->username != NULL;
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
            throw new \Exception('Name is empty');
        }

        $name = $this->name;
        $name = trim($name);
        $name = transliterator_transliterate('Any-Latin;Latin-ASCII;', $name);

        $name_parts = explode(' ', $name);

        $desired_username = implode('_', $name_parts);

        // Discourse doesn't allow repeated special characters - see https://github.com/discourse/discourse/blob/main/app/models/username_validator.rb.
        $desired_username = preg_replace('/[-_.]{2,}/', '_', $desired_username);

        if (! (self::where('username', '=', $desired_username)->exists())) {
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

    public function isRepairDirectoryNone()
    {
        return $this->repairdir_role == Role::REPAIR_DIRECTORY_NONE;
    }

    public function isRepairDirectorySuperAdmin()
    {
        return $this->repairdir_role == Role::REPAIR_DIRECTORY_SUPERADMIN;
    }

    public function isRepairDirectoryRegionalAdmin()
    {
        return $this->repairdir_role == Role::REPAIR_DIRECTORY_REGIONAL_ADMIN;
    }

    public function isRepairDirectoryEditor()
    {
        return $this->repairdir_role == Role::REPAIR_DIRECTORY_EDITOR;
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

        return $network->include_in_zapier;
    }

    public function isCoordinatorOf($network)
    {
        return $this->networks->contains($network);
    }

    public function networks()
    {
        return $this->belongsToMany(Network::class, 'user_network', 'user_id', 'network_id');
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
        $groupsUserIsInChargeOf = Group::join('users_groups', 'groups.idgroups', '=', 'users_groups.group')
            ->where('user', $this->id)
            ->where('role', 3)
            ->get();

        if ($this->hasRole('NetworkCoordinator')) {
            foreach ($this->networks as $network) {
                foreach ($network->groups as $group) {
                    $groupsUserIsInChargeOf->push($group);
                }
            }
        } elseif ($this->hasRole('Administrator')) {
            $groupsUserIsInChargeOf = Group::all();
        }

        return $groupsUserIsInChargeOf->unique();
    }

    public function ensureAPIToken()
    {
        // Generate an API token if we don't already have one.
        $api_token = $this->api_token;

        if (! $api_token) {
            $api_token = \Illuminate\Support\Str::random(60);
            $this->api_token = $api_token;
            $this->save();
        }

        return $api_token;
    }

    /**
     * Get the user's preferred locale.  This is automatically used by email notifications.
     *
     * @return string
     */
    public function preferredLocale()
    {
        // TODO Use of preferredLocale should mean we don't have to explicitly pass the locale.  But that isn't
        // working.  So at the moment we are passing a locale explicitly in the translations in the notifications
        // to users (not admins).
        return $this->language;
    }

    public static function userCanSeeEvent($user, $event) {
        // We need to filter based on approved visibility:
        // - where the group is approved, this event is visible
        // - where the group is not approved, this event is visible to network coordinators or group hosts.
        $amHost = $user && $user->hasRole('Host');
        $admin = $user && $user->hasRole('Administrator');

        $group = Group::find($event->group);

        if (($event->approved && $group->approved) ||
            $admin ||
            ($user && $user->isCoordinatorForGroup($group)) ||
            ($amHost && $user && Fixometer::userIsHostOfGroup($group->idgroups, $user->id))) {
            return true;
        }

        return false;
    }
}
