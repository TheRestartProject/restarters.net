<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Events\ApproveEvent;
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
    use HasFactory;

    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'events';
    protected $primaryKey = 'idevents';
    protected $fillable = [
        'group',
        'event_start_utc',
        'event_end_utc',
        'venue',
        'location',
        'latitude',
        'longitude',
        'free_text',
        'pax',
        'volunteers',
        'hours',
        'wordpress_post_id',
        'approved',
        'created_at',
        'updated_at',
        'shareable_code',
        'online',
        'discourse_thread',
        'devices_updated_at',
        'link',
        'timezone',
        'user_id',
        'network_data',
    ];
    protected $hidden = ['created_at', 'deleted_at', 'frequency', 'group', 'user_id', 'wordpress_post_id', 'cancelled', 'devices_updated_at'];

    // Eager-loading the group reduces N+1 queries.
    protected $with = 'theGroup';

    // Append data to Model
    protected $appends = ['participants', 'ShareableLink', 'event_date_local', 'start_local', 'end_local'];

    protected $casts = [
        // JSON fields in the database should be converted to/from arrays.
        'network_data' => 'array'
    ];

    //Getters
    public function findAllSearchable()
    {
        // TODO Can this be replaced by Party::past?
        return DB::select(DB::raw('SELECT
                    `e`.`idevents` AS `id`,
                    UNIX_TIMESTAMP(`event_start_utc`) AS `event_timestamp`,
                    TIME(CONVERT_TZ(`event_start_utc`, \'GMT\', `e`.`timezone`)) AS `start`,
                    TIME(CONVERT_TZ(`event_end_utc`, \'GMT\', `e`.`timezone`)) AS `end`,
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
                    TIME(CONVERT_TZ(`event_start_utc`, \'GMT\', `e`.`timezone`)) AS `start`,
                    TIME(CONVERT_TZ(`event_end_utc`, \'GMT\', `e`.`timezone`)) AS `end`,
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
                    `e`.`approved`,
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

    public function deleteUserList($party)
    {
        return DB::delete(DB::raw('DELETE FROM `events_users` WHERE `event` = :party'), ['party' => $party]);
    }

    public function ofThisGroup($group = 'admin', $only_past = false, $devices = false)
    {
        return self::when($only_past, function ($query) {
            // We only want the ones in the past.
            $now = date('Y-m-d H:i:s');
            return $query->where('event_end_utc', '<', $now);
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
                    DATE(`e`.`event_start_utc`) AS `plain_date`,
                    NOW() AS `this_moment`,
                    TIME(CONVERT_TZ(`event_start_utc`, \'GMT\', `e`.`timezone`)) AS `start`,
                    TIME(CONVERT_TZ(`event_end_utc`, \'GMT\', `e`.`timezone`)) AS `end`,
                    `e`.`latitude`,
                    `e`.`longitude`
                FROM `'.$this->table.'` AS `e`
                WHERE `e`.`event_end_utc` >= NOW()';

        if (! is_null($group)) {
            $sql .= ' AND `e`.`group` = :group ';
        }

        $sql .= ' ORDER BY `e`.`event_start_utc` ASC
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
        $query = $query->where('event_end_utc', '<', date('Y-m-d H:i:s'));
        return $query;
    }

    public function scopeFuture($query) {
        // A future event is an event where the start time is greater than now.
        $query = $query->undeleted();
        $query = $query->where('event_start_utc', '>', date('Y-m-d H:i:s'))->orderBy('event_start_utc','ASC');
        return $query;
    }

    public function scopeActive($query) {
        // An active event is an event which has started and not yet finished.
        $query = $query->undeleted();
        $now = date('Y-m-d H:i:s');
        $query = $query->where('event_start_utc', '<=', $now)
            ->where('event_end_utc', '>=', $now);
        return $query;
    }

    public function scopeApproved($query) {
        $query = $query->undeleted();
        $query = $query->where('approved', true);
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

    public function scopeMemberOfGroup($query, $userids = null) {

        $this->defaultUserIds($userids);
        $query = $query->approved();
        $query = $query->join('users_groups AS hfgug', 'hfgug.group', '=', 'events.group')
            ->where('hfgug.status', 1)
            ->whereNull('hfgug.deleted_at')
            ->whereIn('hfgug.user', $userids)
            ->select('events.*');
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
        //
        // The parent may want to specify an ORDER BY.  The parent can't use the reorder() method because it
        // doesn't seem to go deep enough to penetrate a UNION query.  So we have to use reorder() here to strip out
        // any ORDER BY introduced by other scopes, and then rely on the parent to specify an ORDER BY if they
        // want one.
        $this->defaultUserIds($userids);
        $hostFor = Party::with('theGroup.networks')->hostFor($userids)->reorder();
        $attending = Party::with('theGroup.networks')->attendingOrAttended($userids)->reorder();
        $memberOf = Party::with('theGroup.networks')->memberOfGroup($userids)->reorder();

        // In theory $query could contain something other than all().
        return $query->whereIn('idevents', $hostFor->
            union($attending)->
            union($memberOf)->pluck('idevents'))->
            select('events.*');
    }

    public function scopeFutureForUser($query, $userids = null) {
        $this->defaultUserIds($userids);
        $query = $query->forUser(null)->future($userids)->reorder()->orderBy('event_start_utc', 'ASC');
        return $query;
    }

    public function scopePastForUser($query, $userids = null) {
        $this->defaultUserIds($userids);
        $query = $query->forUser(null)->past($userids)->reorder()->orderBy('event_start_utc', 'DESC');
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
        $exclude_group_ids = UserGroups::where('user', $user->id)->where('status', 1)->pluck('group')->toArray();

        // We also want to exclude any groups which are not yet approved.
        $exclude_group_ids = array_merge($exclude_group_ids, Group::where('approved', false)->pluck('idgroups')->toArray());

        return $this
      ->select(DB::raw('`events`.*, ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( events.latitude ) ) * cos( radians( events.longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( events.latitude ) ) ) ) AS distance'))
      ->join('groups', 'groups.idgroups', '=', 'events.group')
      ->join('group_network', 'groups.idgroups', '=', 'group_network.group_id')
      ->join('networks', 'networks.id', '=', 'group_network.network_id')
      ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
      ->where(function ($query) use ($exclude_group_ids) {
          $query->whereNotIn('events.group', $exclude_group_ids)
        ->where('event_start_utc', '>=', date('Y-m-d H:i:s'));
      })
      ->having('distance', '<=', User::NEARBY_KM)
      ->groupBy('events.idevents')
      ->orderBy('events.event_start_utc', 'ASC')
      ->orderBy('distance', 'ASC');
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

    // Doesn't work if called 'group' - I guess because a reserved SQL keyword.
    public function theGroup()
    {
        return $this->hasOne(\App\Group::class, 'idgroups', 'group');
    }

    /**
     * Return formatted date, in timezone of event.
     *
     * @param string $format
     * @return false|string
     */
    public function getFormattedLocalStart($format = 'd/m/Y')
    {
        $dt = new Carbon($this->event_start_utc);
        $dt->setTimezone($this->timezone);
        return $dt->format($format);
    }

    /**
     * Return formatted date, in timezone of event.
     *
     * @param string $format
     * @return false|string
     */
    public function getFormattedLocalEnd($format = 'd/m/Y')
    {
        $dt = new Carbon($this->event_end_utc);
        $dt->setTimezone($this->timezone);
        return $dt->format($format);
    }

    public function getEventTimestampAttribute()
    {
        // Returning in local time.
        return "{$this->event_date_local} {$this->start_local}";
    }

    public function getEventStartEndLocal($includeTimezone = false)
    {
        $ret = $this->start_local . ' - ' . $this->end_local;

        if ($includeTimezone) {
            $ret .= ' (' . $this->timezone . ')';
        }

        return $ret;
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

        if (!$this->isInProgress() && !$this->hasFinished() && $start->isCurrentDay()) {
            return true;
        }

        return false;
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
            'invited' => 0,
        ];
    }

    public function getEventStats($eEmissionRatio = null, $uEmissionratio = null, $includeFuture = false)
    {
        $displacementFactor = \App\Device::getDisplacementFactor();
        if (is_null($eEmissionRatio)) {
            $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        }
        if (is_null($uEmissionratio)) {
            $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();
        }

        $result = self::getEventStatsArrayKeys();

        // Normally we only count stats for devices for events that have started or finished.
        if (($includeFuture || $this->hasFinished() || $this->isInProgress()) && !empty($this->allDevices)) {
            foreach ($this->allDevices as $device) {
                // We cache the powered flag for a category to avoid many DB queries.
                $powered = \Cache::remember('category-powered-' . $device->category, 15, function() use ($device) {
                    return $device->deviceCategory->powered;
                });

                if ($powered) {
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
                    case Device::REPAIR_STATUS_FIXED:
                        $result['fixed_devices']++;
                        break;
                    case Device::REPAIR_STATUS_REPAIRABLE:
                        $result['repairable_devices']++;
                        break;
                    case Device::REPAIR_STATUS_ENDOFLIFE:
                        $result['dead_devices']++;
                        break;
                    default:
                        $result['unknown_repair_status']++;
                        break;
                }

                if ($device->isFixed()) {
                    $category = \Cache::remember('category-' . $device->category, 15, function() use ($device) {
                        return $device->deviceCategory;
                    });

                    if ($category->weight == 0 && $device->estimate == 0) {
                        if ($category->isMiscPowered()) {
                            $result['no_weight_powered']++;
                        } elseif ($category->isMiscUnpowered()) {
                            $result['no_weight_unpowered']++;
                        }
                    }
                }
            }
        }

        $result['co2_total'] = $result['co2_powered'] + $result['co2_unpowered'];
        $result['waste_total'] = $result['waste_powered'] + $result['waste_unpowered'];
        $result['participants'] = $this->pax ?? 0;
        $result['volunteers'] = $this->volunteers ?? 0;
        $result['invited'] = $this->allInvited->count();
        $result['hours_volunteered'] = $this->hoursVolunteered();

        return $result;
    }

    public function devices()
    {
        return $this->hasMany(\App\Device::class, 'event', 'idevents');
    }

    public function lengthInHours() {
        $start = new Carbon($this->event_start_utc);
        $end = new Carbon($this->event_end_utc);
        return ceil($start->diffInMinutes($end) / 60);
    }

    public function hoursVolunteered()
    {
        if (! $this->cancelled) {
            // Get difference in hours between start and end.  Make sure we round up.
            $lengthOfEventInHours = $this->lengthInHours();
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

    public function requiresModerationByAdmin()
    {
        if ($this->approved) {
            return false;
        }

        return true;
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

    public function getFriendlyLocationAttribute()
    {
        $short_location = Str::limit($this->venue, 30);

        return "{$this->getFormattedLocalStart('d/m/Y')} / {$short_location}";
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
        return !Device::where('event', '=', $this->idevents)->first();
    }

    public function approve()
    {
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

    // Timezone-aware, ISO8601 formatted.  These are unambiguous, e.g. for API results.
    public function getEventStartUtcAttribute() {
        return array_key_exists('event_start_utc', $this->attributes) ? Carbon::parse($this->attributes['event_start_utc'], 'UTC')->toIso8601String() : null;
    }

    public function getEventEndUtcAttribute() {
        return array_key_exists('event_end_utc', $this->attributes) ? Carbon::parse($this->attributes['event_end_utc'], 'UTC')->toIso8601String() : null;
    }

    // MySQL doesn't handle ISO 8601 strings well, so we convert them to the UTC timezone before
    // storing them in the database.  This is a mutator, so it will be called when the model is saved.
    public function setEventStartUtcAttribute($val) {
        $dt = Carbon::parse($val);
        $dt->setTimezone('UTC');
        $this->attributes['event_start_utc'] = $dt->toDateTimeString();
    }

    public function setEventEndUtcAttribute($val) {
        $dt = Carbon::parse($val);
        $dt->setTimezone('UTC');
        $this->attributes['event_end_utc'] = $dt->toDateTimeString();
    }

    // Mutators for previous event_date/start/end fields.  These are now superceded by the UTC fields and therefore
    // should never be set directly.  Throw exceptions to ensure that they are not.
    public function setEventDateAttribute($val) {
        throw new \Exception("Attempt to set event time fields directly; please use event_start_utc and event_end_utc");
    }

    public function setStartAttribute($val) {
        throw new \Exception(
            "Attempt to set event time fields directly; please use event_start_utc and event_end_utc"
        );
    }

    public function setEndAttribute($val) {
        throw new \Exception(
            "Attempt to set event time fields directly; please use event_start_utc and event_end_utc"
        );
    }

    public function getEventDateLocalAttribute() {
        $dt = new Carbon($this->event_start_utc);
        $dt->setTimezone($this->timezone);
        return $dt->toDateString();
    }
    public function getStartLocalAttribute() {
        $dt = new Carbon($this->event_start_utc);
        $dt->setTimezone($this->timezone);
        return $dt->toTimeString('minute');
    }

    public function getEndLocalAttribute() {
        $dt = new Carbon($this->event_end_utc);
        $dt->setTimezone($this->timezone);
        return $dt->toTimeString('minute');
    }

    public static function expandVolunteers($volunteers, $showEmails) {
        $ret = [];

        foreach ($volunteers as $volunteer) {
            $volunteer['userSkills'] = [];
            $volunteer['confirmed'] = intval($volunteer->status) === 1;
            $volunteer['profilePath'] = '/uploads/thumbnail_placeholder.png';
            $volunteer['fullName'] = $volunteer->getFullName();

            if ($volunteer->volunteer) {
                $volunteer['volunteer'] = $volunteer->volunteer;

                if (!$showEmails) {
                    $volunteer['volunteer']['email'] = NULL;
                }

                if (! empty($volunteer->volunteer)) {
                    $volunteer['userSkills'] = $volunteer->volunteer->userSkills->all();
                    $volunteer['profilePath'] = '/uploads/thumbnail_'.$volunteer->volunteer->getProfile($volunteer->volunteer->id)->path;

                    foreach ($volunteer['userSkills'] as $skill) {
                        // Force expansion
                        $skill->skillName->skill_name;
                    }
                }
            }

            $ret[] = $volunteer;
        }

        return $ret;
    }
}
