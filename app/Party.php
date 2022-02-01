<?php

namespace App;

use App\Events\ApproveEvent;
use App\EventUsers;
use App\Helpers\Fixometer;
use App\Notifications\NotifyRestartersOfNewEvent;
use App\Notifications\EventConfirmed;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Notification;
use OwenIt\Auditing\Contracts\Auditable;

class Party extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'events';
    protected $primaryKey = 'idevents';
    protected $fillable = [
        'group',
        'event_start_utc',
        'event_end_utc',
        'event_date',
        'start',
        'end',
        'venue',
        'location',
        'latitude',
        'longitude',
        'free_text',
        'pax',
        'volunteers',
        'hours',
        'wordpress_post_id',
        'created_at',
        'updated_at',
        'shareable_code',
        'online',
        'discourse_thread',
        'devices_updated_at',
        'link',
        'timezone'
    ];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'frequency', 'group', 'group', 'user_id', 'wordpress_post_id', 'cancelled', 'devices_updated_at'];

    // Append data to Model
    protected $appends = ['participants', 'ShareableLink'];

    //Getters
    public function findAll()
    {
        return DB::select(DB::raw('SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP(`event_start_utc`) AS `event_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`link`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`pax`,
                    `e`.`volunteers`,
                    `e`.`free_text`,
                    `e`.`hours`,
                    `e`.`wordpress_post_id`,
                    `e`.`discourse_thread`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`
                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                ORDER BY `e`.`event_start_utc` DESC'));
    }

    public function findAllSearchable()
    {
        // TODO Can this be replaced by Partp::past?
        return DB::select(DB::raw('SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP(`event_start_utc`) AS `event_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`link`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`pax`,
                    `e`.`free_text`,
                    `e`.`hours`,
                    `e`.`discourse_thread`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`
                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                WHERE `event_end_utc` < NOW()
                ORDER BY `e`.`event_start_utc` DESC'));
    }

    public function findThis($id, $devices = false)
    {
        $sql = 'SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP(`event_start_utc`) AS `event_date` ,
                    UNIX_TIMESTAMP(`event_start_utc`) AS `event_timestamp`,
                    UNIX_TIMESTAMP(`event_end_utc`) AS `event_end_timestamp`,
                    `e`.`start` AS `start`,
                    `e`.`end` AS `end`,
                    `e`.`venue`,
                    `e`.`link`,
                    `e`.`location`,
                    `e`.`latitude`,
                    `e`.`longitude`,
                    `e`.`group`,
                    `e`.`pax`,
                    `e`.`volunteers`,
                    `e`.`hours`,
                    `e`.`free_text`,
                    `e`.`wordpress_post_id`,
                    `e`.`online`,
                    `e`.`discourse_thread`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`

                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                WHERE `e`.`idevents` = :id
                ORDER BY `e`.`event_start_utc` DESC';

        $party = DB::select(DB::raw($sql), ['id' => $id]);

        if ($devices) {
            $devices = new Device;
            $party[0]->devices = $devices->ofThisEvent($party[0]->id);
        }

        return $party;
    }

    public function createUserList($party, $users)
    {
        /* reset user list **/
        if (! self::deleteUserList($party)) {
            return false;
        }
        $sql = 'INSERT INTO `events_users`(`event`, `user`) VALUES (:party, :user)';
        foreach ($users as &$user) {
            try {
                DB::insert(DB::raw($sql), ['party' => $party, 'user' => $user]);
            } catch (\Illuminate\Database\QueryException $e) {
                dd($e);
            }
        }
    }

    public function deleteUserList($party)
    {
        return DB::delete(DB::raw('DELETE FROM `events_users` WHERE `event` = :party'), ['party' => $party]);
    }

    public function ofThisUser($id, $only_past = false, $devices = false)
    {
        //Tested
        $sql = 'SELECT *, `e`.`venue` AS `venue`, `e`.`link` AS `link`, `e`.`location` as `location`, UNIX_TIMESTAMP(`event_start_utc`) AS `event_timestamp`
                FROM `'.$this->table.'` AS `e`
                INNER JOIN `events_users` AS `eu` ON `eu`.`event` = `e`.`idevents`
                INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`
                LEFT JOIN (
                    SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                    FROM `devices` AS `dv`
                    GROUP BY  `dv`.`event`
                ) AS `d` ON `d`.`event` = `e`.`idevents`
                WHERE `eu`.`user` = :id';
        if ($only_past) {
            $sql .= ' AND `e`.`event_end_utc` < NOW()';
        }
        $sql .= ' ORDER BY `e`.`event_start_utc` DESC';

        try {
            $parties = DB::select(DB::raw($sql), ['id' => $id]);
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }

        if ($devices) {
            $devices = new Device;
            foreach ($parties as $i => $party) {
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }
        }

        return $parties;
    }

    public function ofThisGroup2($group = 'admin', $only_past = false, $devices = false)
    {
        //Tested
        $sql = 'SELECT
                    *,
	`e`.`venue` AS `venue`, `e`.`link` AS `link`, `e`.`location` as `location`,
                    `g`.`name` AS group_name,
                    UNIX_TIMESTAMP(e.`event_start_utc`) ) AS `event_timestamp`
                FROM `'.$this->table.'` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        if (is_numeric($group) && $group != 'admin') {
            $sql .= ' WHERE `e`.`group` = :id ';
        }

        if ($only_past) {
            $sql .= ' AND `e`.`event_end_utc`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_start_utc` DESC';

        if (is_numeric($group) && $group != 'admin') {
            try {
                $parties = DB::select(DB::raw($sql), ['id' => $group]);
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

        if ($devices) {
            $devices = new Device;
            foreach ($parties as $i => $party) {
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }
        }

        return $parties;
    }

    public function ofTheseGroups($groups = 'admin', $only_past = false, $devices = false)
    {
        //Tested
        $sql = 'SELECT
                    *,
	`e`.`venue` AS `venue`, `e`.`link` AS `link`, `e`.`location` as `location`,
                    `g`.`name` AS group_name,
                    UNIX_TIMESTAMP(e.`event_start_utc`) ) AS `event_timestamp`
                FROM `'.$this->table.'` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        if (is_array($groups) && $groups != 'admin') {
            $sql .= ' WHERE `e`.`group` IN ('.implode(', ', $groups).') ';
        }

        if ($only_past) {
            $sql .= ' AND `e`.`event_end_utc` < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_start_utc` DESC';

        try {
            $parties = DB::select(DB::raw($sql));
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }

        if ($devices) {
            $devices = new Device;
            foreach ($parties as $i => $party) {
                $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
            }
        }

        return $parties;
    }

    public function ofThisGroup($group = 'admin', $only_past = false, $devices = false)
    {
        return self::when($only_past, function ($query) {
            // We only want the ones in the past.
            $now = date('Y-m-d H:i:s');
            return $query->whereDate('event_end_utc', '<', $now);
        })->when(is_numeric($group), function ($query) use ($group) {
            // For a specific group.  Note that 'admin' is not numeric so won't pass this test.
            return $query->where('group', $group);
        })->get();
    }

    public function findNextParties($group = null)
    {
        $sql = 'SELECT
                    `e`.`idevents`,
                    `e`.`venue`,
                    `e`.`link`,
                    `e`.`location`,
                    UNIX_TIMESTAMP(`e`.`event_start_utc`) AS `event_timestamp`,
                    `e`.`event_date` AS `plain_date`,
                    NOW() AS `this_moment`,
                    `e`.`start`,
                    `e`.`end`,
                    `e`.`latitude`,
                    `e`.`longitude`
                FROM `'.$this->table.'` AS `e`
                WHERE `e`.`event_end_utc` >= NOW()';

        if (! is_null($group)) {
            $sql .= ' AND `e`.`group` = :group ';
        }

        $sql .= ' ORDER BY `e`.`event_date` ASC
                LIMIT 10';

        if (! is_null($group)) {
            try {
                return DB::select(DB::raw($sql), ['group' => $group]);
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

    public function findLatest($limit = 10)
    {
        return DB::select(DB::raw('SELECT
                    `e`.`idevents`,
                    `e`.`venue`,
                    `e`.`link`,
                    `e`.`location`,
                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_date`,
                    `e`.`start`,
                    `e`.`end`,
                    `e`.`latitude`,
                    `e`.`longitude`
                FROM `'.$this->table.'` AS `e`
                ORDER BY `e`.`event_date` DESC
                LIMIT :limit'), ['limit' => $limit]);
    }

    public function attendees()
    {
        return DB::select(DB::raw('SELECT SUM(pax) AS pax FROM '.$this->table));
    }

    // Scopes.  Each scope should build on a previous scope, getting more specific as we go down this file.  That
    // isolates query logic more clearly.
    private function defaultUserIds(&$userids) {
        if (!$userids) {
            $userids = [ Auth::user()->id ];
        }
    }

    public function scopeUndeleted($query) {
        // This is the base scope.  Almost always we are only interested in seeing events which have not been
        // deleted.
        return $query->whereNull('events.deleted_at')
            ->orderBy('event_start_utc', 'DESC');
    }

    public function scopePast($query) {
        // A past event is an event where the end time is less than now.
        $query = $query->undeleted();
        $query = $query->whereDate('event_end_utc', '<', date('Y-m-d H:i:s'));
        return $query;
    }

    public function scopeFuture($query) {
        // A future event is an event where the start time is greater than now.
        $query = $query->undeleted();
        $query = $query->whereDate('event_start_utc', '>', date('Y-m-d H:i:s'));
        return $query;
    }

    public function scopeActive($query) {
        // An active event is an event which has started and not yet finished.
        $query = $query->undeleted();
        $now = date('Y-m-d H:i:s');
        $query = $query->whereDate('event_start_utc', '<=', $now)
            ->whereDate('event_end_utc', '<=', $now);
        return $query;
    }

    public function scopeApproved($query) {
        // wordpress_post_id indicates event approval.
        $query = $query->undeleted();
        $query = $query->whereNotNull('wordpress_post_id');
        return $query;
    }

    public function scopeHostFor($query, $userids = null) {
        // Events where this user is a host.
        $this->defaultUserIds($userids);
        $query = $query->undeleted();
        $query = $query->join('events_users AS hf', function ($join) use ($userids) {
            $join->on('hf.event', '=', 'events.idevents');
            $join->whereIn('hf.user', $userids);
            $join->where('hf.role', '=', Role::HOST);
        })->select('events.*');

        return $query;
    }

    public function scopeAttendingOrAttended($query, $userids = null) {
        // Events this user has attending/is attending.
        $this->defaultUserIds($userids);
        $query = $query->undeleted();
        $query = $query->join('events_users AS aoa', function ($join) use ($userids) {
            $join->on('aoa.event', '=', 'events.idevents');
            $join->whereIn('aoa.user', $userids);

            // Check the status so that we exclude any events we have been invited to but not confirmed.
            // status is a string, so using 'like' rather than = otherwise
            // those with an invite string starting with '1' are included.
            $join->where('aoa.status', 'like', '1');
        })->select('events.*');

        return $query;
    }

    public function scopeInvitedNotConfirmed($query, $userids = null) {
        // Events this user has been invited to but not confirmed.  Only interested in future events.
        $this->defaultUserIds($userids);
        $query = $query->future();
        $query = $query->join('events_users AS inceu', function ($join) use ($userids) {
            $join->on('inceu.event', '=', 'events.idevents');
            $join->whereIn('inceu.user', $userids);
            $join->where('inceu.status', '!=', 1);
        })->select('events.*');

        return $query;
    }

    public function scopeMemberOfGroup($query, $userids = null) {
        // Any approved events for groups that this user has joined (not just been invited to) and not left.
        $this->defaultUserIds($userids);
        $query = $query->approved();
        $query = $query->join('users_groups AS hfgug', 'hfgug.group', '=', 'events.group')
            ->where('hfgug.status', 1)
            ->whereNull('hfgug.deleted_at')
            ->whereIn('hfgug.user', $userids)
            ->select('events.*');
        return $query;
    }

    public function scopeHostOfGroup($query, $userids = null) {
        $query = $query->memberOfGroup()
            ->where('hfgug.role', '=', Role::HOST);
        return $query;
    }

    public function scopeForUser($query, $userids = null) {
        // Events that are relevant to a user are:
        // - ones they are a host for
        // - ones they have are attending
        // - ones for groups that they're a member of
        //
        // The queries here are not desperately efficient, but we're battling Eloquent a bit.  The data size is
        // low enough it's not really an issue.
        $this->defaultUserIds($userids);
        $hostFor = Party::hostFor($userids);
        $attending = Party::attendingOrAttended($userids);
        $memberOf = Party::memberOfGroup($userids);

        // In theory $query could contain something other than all().
        return $query->whereIn('idevents', $hostFor->
            union($attending)->
            union($memberOf)->pluck('idevents'))->
            select('events.*');
    }

    public function scopeFutureForUser($query, $userids = null) {
        $this->defaultUserIds($userids);
        $query = $query->forUser()->future($userids);
        return $query;
    }

    public function scopePastForUser($query, $userids = null) {
        $this->defaultUserIds($userids);
        $query = $query->forUser()->past($userids);
        return $query;
    }

    public function scopeForGroup($query, $idgroups) {
        // TODO This should probably move into Group, and be a scope in there.  But we've not yet rationalised the
        // scopes in Groups.
        $query->where('events.group', $idgroups);
        return $query;
    }

    /**
     * [scopeUpcomingEventsInUserArea description]
     * All upcoming events (greater than today) by a User's Location.
     * @author Christopher Kelker
     * @date   2019-05-30T10:15:36+010
     * @param  [type]                  $query
     * @param  [type]                  $user
     * @return [type]
     */
    public function scopeUpcomingEventsInUserArea($query, $user)
    {
        // We want to exclude groups which we are a member of, but include ones where we have been invited but
        // not yet joined.
        $user_group_ids = UserGroups::where('user', $user->id)->where('status', 1)->pluck('group')->toArray();

        return $this
      ->select(DB::raw('`events`.*, ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( events.latitude ) ) * cos( radians( events.longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( events.latitude ) ) ) ) AS distance'))
      ->join('groups', 'groups.idgroups', '=', 'events.group')
      ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
      ->where(function ($query) use ($user_group_ids) {
          $query->whereNotIn('events.group', $user_group_ids)
        ->whereDate('event_start_utc', '>=', date('Y-m-d H:i:s'));
      })
      ->having('distance', '<=', User::NEARBY_KM)
      ->groupBy('events.idevents')
      ->orderBy('events.event_start_utc', 'ASC')
      ->orderBy('distance', 'ASC');
    }

    public function scopeRequiresModeration($query)
    {
        $query = $query->future();
        $query = $query->whereNull('wordpress_post_id');
        return $query;
    }

    public function allDevices()
    {
        return $this->hasMany(\App\Device::class, 'event', 'idevents')->join('categories', 'categories.idcategories', '=', 'devices.category');
    }

    public function allInvited()
    {
        return $this->hasMany(\App\EventsUsers::class, 'event', 'idevents')->where('status', '!=', 1);
    }

    public function allConfirmedVolunteers()
    {
        return $this->hasMany(EventsUsers::class, 'event', 'idevents')
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhereNull('status');
            });
    }

    public function host()
    {
        return $this->hasOne(\App\Host::class, 'idgroups', 'group');
    }

    // Doesn't work if called 'group' - I guess because a reserved SQL keyword.
    public function theGroup()
    {
        return $this->hasOne(\App\Group::class, 'idgroups', 'group');
    }

    public function getEventDate($format = 'd/m/Y')
    {
        return date($format, strtotime($this->event_date));
    }

    public function getEventStart()
    {
        return date('H:i', strtotime($this->start));
    }

    public function getEventEnd()
    {
        return date('H:i', strtotime($this->end));
    }

    public function getEventStartEnd()
    {
        return $this->getEventStart().'-'.$this->getEventEnd();
    }

    public function getEventName()
    {
        if (! empty($this->venue)) {
            return $this->venue;
        }

        return $this->location;
    }

    public function isUpcoming()
    {
        $now = Carbon::now();
        $event_start = new Carbon($this->event_start_utc);
        return ($event_start->greaterThan($now));
    }

    /**
     * [isStartingSoon description]
     * If the event is not of today = false
     * If the event is in progress = false
     * If the event has finished = false
     * If the event is of today, is not in progress and has not finished = true.
     * @author Christopher Kelker
     * @date   2019-06-13T15:48:05+010
     * @return bool
     */
    public function isStartingSoon()
    {
        $start = new Carbon($this->event_start_utc);

        if (!$this->$this->isInProgress() && !$this->hasFinished() && $start->isCurrentDay()) {
            return true;
        }

        return true;
    }

    public function isInProgress()
    {
        $date_now = Carbon::now();
        $start = new Carbon($this->event_start_utc);
        $end = new Carbon($this->event_end_utc);
        return $date_now->gte($start) && $date_now->lte($end);
    }

    public function hasFinished()
    {
        $date_now = Carbon::now();
        $end = new Carbon($this->event_end_utc);
        return $date_now->gt($end);
    }

    public static function getEventStatsArrayKeys()
    {
        return [
            'co2_powered' => 0,
            'co2_unpowered' => 0,
            'co2_total' => 0,
            'waste_powered' => 0,
            'waste_unpowered' => 0,
            'waste_total' => 0,
            'fixed_devices' => 0,
            'fixed_powered' => 0,
            'fixed_unpowered' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'unknown_repair_status' => 0,
            'devices_powered' => 0,
            'devices_unpowered' => 0,
            'no_weight_powered' => 0,
            'no_weight_unpowered' => 0,
            'participants' => 0,
            'volunteers' => 0,
            'hours_volunteered' => 0,
        ];
    }

    public function getEventStats($eEmissionRatio = null, $uEmissionratio = null)
    {
        $displacementFactor = \App\Device::getDisplacementFactor();
        if (is_null($eEmissionRatio)) {
            $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        }
        if (is_null($uEmissionratio)) {
            $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();
        }

        $result = self::getEventStatsArrayKeys();

        if (! empty($this->allDevices)) {
            foreach ($this->allDevices as $device) {
                if ($device->deviceCategory->powered) {
                    $result['devices_powered']++;

                    if ($device->isFixed()) {
                        $result['co2_powered'] += $device->eCo2Diverted($eEmissionRatio, $displacementFactor);
                        $result['waste_powered'] += $device->eWasteDiverted();
                        $result['fixed_powered']++;
                    }
                } else {
                    $result['devices_unpowered']++;

                    if ($device->isFixed()) {
                        $result['co2_unpowered'] += $device->uCo2Diverted($uEmissionratio, $displacementFactor);
                        $result['waste_unpowered'] += $device->uWasteDiverted();
                        $result['fixed_unpowered']++;
                    }
                }

                switch ($device->repair_status) {
                    case 1:
                        $result['fixed_devices']++;
                        break;
                    case 2:
                        $result['repairable_devices']++;
                        break;
                    case 3:
                        $result['dead_devices']++;
                        break;
                    default:
                        $result['unknown_repair_status']++;
                        break;
                }

                if ($device->isFixed()) {
                    if (! $device->deviceCategory->weight && ! $device->estimate) {
                        if ($device->deviceCategory->isMiscPowered()) {
                            $result['no_weight_powered']++;
                        } elseif ($device->deviceCategory->isMiscUnpowered()) {
                            $result['no_weight_unpowered']++;
                        }
                    }
                }
            }

            $result['co2_total'] = $result['co2_powered'] + $result['co2_unpowered'];
            $result['waste_total'] = $result['waste_powered'] + $result['waste_unpowered'];
            $result['participants'] = $this->pax ?? 0;
            $result['volunteers'] = $this->volunteers ?? 0;
            $result['hours_volunteered'] = $this->hoursVolunteered();

            return $result;
        }
    }

    public function devices()
    {
        return $this->hasMany(\App\Device::class, 'event', 'idevents');
    }

    public function hoursVolunteered()
    {
        if (! $this->cancelled) {
            $lengthOfEventInHours = 3;
            $extraHostHours = 9;
            $hoursIfNoVolunteersRecorded = 12;

            $hoursVolunteered = $extraHostHours;

            if ($this->volunteers > 0) {
                $hoursVolunteered += $this->volunteers * $lengthOfEventInHours;
            } else {
                $hoursVolunteered += $hoursIfNoVolunteersRecorded;
            }
        } else {
            // Cancelled events are assumed to take 3 hours.
            $hoursVolunteered = 3;
        }

        return $hoursVolunteered;
    }

    public function getEventStartTimestampAttribute()
    {
        return strtotime($this->event_start_utc);
    }

    public function getShareableLinkAttribute()
    {
        if (! empty($this->shareable_code)) {
            return url("party/invite/{$this->shareable_code}");
        }

        return '';
    }

    /**
     * @param int|null $user_id
     * @return bool
     */
    public function isVolunteer($user_id = null)
    {
        return $this->allConfirmedVolunteers
            ->contains('user', $user_id ?: auth()->id());
    }

    public function isBeingAttendedBy($userId)
    {
        return EventsUsers::where([
            ['event', '=', $this->idevents],
            ['user', '=', $userId],
            ['status', '=', 1],
        ])->exists();
    }

    /**
     * [users description]
     * All Event Users.
     *
     * @author Christopher Kelker - @date 2019-03-21
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @return  [type]
     */
    public function users()
    {
        return $this->hasMany(EventsUsers::class, 'event', 'idevents');
    }

    /**
     * [owner description]
     * Party Owner/Creator.
     *
     * @author Christopher Kelker - @date 2019-03-21
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @return  [type]
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getParticipantsAttribute()
    {
        return $this->pax;
    }

    public function checkForMissingData()
    {
        $participants_count = $this->participants;
        $volunteers_count = $this->allConfirmedVolunteers->count();
        $devices_count = $this->allDevices->count();

        return [
            'participants_count' => $participants_count,
            'volunteers_count' => $volunteers_count,
            'devices_count' => $devices_count,
        ];
    }

    public function requiresModerationByAdmin()
    {
        if (! is_null($this->wordpress_post_id)) {
            return false;
        }

        return true;
    }

    public function VisuallyHighlight()
    {
        if ($this->requiresModerationByAdmin() && Fixometer::hasRole(auth()->user(), 'Administrator')) {
            return 'cell-warning-heading';
        } elseif ($this->isUpcoming() || $this->isInProgress()) {
            if (! $this->isVolunteer()) {
                return 'cell-warning-heading';
            } else {
                return 'cell-primary-heading';
            }
        } elseif ($this->hasFinished()) {
            if (
                $this->checkForMissingData()['participants_count'] == 0 ||
                $this->checkForMissingData()['volunteers_count'] <= 1 ||
                $this->checkForMissingData()['devices_count'] == 0
            ) {
                return 'cell-danger-heading';
            }
        } else {
            return '';
        }
    }

    public function scopeHasDevicesRepaired($query, int $has_x_devices_fixed = 1)
    {
        return $query->whereHas('allDevices', function ($query) {
            return $query->where('repair_status', 1);
        }, '>=', $has_x_devices_fixed);
    }

    public function scopeEventHasFinished($query)
    {
        $now = Carbon::now();
        return $query->whereRaw("`event_end_utc` < '{$now}'");
    }

    public function getWastePreventedAttribute()
    {
        return round($this->getEventStats()['waste_total'], 2);
    }

    public function scopeWithAll($query)
    {
        return $query->with([
            'allDevices.deviceCategory',
            'allInvited',
            'allConfirmedVolunteers',
            'host',
            'theGroup.groupImage.image',
            'devices.deviceCategory',
        ]);
    }

    public function getFriendlyLocationAttribute()
    {
        $short_location = Str::limit($this->venue, 30);

        return "{$this->getEventDate('d/m/Y')} / {$short_location}";
    }

    public function shouldPushToWordpress()
    {
        return $this->theGroup->eventsShouldPushToWordpress();
    }

    public function associatedNetworkCoordinators()
    {
        $group = $this->theGroup;

        $coordinators = collect([]);

        foreach ($group->networks as $network) {
            foreach ($network->coordinators as $coordinator) {
                $coordinators->push($coordinator);
            }
        }

        return $coordinators;
    }

    public function getMaxUpdatedAtDevicesUpdatedAtAttribute()
    {
        return strtotime($this->updated_at) > strtotime($this->devices_updated_at) ? $this->updated_at : $this->devices_updated_at;
    }

    public function canDelete()
    {
        $stats = $this->getEventStats();

        return $stats['devices_powered'] == 0 && $stats['devices_unpowered'] == 0;
    }

    public function approve()
    {
        $group = Group::find($this->group);

        // Only send notifications if the event is in the future.
        // We don't want to send emails to Restarters about past events being added.
        if ($this->isUpcoming()) {
            // Retrieving all users from the User model whereby they allow you send emails but their role must not include group admins
            $group_restarters = User::join('users_groups', 'users_groups.user', '=', 'users.id')
                ->where('users_groups.group', $this->group)
                ->where('users_groups.role', 4)
                ->select('users.*')
                ->get();

            // If there are restarters against the group
            if (! $group_restarters->isEmpty()) {
                // Send user a notification and email
                Notification::send($group_restarters, new NotifyRestartersOfNewEvent([
                                                                                         'event_venue' => $this->venue,
                                                                                         'event_url' => url('/party/view/'.$this->idevents),
                                                                                         'event_group' => $group->name,
                                                                                     ]));
            }
        }

        // Notify the person who created it that it has now been approved.
        $host = User::find(EventsUsers::where('event', $this->idevents)->first());

        if ($host) {
            Notification::send($host, new EventConfirmed($this));
        }

        event(new ApproveEvent($this));
    }

    public function getTimezoneAttribute($value)
    {
        // We might have a timezone attribute on the event.
        if ($value) {
            return $value;
        }

        // Use the timezone from the group (which will fallback to network if required).
        return $this->theGroup->timezone;
    }

    // TODO The intention is that we migrate all the code over to use the UTC variants of event date/start/end.
    // Timezone-aware, ISO8601 formatted.  These are unambiguous, e.g. for API results.
    public function getEventStartUtcAttribute() {
        $start = Carbon::parse($this->attributes['event_start_utc'], 'UTC');
        return $start->toIso8601String();
    }

    public function getEventEndUtcAttribute() {
        $end = Carbon::parse($this->attributes['event_end_utc'], 'UTC');
        return $end->toIso8601String();
    }

    // Mutators for legacy event_date/start/end fields.  These are now derived from the UTC fields via virtual
    // columns, and therefore should never be set directly.  Throw exceptions to ensure that they are not, until we
    // have retired these fields.
    //
    // The tests create events using the old fields, and we've not changed those yet, but PartyFactory will have
    // populated the new ones - so just ignore that.
    //
    // You might think that we could have mutators which set these correctly.  But this isn't possible; the UTC value
    // of the date depends on the local date, time and timezone, and cannot be set in isolation.
    public function setEventDateAttribute($val) {
        if (!array_key_exists('event_start_utc', $this->attributes)) {
            throw new \Exception("Attempt to set event time fields directly; please use event_start_utc and event_end_utc");
        }
    }

    public function setStartAttribute($val) {
        if (!array_key_exists('event_start_utc', $this->attributes)) {
            throw new \Exception(
                "Attempt to set event time fields directly; please use event_start_utc and event_end_utc"
            );
        }
    }

    public function setEndAttribute($val) {
        if (!array_key_exists('event_start_utc', $this->attributes)) {
            throw new \Exception(
                "Attempt to set event time fields directly; please use event_start_utc and event_end_utc"
            );
        }
    }

    // We also need accessors, to avoid any cases where we mutate the values and access them before we have
    // saved and done issue a refresh(), which would pick up new values from the virtual columns.
    //
    // These should return local values, i.e. in the timezone of the event.
    public function getEventDateAttribute() {
        $dt = new Carbon($this->event_start_utc);
        $dt->setTimezone($this->timezone);
        return $dt->toDateString();
    }
    public function getStartAttribute() {
        $dt = new Carbon($this->event_start_utc);
        $dt->setTimezone($this->timezone);
        return $dt->toTimeString();
    }

    public function getEndAttribute() {
        $dt = new Carbon($this->event_end_utc);
        $dt->setTimezone($this->timezone);
        return $dt->toTimeString();
    }
}
