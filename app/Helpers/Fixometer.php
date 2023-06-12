<?php

namespace App\Helpers;

use App;
use App\Barrier;
use App\Group;
use App\Party;
use App\Permissions;
use App\Role;
use App\Skills;
use App\User;
use App\UserGroups;
use App\UsersPermissions;
use App\UsersPreferences;
use Auth;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Request;

class Fixometer
{
    public static function allAges()
    {
        $ages = ['' => ''];

        for ($i = intval(date('Y')) - 18; $i > intval(date('Y', strtotime('- 100 years'))); $i--) {
            $ages[$i] = $i;
        }

        return $ages;
    }

    public static function allRoles()
    {
        return Role::all();
    }

    public static function allPermissions()
    {
        return Permissions::all();
    }

    public static function hasRole($user, $role)
    {
        if (is_null($user)) {
            // Use currently logged in user.
            $user = Auth::user();
        }

        if ($user) {
            $usersRole = $user->role()->first()->role;

            if ($usersRole == 'Root') {
                return true;
            }

            if ($usersRole == ucwords($role)) {
                return true;
            }
        }
        return false;
    }

    public static function barChartValue($portion, $total)
    {
        if ((int) $portion > 0) {
            return round((($portion / $total) * 100), 2) - 15;
        }

        return -15;
    }

    public static function featureIsEnabled($feature)
    {
        return $feature === true;
    }

    public static function userHasViewPartyPermission($partyId, $userId = null)
    {
        $party = Party::findOrFail($partyId);
        $group = $party->theGroup;

        if ($group->approved) {
            // Events on approved groups are always visible, whether or not the event has been approved.
            return true;
        }

        // The group is not approved.  Events are only visible to:
        // - administrators
        // - (relevant) network coordinators
        // - hosts of the relevant group
        if (is_null($userId)) {
            if (empty(Auth::user())) {
                return false;
            } else {
                $userId = Auth::user()->id;
            }
        }

        $user = User::findOrFail($userId);

        if (self::hasRole($user, 'Administrator')) {
            return true;
        }

        if (self::hasRole($user, 'NetworkCoordinator')) {
            $group = $party->theGroup;
            foreach ($group->networks as $network) {
                if ($network->coordinators->contains($user)) {
                    return true;
                }
            }
        }

        if (self::userIsHostOfGroup($group->idgroups, $userId)) {
            return true;
        }

        return false;
    }

    public static function userHasEditPartyPermission($partyId, $userId = null)
    {
        $party = Party::findOrFail($partyId);
        $group = $party->theGroup;

        if (is_null($userId)) {
            if (empty(Auth::user())) {
                return false;
            } else {
                $userId = Auth::user()->id;
            }
        }

        $user = User::findOrFail($userId);

        if (self::hasRole($user, 'Administrator')) {
            return true;
        }

        if (self::hasRole($user, 'NetworkCoordinator')) {
            foreach ($group->networks as $network) {
                if ($network->coordinators->contains($user)) {
                    return true;
                }
            }
        }

        if (self::userIsHostOfGroup($group->idgroups, $userId)) {
            return true;
        }

        return false;
    }

