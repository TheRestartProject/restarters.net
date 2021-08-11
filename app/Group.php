<?php

namespace App;

use App\Network;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Group extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'groups';
    protected $primaryKey = 'idgroups';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'website',
        'area',
        'location',
        'postcode',
        'latitude',
        'longitude',
        'country',
        'free_text',
        'facebook',
        'wordpress_post_id',
        'shareable_code',
        'network_id',
        'external_id',
        'devices_updated_at',
    ];

    protected $appends = ['ShareableLink', 'approved'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('all_hosts_count', function ($builder) {
            $builder->withCount('allHosts');
        });

        static::addGlobalScope('all_restarters_count', function ($builder) {
            $builder->withCount('allRestarters');
        });
    }

    public function addTag($tag)
    {
        $this->group_tags()->save($tag);
    }

    // NGM: when tests in place, this method name should be changed to just `tags`.
    // It's on a group, the group_ prefix is superfluous.
    public function group_tags()
    {
        return $this->belongsToMany(\App\GroupTags::class, 'grouptags_groups', 'group', 'group_tag');
    }

    // Setters

    //Getters
    public function findAll()
    {
        try {
            return DB::select(DB::raw('SELECT
                    `g`.`idgroups` AS `id`,
                    `g`.`name` AS `name`,
                    `g`.`location` AS `location`,
                    `g`.`latitude` AS `latitude`,
                    `g`.`longitude` AS `longitude`,
                    `g`.`free_text` AS `free_text`,
                    `g`.`area` AS `area`,
                    `g`.`postcode` AS `postcode`,
                    `g`.`frequency` AS `frequency`,
                    GROUP_CONCAT(`u`.`name` ORDER BY `u`.`name` ASC SEPARATOR ", "  )  AS `user_list`
                FROM `'.$this->table.'` AS `g`
                LEFT JOIN `users_groups` AS `ug` ON `g`.`idgroups` = `ug`.`group`
                LEFT JOIN `users` AS `u` ON `ug`.`user` = `u`.`id`
                GROUP BY `g`.`idgroups`
                ORDER BY `g`.`name` ASC'));
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }
    }

    public function findList()
    {
        try {
            return DB::select(DB::raw('SELECT
                `g`.`idgroups` AS `id`,
                `g`.`name` AS `name`,
                `g`.`location` AS `location`,
                `g`.`area` AS `area`,
                `g`.`postcode` AS `postcode`,
                `xi`.`path` AS `path`

            FROM `'.$this->table.'` AS `g`

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

    public function findOne($id)
    {
        //Took out GROUP BY `images`.`path` NB:Error message -> 'fixometer_laravel.images.idimages' isn't in GROUP BY
        try {
            $group = DB::select(DB::raw('SELECT * FROM `'.$this->table.'` AS `g`
                LEFT JOIN (
                    SELECT * FROM `images`
                        INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                        WHERE `xref`.`object_type` = 5
                        AND `xref`.`reference_type` = '.env('TBL_GROUPS').'
                        GROUP BY `images`.`path`
                ) AS `xi`
                ON `xi`.`reference` = `g`.`idgroups`
                WHERE `id'.$this->table.'` = :id'), ['id' => $id]);
        } catch (\Illuminate\Database\QueryException $e) {
            dd($e);
        }

        if (! empty($group)) {
            return $group[0];
        }
    }

    public function findHost($id)
    {
        return DB::select(DB::raw('SELECT *,
                    `g`.`name` AS `groupname`,
                    `u`.`name` AS `hostname`
                FROM `'.$this->table.'` AS `g`
                INNER JOIN `users_groups` AS `ug`
                    ON `ug`.`group` = `g`.`idgroups`
                INNER JOIN `users` AS `u`
                    ON `u`.`id` = `ug`.`user`
                LEFT JOIN (
                    SELECT * FROM `images`
                        INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                        WHERE `xref`.`object_type` = 5
                        AND `xref`.`reference_type` = '.env('TBL_USERS').'
                        GROUP BY `images`.`path`
                ) AS `xi`
                ON `xi`.`reference` = `u`.`id`

                WHERE `g`.`idgroups` = :id
                AND `u`.`role` = 3'), ['id' => $id]);
    }

    public function ofThisUser($id)
    {
        return DB::select(DB::raw('SELECT * FROM `'.$this->table.'` AS `g`
                INNER JOIN `users_groups` AS `ug`
                    ON `ug`.`group` = `g`.`idgroups`

                LEFT JOIN (
                    SELECT * FROM `images`
                        INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                        WHERE `xref`.`object_type` = 5
                        AND `xref`.`reference_type` = '.env('TBL_GROUPS').'
                        GROUP BY `images`.`path`
                ) AS `xi`
                ON `xi`.`reference` = `g`.`idgroups`

                WHERE `ug`.`user` = :id
                ORDER BY `g`.`name` ASC'), ['id' => $id]);
    }

    public function groupImage()
    {
        return $this->hasOne(\App\Xref::class, 'reference', 'idgroups')->where('reference_type', env('TBL_GROUPS'))->where('object_type', 5);
    }

    public function allHosts()
    {
        return $this->hasMany(\App\UserGroups::class, 'group', 'idgroups')->where('role', 3);
    }

    public function allRestarters()
    {
        return $this->hasMany(\App\UserGroups::class, 'group', 'idgroups')->where('role', 4);
    }

    public function allVolunteers()
    {
        return $this->hasMany(\App\UserGroups::class, 'group', 'idgroups')->orderBy('role', 'ASC');
    }

    public function allConfirmedHosts()
    {
        return $this->allHosts()->confirmedInvitation();
    }

    public function allConfirmedRestarters()
    {
        return $this->allRestarters()->confirmedInvitation();
    }

    // This could use confirmedInvitation scope, but not changing it until whatever is using
    // it has a test around it.
    public function allConfirmedVolunteers()
    {
        return $this->allVolunteers()
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhereNull('status');
            });
    }

    public function getLocation()
    {
        return rtrim($this->location);
    }

    public function canDelete()
    {
        // Groups are deletable unless they have an event with a device.
        $ret = true;

        $allEvents = Party::where('events.group', $this->idgroups)
            ->get();

        foreach ($allEvents as $event) {
            $stats = $event->getEventStats();

            if ($stats['devices_powered'] || $stats['devices_unpowered']) {
                $ret = false;
            }
        }

        return $ret;
    }

    public function getGroupStats($emissionRatio = null)
    {
        if (is_null($emissionRatio)) {
            $emissionRatio = \App\Helpers\FootprintRatioCalculator::calculateRatio();
        }

        $allPastEvents = Party::pastEvents()
            ->where('events.group', $this->idgroups)
            ->get();

        $groupStats = [];
        // Rollup all events stats into stats for this group.
        foreach ($allPastEvents as $event) {
            $eventStats = $event->getEventStats($emissionRatio);

            foreach ($eventStats as $statKey => $statValue) {
                if (! array_key_exists($statKey, $groupStats)) {
                    $groupStats[$statKey] = 0;
                }
                $groupStats[$statKey] += $statValue;
            }
        }

        // Keeping the specific subset of stats returned for now,
        // with existing names.
        return [
            'pax' => $groupStats['participants'] ?? 0,
            'hours' => $groupStats['hours_volunteered'] ?? 0,
            'parties' => count($allPastEvents),
            'co2' => $groupStats['co2'] ?? 0,
            'ewaste' => $groupStats['ewaste'] ?? 0,
            'unpowered_waste' => $groupStats['unpowered_waste'] ?? 0,
            'waste' => ($groupStats['ewaste'] ?? 0) + ($groupStats['unpowered_waste'] ?? 0),
            'fixed_devices' => $groupStats['fixed_devices'] ?? 0,
            'fixed_powered' => $groupStats['fixed_powered'] ?? 0,
            'fixed_unpowered' => $groupStats['fixed_unpowered'] ?? 0,
            'repairable_devices' => $groupStats['repairable_devices'] ?? 0,
            'dead_devices' => $groupStats['dead_devices'] ?? 0,
            'no_weight' => $groupStats['no_weight'] ?? 0,
            'devices_powered' => $groupStats['devices_powered'] ?? 0,
            'devices_unpowered' => $groupStats['devices_unpowered'] ?? 0,
        ];
    }

    /**
     * Adds a volunteer to the group.
     *
     * @param \App\User $volunteer A registered user.
     */
    public function addVolunteer($volunteer)
    {
        UserGroups::updateOrCreate([
            'user' => $volunteer->id,
            'group' => $this->idgroups,
        ], [
            'status' => 1,
            'role' => Role::RESTARTER,
        ]);
    }

    /**
     * Convert an existing volunteer of a group into a host of the group.
     *
     * This also converts the volunteer's overall role into that of a host when applicable.
     *
     * @param App\User $groupVolunteer A user who is already a member of the group.
     */
    public function makeMemberAHost($groupMember)
    {
        if (! $this->allVolunteers()->pluck('user')->contains($groupMember->id)) {
            throw new \Exception('Volunteer is not currently in this group.  Only existing group members can be made hosts.');
        }

        $userGroupAssociation = UserGroups::where('user', $groupMember->id)
            ->where('group', $this->idgroups)->first();
        $userGroupAssociation->role = Role::HOST;
        $userGroupAssociation->save();

        // Update user's role (only if currently Restarter role)
        $groupMember->convertToHost();
    }

    public function getShareableLinkAttribute()
    {
        if (! empty($this->shareable_code)) {
            return url("group/invite/{$this->shareable_code}");
        }

        return '';
    }

    /**
     * @param int|null $user_id
     * @return bool
     */
    public function isVolunteer($user_id = null)
    {
        $attributes = ['user' => $user_id ?: auth()->id()];

        return $this->allConfirmedVolunteers()->where($attributes)->exists();
    }

    public function addEvent($event)
    {
        $event->theGroup()->associate($this);
    }

    public function parties()
    {
        return $this->hasMany(Party::class, 'group', 'idgroups');
    }

    /**
     * All parties for the group that are taking place today or later.
     *
     * @author Christopher Kelker - @date 2019-03-21
     * @editor  Christopher Kelker, Neil Mather
     * @version 1.0.1
     * @return  [type]
     */
    public function upcomingParties($exclude_parties = [])
    {
        $from = date('Y-m-d');

        if (! empty($exclude_parties)) {
            return $this->parties()
                ->where('event_date', '>=', $from)
                ->whereNotIn('idevents', $exclude_parties)
                ->get();
        }

        return $this->parties()->where('event_date', '>=', $from)->get();
    }

    /**
     * [pastParties description]
     * All Past Parties where between the Start Parties Date
     * is yesterday or a month earlier.
     *
     * @author Christopher Kelker - @date 2019-03-21
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @return  [type]
     */
    public function pastParties($exclude_parties = [])
    {
        if (! empty($exclude_parties)) {
            return $this->parties()
                ->where('event_date', '<', date('Y-m-d'))
                ->whereNotIn('idevents', $exclude_parties)
                ->get();
        }

        return $this->parties()->where('event_date', '<', date('Y-m-d'))->get();
    }

    /**
     * [totalPartiesHours description]
     * Total Group Parties Hours.
     *
     * @author Christopher Kelker - @date 2019-03-21
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @return  [type]
     */
    public function totalPartiesHours()
    {
        $sum = 0;

        foreach ($this->parties as $party) {
            $sum += $party->hours;
        }

        return $sum;
    }

    public function groupImagePath()
    {
        if (is_object($this->groupImage) && is_object($this->groupImage->image)) {
            return asset('/uploads/mid_'.$this->groupImage->image->path);
        }

        return url('/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg');
    }

    public function getNextUpcomingEvent()
    {
        $event = $this->parties()
            ->whereNotNull('wordpress_post_id')
            ->whereDate('event_date', '>=', date('Y-m-d'))
            ->orderBy('event_date', 'asc');

        if (! $event->count()) {
            return null;
        }

        return $event->first();
    }

    public function userEvents()
    {
        return $this->parties()
            ->join('events_users', 'events.idevents', '=', 'events_users.event')
            ->where(function ($query) {
                $query->where('events.group', $this->idgroups)
                    ->where('events_users.user', auth()->id());
            })
            ->select('events.*')
            ->groupBy('events.idevents')
            ->orderBy('events.idevents', 'ASC')
            ->get();
    }

    public function getApprovedAttribute()
    {
        return ! is_null($this->wordpress_post_id);
    }

    public function networks()
    {
        return $this->belongsToMany(Network::class, 'group_network', 'group_id', 'network_id');
    }

    public function isMemberOf($network)
    {
        return $this->networks->contains($network);
    }

    // If just one of the networks that the group is a member of
    // should push to Wordpress, then we should push.
    public function eventsShouldPushToWordpress()
    {
        foreach ($this->networks as $network) {
            if ($network->events_push_to_wordpress) {
                return true;
            }
        }

        return false;
    }

    // If just one of the networks that the group is a member of
    // should push to Wordpress, then we should push.
    public function changesShouldPushToZapier()
    {
        foreach ($this->networks as $network) {
            if ($network->include_in_zapier) {
                return true;
            }
        }

        return false;
    }

    public function getMaxUpdatedAtDevicesUpdatedAtAttribute()
    {
        return strtotime($this->updated_at) > strtotime($this->devices_updated_at) ? $this->updated_at : $this->devices_updated_at;
    }
}
