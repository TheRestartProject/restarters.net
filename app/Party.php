<?php

namespace App;

use App\Device;
use App\EventUsers;
use App\Helpers\Fixometer;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;

class Party extends Model implements Auditable
{
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use \App\Traits\GlobalScopes;

    protected $table = 'events';
    protected $primaryKey = 'idevents';
    protected $fillable = [
        'group',
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
    ];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'frequency', 'group', 'group', 'user_id', 'wordpress_post_id', 'cancelled', 'devices_updated_at'];

    // Append data to Model
    protected $appends = ['participants', 'ShareableLink'];

    //Getters
    public function findAll()
    {
        //Tested
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
                ORDER BY `e`.`start` DESC'));
    }

    public function findAllSearchable()
    {
        //Tested
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
                    `e`.`discourse_thread`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`
                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                WHERE `event_date` <= NOW()
                ORDER BY `e`.`event_date` DESC'));
    }

    public function findThis($id, $devices = false)
    {
        //Tested however with devices = true doesn't work
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
                    `e`.`online`,
                    `e`.`discourse_thread`,
                    `g`.`name` AS `group_name`,
                    `g`.`idgroups` AS `group_id`

                FROM `events` AS `e`
                INNER JOIN `groups` AS `g`
                    ON `g`.`idgroups` = `e`.`group`
                WHERE `e`.`idevents` = :id
                ORDER BY `e`.`start` DESC';

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
        $sql = 'SELECT *, `e`.`venue` AS `venue`, `e`.`location` as `location`, UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`
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
            $sql .= ' AND `e`.`event_date` < NOW()';
        }
        $sql .= ' ORDER BY `e`.`event_date` DESC';

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
	`e`.`venue` AS `venue`, `e`.`location` as `location`,
                    `g`.`name` AS group_name,


                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`

                FROM `'.$this->table.'` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        //UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) )
        if (is_numeric($group) && $group != 'admin') {
            $sql .= ' WHERE `e`.`group` = :id ';
        }

        if ($only_past) {
            $sql .= ' AND TIMESTAMP(`e`.`event_date`, `e`.`start`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_date` DESC';

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
	`e`.`venue` AS `venue`, `e`.`location` as `location`,
                    `g`.`name` AS group_name,


                    UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`

                FROM `'.$this->table.'` AS `e`

                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`

                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event`
                        FROM `devices` AS `dv`
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` ';
        //UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) )
        if (is_array($groups) && $groups != 'admin') {
            $sql .= ' WHERE `e`.`group` IN ('.implode(', ', $groups).') ';
        }

        if ($only_past) {
            $sql .= ' AND TIMESTAMP(`e`.`event_date`, `e`.`start`) < NOW()';
        }

        $sql .= ' ORDER BY `e`.`event_date` DESC';

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
            return $query->where(function ($query) {
                // Before today, or before the start time.
                return $query->where('event_date', '<', Carbon::now()->toDateString())
                    ->orWhere(function ($query) {
                        return $query->where('event_date', '=', Carbon::now()->toDateString())
                            ->where('start', '<', Carbon::now()->toTimeString());
                    });
            });
        })->when(is_numeric($group), function ($query) use ($group) {
            // For a specific group.  Note that 'admin' is not numeric so won't pass this test.
            return $query->where('group', $group);
        })->get();
    }

    public function findNextParties($group = null)
    {
        //Tested
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
                FROM `'.$this->table.'` AS `e`

                WHERE TIMESTAMP(`e`.`event_date`, `e`.`start`) >= NOW() '; // added one day to make sure it only gets moved to the past the next day

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
        //Tested
        return DB::select(DB::raw('SELECT SUM(pax) AS pax FROM '.$this->table));
    }

    /**
     * Laravel specific code.
     */
    public function scopeUpcomingEvents($query)
    {
        // We want to show approved events (wordpress_post_id IS NOT NULL), or where we are a host (e.g. because
        // we created it, or if we are a network coordinator/admin.
        $ret = $this->join('groups', 'groups.idgroups', '=', 'events.group')
            ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->leftJoin('events_users', function ($join) {
                $join->on('events_users.event', '=', 'events.idevents');
                $join->where('events_users.user', '=', Auth::user()->id);
            })
            ->leftJoin('users', 'users.id', '=', 'events_users.user')
            ->where(function ($query) {
                $query
                  ->whereNotNull('events.wordpress_post_id')
                  ->orWhere('events_users.role', '=', Role::HOST)
                  ->orWhere('users_groups.role', '=', Role::HOST)
                  ->orWhereIn('users.role', [Role::NETWORK_COORDINATOR, Role::ADMINISTRATOR]);
            })
            ->whereDate('event_date', '>=', date('Y-m-d'))
            ->select('events.*')
            ->groupBy('idevents')
            ->orderBy('event_date', 'ASC');

        //error_log("Query " . $ret->toSql() . " with " . Auth::user()->id . " and " . date('Y-m-d'));
        return $ret;
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
        //Look for groups where user ID exists in pivot table
        $user_group_ids = UserGroups::where('user', $user->id)->pluck('group')->toArray();

        return $this
      ->select(DB::raw('`events`.*, ( 6371 * acos( cos( radians('.$user->latitude.') ) * cos( radians( events.latitude ) ) * cos( radians( events.longitude ) - radians('.$user->longitude.') ) + sin( radians('.$user->latitude.') ) * sin( radians( events.latitude ) ) ) ) AS distance'))
      ->join('groups', 'groups.idgroups', '=', 'events.group')
      ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
      ->where(function ($query) use ($user_group_ids) {
          $query->whereNotIn('events.group', $user_group_ids)
        ->whereDate('event_date', '>=', date('Y-m-d'));
      })
      ->having('distance', '<=', 35) // kilometers (km)

      ->groupBy('events.idevents')
      ->orderBy('events.event_date', 'ASC')
      ->orderBy('events.start', 'ASC')
      ->orderBy('distance', 'ASC');
    }

    public function scopeAllUpcomingEvents()
    {
        return $this->whereRaw('CONCAT(`event_date`, " ", `start`) > CURRENT_TIMESTAMP()')
                    ->orderByRaw('CONCAT(`event_date`, " ", `start`)');
    }

    public function scopeRequiresModeration()
    {
        return $this->whereNull('wordpress_post_id')
                      ->whereDate('event_date', '>=', date('Y-m-d'))
                        ->orderBy('event_date', 'ASC');
    }

    public function scopePastEvents()
    {
        return $this->whereNotNull('wordpress_post_id')
                      ->whereDate('event_date', '<', date('Y-m-d'))
                        ->orderBy('event_date', 'DESC');
    }

    /**
     * [scopeUsersPastEvents description]
     * Get all Past Events from the User or User's groups, or any Past Events
     * in which the User(s) has attended but is NOT a part of the
     * group associated.
     * @author Christopher Kelker
     * @date   2019-05-28T09:50:22+010
     * @param  [type]                  $query
     * @param  [type]                  $user_ids
     * @return [type]
     */
    public function scopeUsersPastEvents($query, array $user_ids = null)
    {
        // if no $user_ids are supplied, the use the current Auth's ID
        if (empty($user_ids)) {
            $user_ids[] = auth()->id();
        }

        return $query->join('groups', 'groups.idgroups', '=', 'events.group')
      ->join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
      ->join('events_users', 'events_users.event', '=', 'events.idevents')
      ->whereNotNull('events.wordpress_post_id')
      ->whereDate('events.event_date', '<', date('Y-m-d'))

      ->where(function ($query) use ($user_ids) {
          $query->whereIn('users_groups.user', $user_ids)
          ->orWhereIn('events_users.user', $user_ids);
      })

      ->select('events.*')
      ->groupBy('idevents')
      ->orderBy('events.event_date', 'DESC');
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

    public function getEventTimestampAttribute()
    {
        return "{$this->event_date} {$this->start}";
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
        $date_now = new \DateTime();
        $event_start = new \DateTime($this->event_date.' '.$this->start);

        if ($date_now < $event_start) {
            return true;
        }

        return false;
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
        $current_date = date('Y-m-d');
        $event_date = $this->event_date;

        if ($current_date != $event_date) {
            return false;
        }

        if ($this->isInProgress()) {
            return false;
        }

        $date_now = new \DateTime();
        $event_end = new \DateTime($this->event_date.' '.$this->end);

        if ($date_now > $event_end) {
            return false;
        }

        return true;
    }

    public function isInProgress()
    {
        $date_now = Carbon::now();

        $event_start = new Carbon($this->event_date.' '.$this->start);
        // Temporarily start an hour early for Repair Together
        // Until we have timezone support.
        $event_start = $event_start->addHours(-1);

        $event_end = new Carbon($this->event_date.' '.$this->end);

        if ($date_now >= $event_start && $date_now <= $event_end) {
            return true;
        }

        return false;
    }

    public function hasFinished()
    {
        $date_now = new \DateTime();
        $event_end = new \DateTime($this->event_date.' '.$this->end);

        if ($date_now > $event_end) {
            return true;
        }

        return false;
    }

    public function getEventStats($emissionRatio = null)
    {
        $displacementFactor = \App\Device::getDisplacementFactor();
        if (is_null($emissionRatio)) {
            $emissionRatio = \App\Helpers\FootprintRatioCalculator::calculateRatio();
        }

        $co2Diverted = 0;
        $ewasteDiverted = 0;
        $unpoweredWasteDiverted = 0;
        $fixed_devices = 0;
        $fixed_powered = 0;
        $fixed_unpowered = 0;
        $repairable_devices = 0;
        $dead_devices = 0;
        $no_weight = 0;
        $devices_powered = 0;
        $devices_unpowered = 0;

        if (! empty($this->allDevices)) {
            foreach ($this->allDevices as $device) {
                if ($device->deviceCategory->powered) {
                    $devices_powered++;

                    if ($device->isFixed()) {
                        $co2Diverted += $device->co2Diverted($emissionRatio, $displacementFactor);
                        $ewasteDiverted += $device->ewasteDiverted();
                        $fixed_powered++;
                    }
                } else {
                    $devices_unpowered++;

                    if ($device->isFixed()) {
                        // CO2 estimates don't include unpowered items.
                        $unpoweredWasteDiverted += $device->unpoweredWasteDiverted();
                        $fixed_unpowered++;
                    }
                }

                switch ($device->repair_status) {
                    case 1:
                        $fixed_devices++;

                        break;
                    case 2:
                        $repairable_devices++;

                        break;
                    case 3:
                        $dead_devices++;

                        break;
                    default:
                        break;
                }

                if ($device->isFixed() && ($device->category == 46 || ! $device->deviceCategory->weight) && ! $device->estimate) {
                    $no_weight++;
                }
            }

            return [
                'co2' => $co2Diverted,
                'ewaste' => $ewasteDiverted,
                'unpowered_waste' => $unpoweredWasteDiverted,
                'fixed_devices' => $fixed_devices,
                'fixed_powered' => $fixed_powered,
                'fixed_unpowered' => $fixed_unpowered,
                'repairable_devices' => $repairable_devices,
                'dead_devices' => $dead_devices,
                'no_weight' => $no_weight,
                'participants' => $this->pax,
                'volunteers' => $this->volunteers,
                'hours_volunteered' => $this->hoursVolunteered(),
                'devices_powered' => $devices_powered,
                'devices_unpowered' => $devices_unpowered,
            ];
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
        return strtotime($this->event_date.' '.$this->start);
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
            if ($this->checkForMissingData()['participants_count'] == 0 ||
        $this->checkForMissingData()['volunteers_count'] <= 1 ||
        $this->checkForMissingData()['devices_count'] == 0) {
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

        return $query->whereRaw("CONCAT(`event_date`, ' ', `end`) < '{$now}'");
    }

    public function getWastePreventedAttribute()
    {
        return round($this->getEventStats()['ewaste'], 2);
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

    public function canDelete() {
        $stats = $this->getEventStats();
        return $stats['devices_powered'] == 0 && $stats['devices_unpowered'] == 0;
    }
}