    public static function userHasDeletePartyPermission($partyId, $userId = null)
    {
        if (is_null($userId)) {
            if (empty(Auth::user())) {
                return false;
            } else {
                $userId = Auth::user()->id;
            }
        }

        $user = User::find($userId);

        if (self::hasRole($user, 'Administrator')) {
            return true;
        }

        $group = Party::find($partyId)->theGroup;

        if (self::hasRole($user, 'Host') && self::userIsHostOfGroup($group->idgroups, $userId)) {
            return true;
        }

        if (self::hasRole($user, 'NetworkCoordinator')) {
            foreach ($group->networks as $network) {
                if ($network->coordinators->contains($user)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function userCanApproveEvent($eventId, $userId = null)
    {
        if (is_null($userId)) {
            $userId = Auth::user()->id;
        }
        $user = User::find($userId);

        if (self::hasRole($user, 'Administrator')) {
            return true;
        }

        if (self::hasRole($user, 'NetworkCoordinator')) {
            $group = Party::find($eventId)->theGroup;
            foreach ($group->networks as $network) {
                if ($network->coordinators->contains($user)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function userHasEditEventsDevicesPermission($partyId, $userId = null)
    {
        if (is_null($userId)) {
            $userId = Auth::user()->id;
        }
        $user = User::find($userId);

        // Admins can do anything.
        if (self::hasRole($user, 'Administrator')) {
            return true;
        }

        if (self::hasRole($user, 'NetworkCoordinator')) {
            $group = Party::find($partyId)->theGroup;
            foreach ($group->networks as $network) {
                if ($network->coordinators->contains($user)) {
                    return true;
                }
            }
        }

        // Hosts of a group can do anything with events from that group.
        $group_id_of_event = Party::where('idevents', $partyId)->value('group');
        if (self::userIsHostOfGroup($group_id_of_event, $userId)) {
            return true;
        }

        // If you attended an event, you can edit devices.
        if (DB::table('events_users')->where('event', $partyId)->where('user', $userId)->where('status', 1)->exists()) {
            return true;
        }

        return false;
    }

    public static function userCanCreateEvents($user)
    {
        if (is_null($user)) {
            $user = Auth::user();
        }

        if (!$user) {
            return false;
        }

        $usersRole = $user->role()->first()->role;
        $superusers = ['Root', 'Administrator', 'NetworkCoordinator'];

        if (in_array($usersRole, $superusers)) {
            return true;
        }

        $userIsHostOfAGroup = UserGroups::where('user', $user->id)
            ->where('role', 3)
            ->count() > 0;

        if ($userIsHostOfAGroup) {
            return true;
        }

        return false;
    }

    public static function userIsHostOfGroup($groupId, $userId)
    {
        $user_group_association = DB::table('users_groups')
            ->where('group', $groupId)
            ->where('user', $userId)
            ->where('role', 3)
            ->whereNull('deleted_at')
            ->first();

        if (! empty($user_group_association)) {
            return true;
        }

        return false;
    }

    /** Prints out Bootstrap alerts
     * finds key of response and
     * uses it to format the alert
     * as wished
     * */
    public static function printResponse($response, $dismissible = true)
    {
        foreach ($response as $type => $text) {
            switch ($type) {
                case 'success':
                    $icon = 'check';

                    break;
                case 'danger':
                    $icon = 'exclamation-triangle';

                    break;
                case 'warning':
                    $icon = 'exclamation-circle';

                    break;
                case 'info':
                    $icon = 'info';

                    break;

                default:
                    $icon = '';
                    break;
            }
            echo '<div class="alert alert-'.$type;
            if ($dismissible) {
                echo '  alert-dismissible';
            }
            echo '" role="alert">
                  <!--<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>-->
                  <i class="fa fa-'.$icon.'"></i> '.$text.'

              </div>';
        }
    }

    /**
     * Rearranges an array
     * used to "reflow" the $_FILES array
     * with multiple entries
     * */
    public static function rearrange($arr)
    {
        foreach ($arr as $key => $all) {
            foreach ($all as $i => $val) {
                $new[$i][$key] = $val;
            }
        }

        return $new;
    }

    /**
     * check for existence of images associated with
     * this particular object of $type and $id
     * return boolean, or full results if requested
     * */
    public static function hasImage($id, $table, $return_rows = false)
    {
        switch ($table) {
            case 'users':
                $object = env('TBL_USERS');

                break;
            case 'groups':
                $object = env('TBL_GROUPS');

                break;
            case 'events':
                $object = env('TBL_EVENTS');

                break;
            case 'devices':
                $object = env('TBL_DEVICES');

                break;
            default:
                $object = false;

                break;
        }
        if ($object) {
            $sql = '
                      SELECT * FROM `images`
                          INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                          WHERE `xref`.`object_type` = 5
                          AND `xref`.`reference_type` = :object
                          AND `xref`.`reference` = :id
                          GROUP BY `images`.`path`';

            try {
                if ($return_rows) {
                    return DB::select(DB::raw($sql), ['id' => $id, 'object' => $object]);
                }

                return count(DB::select(DB::raw($sql), ['id' => $id, 'object' => $object])) > 0 ? true : false;
            } catch (\Illuminate\Database\QueryException $e) {
                return db($e);
            }
        }
    }

    /**
     * check for existence of images associated with
     * this particular object of $type and $id
     * return boolean, or full results if requested
     * */
    public static function removeImage($id, $table, $image)
    {
        switch ($table) {
            case 'users':
                $object = env('TBL_USERS');

                break;
            case 'groups':
                $object = env('TBL_GROUPS');

                break;
            case 'events':
                $object = env('TBL_EVENTS');

                break;
            case 'devices':
                $object = env('TBL_DEVICES');

                break;
            default:
                $object = false;

                break;
        }

        if ($object) {
            /** delete cross references **/
            $sql = 'DELETE FROM `xref`
                  WHERE
                  `xref`.`object_type` = 5 AND
                  `xref`.`reference_type` = :object AND
                  `xref`.`reference` = :id ';

            DB::delete(DB::raw($sql), ['id' => $id, 'object' => $object]);

            /** delete image from db **/
            $sql = 'DELETE FROM `images` WHERE `images`.`idimages` = :image';

            DB::delete(DB::raw($sql), ['image' => $image->idimages]);

            /** delete image from disk **/
            unlink($_SERVER['DOCUMENT_ROOT'].'/uploads/'.$image->path);
        }
    }

    public static function getRoleName($id)
    {
        return Role::where('idroles', $id)->first()->role;
    }

    public static function getAllCountries($locale = null)
    {
        $ret = [];

        if (!$locale) {
            $locale = \App::getLocale();
        }

        foreach (\Lang::get('countries', [], $locale) as $key => $value) {
            $ret[ $key ] = $value;
        }

        return $ret;
    }

    public static function getCountryFromCountryCode($countryCode)
    {
        $countriesArray = self::getAllCountries();

        if (array_key_exists($countryCode, $countriesArray)) {
            return $countriesArray[$countryCode];
        }

        return '';
    }

    public static function translateCountry($country, $countries = null) {
        // Countries are stored in the DB as English names, so we need to translate them.
        if (!$countries) {
            // This can be passed by the caller to speed things up in a loop.
            $countries = array_flip(App\Helpers\Fixometer::getAllCountries('en'));
        }

        if (array_key_exists($country, $countries)) {
            $code = $countries[$country];
            $country = \Lang::get('countries.' . $code, [], \App::getLocale());
        }

        return $country;
    }

    public static function skillCategories()
    {
        return [
            '1' => 'Organising skills - please select at least one if you’d like to host events',
            '2' => 'Technical skills',
        ];
    }

    public static function allSkills()
    {
        $return[1] = Skills::where('category', 1)->get();
        $return[2] = Skills::where('category', 2)->get();

        return $return;
    }

    public static function footprintReliability()
    {
        return [
            '1' => 'Very poor',
            '2' => 'Poor',
            '3' => 'Fair',
            '4' => 'Good',
            '5' => 'Very good',
            '6' => 'N/A',
        ];
    }

    public static function categoryCluster()
    {
        return [
            1 => 'Computers and Home Office',
            2 => 'Electronic Gadgets',
            3 => 'Home Entertainment',
            4 => 'Kitchen and Household Items',
          ];
    }

    public static function loginRegisterStats()
    {
        $Party = new \App\Party;
        $Device = new \App\Device;

        $stats = [];
        if (\Cache::has('all_stats')) {
            $stats = \Cache::get('all_stats');

            // We've seen a Sentry problem which I can only see happening if there was invalid data in the cache.
            if (
                ! $stats ||
                ! array_key_exists('allparties', $stats) ||
                ! array_key_exists('waste_stats', $stats) ||
                ! array_key_exists('device_count_status', $stats)
            ) {
                $stats = [];
            }
        }

        if ($stats == []) {
            $stats['allparties'] = $Party->ofThisGroup('admin', true, false);
            $stats['waste_stats'] = \App\Helpers\LcaStats::getWasteStats();
            $stats['device_count_status'] = $Device->statusCount();
            \Cache::put('all_stats', $stats, 7200);
        }

        return $stats;
    }

    public static function userHasEditGroupPermission($group_id, $user_id, $role = 3)
    {
        // Admins can do anything.
        if (self::hasRole(User::find($user_id), 'Administrator')) {
            return true;
        }

        return ! empty(\App\UserGroups::where('group', $group_id)
            ->where('user', $user_id)
            ->where('role', $role)
            ->first());
    }

    public static function buildSortQuery($columnName)
    {
        $newSortDir = 'asc';

        if (Request::has('sort') && Request::input('sort') == $columnName) {
            $existingSortDir = Request::input('sortdir');
            if ($existingSortDir == 'asc') {
                $newSortDir = 'desc';
            } elseif ($existingSortDir == 'desc') {
                $newSortDir = 'asc';
            }
        }

        $existing = Request::except(['sort', 'sortdir']);

        return http_build_query($existing).'&sort='.$columnName.'&sortdir='.$newSortDir;
    }

    /*
     *
     * Looks at skills (object) and determines role (int)
     *
     */
    public static function skillsDetermineRole($skills = null)
    {
        if (is_null($skills)) {
            $has_host_skills = 0;
        } else {
            $has_host_skills = \App\Skills::where('category', 1)->whereIn('id', $skills)->count();
        }

        // If number of host skills is greater than or equal to amount, then give host role
        if ($has_host_skills >= 1) {
            $role = 3;
        } else {
            $role = 4;
        }

        return $role;
    }

    /** checks if user has permission **/
    public static function hasPermission($slug)
    {

        // Check if guest
        if (Auth::guest()) {
            return false;
        }

        // Check if Permission Exists
        $has_permission = UsersPermissions::join('permissions', 'permissions.idpermissions', '=', 'users_permissions.permission_id')
            ->where('users_permissions.user_id', Auth::user()->id)
            ->where('permissions.slug', $slug)
            ->first();

        // Does user have it?
        if (empty($has_permission)) {
            return false;
        }

        return true;
    }

    /**
     * Returns users who have a particular preference by slug
     *
     * @param $slug
     * @return Collection
     */
    public static function usersWhoHavePreference($slug)
    {
        return User::join('users_preferences', 'users_preferences.user_id', '=', 'users.id')
            ->join('preferences', 'preferences.id', '=', 'users_preferences.preference_id')
            ->where('preferences.slug', $slug)
            ->select('users.*')
            ->get();
    }

    public static function notificationClasses($modal)
    {
        $modal = str_replace("App\Notifications\\", '', $modal);

        $user_array = [
            'AdminNewUser',
            'ResetPassword',
        ];

        $event_array = [
            'EventConfirmed',
            'EventDevices',
            'EventRepairs',
            'JoinEvent',
            'AdminModerationEvent',
            'NotifyRestartersOfNewEvent',
            'RSVPEvent',
            'AdminWordPressCreateEventFailure',
            'AdminWordPressEditEventFailure',
        ];

        $group_array = [
            'GroupConfirmed',
            'JoinGroup',
            'AdminModerationGroup',
            'NewGroupMember',
            'NewGroupWithinRadius',
            'AdminWordPressCreateGroupFailure',
            'AdminWordPressEditGroupFailure',
        ];

        $device_array = [
            'NotifyAdminNoDevices',
            'AdminAbnormalDevices',
        ];

        if (in_array($modal, $user_array)) {
            return 'card__restart';
        } elseif (in_array($modal, $event_array)) {
            return 'card__parties';
        } elseif (in_array($modal, $group_array)) {
            return 'card__groups';
        } elseif (in_array($modal, $device_array)) {
            return 'card__devices';
        }
    }

    public static function allBarriers()
    {
        return Barrier::all();
    }

    /**
     * [generateUniqueShareableCode description]
     * Generate a unique Shareable Code from a random string,
     * If the Code already exists then loop again!
     *
     * @author Christopher Kelker - @date 2019-03-22
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @param   [type]      $row
     * @param   [type]      $model
     * @return  [type]
     */
    public static function generateUniqueShareableCode($model, $column)
    {
        do {
            $random = Str::random(30);
        } while ($model::where($column, $random)->exists());

        return $random;
    }
}
