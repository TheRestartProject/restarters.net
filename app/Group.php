<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Auditable;

class Group extends Model implements Auditable
{
    use HasFactory;

    use \OwenIt\Auditing\Auditable;

    protected $table = 'groups';
    protected $primaryKey = 'idgroups';

    // Eager-loading reduces N+1 queries.
    protected $with = ['networks'];

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
        'country_code',
        'free_text',
        'facebook',
        'wordpress_post_id',
        'approved',
        'shareable_code',
        'network_id',
        'external_id',
        'devices_updated_at',
        'timezone',
        'phone',
        'network_data',
        'email',
        'archived_at'
    ];

    protected $appends = ['ShareableLink', 'auto_approve'];

    protected $casts = [
        // JSON fields in the database should be converted to/from arrays.
        'network_data' => 'array'
    ];

    // The distance is not in the groups table; we add it on some queries from the select.
    private $distance = null;

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

        static::addGlobalScope('all_confirmed_hosts_count', function ($builder) {
            $builder->withCount('allConfirmedHosts');
        });

        static::addGlobalScope('all_confirmed_restarters_count', function ($builder) {
            $builder->withCount('allConfirmedRestarters');
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
        return $this->hasMany(\App\UserGroups::class, 'group', 'idgroups')->where('role', Role::HOST);
    }

    public function allRestarters()
    {
        return $this->hasMany(\App\UserGroups::class, 'group', 'idgroups')->where('role', Role::RESTARTER);
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
                $query->whereNull('deleted_at')
                    ->where('status', 1)
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
        return !Party::withTrashed()->where('events.group', $this->idgroups)
            ->join('devices', 'event', '=', 'events.idevents')->first();
    }

    public static function getGroupStatsArrayKeys()
    {
        return \App\Party::getEventStatsArrayKeys() + ['parties' => 0];
    }

    public function getGroupStats($eEmissionRatio = null, $uEmissionratio = null)
    {
        if (is_null($eEmissionRatio)) {
            $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        }
        if (is_null($uEmissionratio)) {
            $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();
        }

        $allPastEvents = Party::past()
            ->where('events.group', $this->idgroups)
            ->get();

        $result = \App\Party::getEventStatsArrayKeys();

        // Rollup all events stats into stats for this group.
        foreach ($allPastEvents as $event) {
            $eventStats = $event->getEventStats($eEmissionRatio, $uEmissionratio);

            foreach ($eventStats as $statKey => $statValue) {
                $result[$statKey] += $statValue;
            }
        }

        $result['parties'] = count($allPastEvents);

        return $result;
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

    public function parties()
    {
        return $this->hasMany(Party::class, 'group', 'idgroups');
    }

    /**
     * All parties for the group that are taking place in the future.
     *
     * @author Christopher Kelker - @date 2019-03-21
     * @editor  Christopher Kelker, Neil Mather
     * @version 1.0.1
     * @return  [type]
     */
    public function upcomingParties()
    {
        $from = date('Y-m-d H:i:s');
        return $this->parties()->where('event_end_utc', '>', $from)->get();
    }

    /**
     * [pastParties description]
     * All Past Parties.
     *
     * @author Christopher Kelker - @date 2019-03-21
     * @editor  Christopher Kelker
     * @version 1.0.0
     * @return  [type]
     */
    public function pastParties()
    {
        $now = date('Y-m-d H:i:s');
        return $this->parties()->where('event_end_utc', '<', $now)->get();
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
            ->where('approved', true)
            ->where('event_start_utc', '>=', date('Y-m-d H:i:s'))
            ->orderBy('event_start_utc', 'asc');

        if (! $event->count()) {
            return null;
        }

        return $event->first();
    }

    public function networks()
    {
        return $this->belongsToMany(Network::class, 'group_network', 'group_id', 'network_id');
    }

    public function isMemberOf($network)
    {
        return $this->networks->contains($network);
    }

    // If just one of the networks that the group is a member of should push to Wordpress, then we should push.
    // If an group is not approved, then we should not push the events to Wordpress.
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

    public function getAutoApproveAttribute()
    {
        // A group's events are auto-approved iff all the networks that the group belongs to are set to auto-approve
        // events.
        $autoapprove = false;

        $networks = $this->networks;

        if ($networks && count($networks)) {
            $autoapprove = true;

            foreach ($networks as $network) {
                $autoapprove &= $network->auto_approve_events;
            }
        }

        return $autoapprove;
    }

    public function getDistanceAttribute()
    {
        return $this->distance;
    }

    public function setDistanceAttribute($val)
    {
        $this->distance = $val;
    }

    public function createDiscourseGroup() {
        // Get the host who created the group.
        $success = false;
        $member = UserGroups::where('group', $this->idgroups)->first();
        $host = null;

        if (!empty($member)) {
            $host = User::find($member->user);
        }

        $unique = '';

        do {
            $retry = false;

            try {
                // We want to internationalise the message.  Use the languages of any networks that the group
                // is in.
                $text = '';
                $langs = [];

                foreach ($this->networks as $network) {
                    $lang = $network->default_language;

                    if (!in_array($lang, $langs)) {
                        $text .= Lang::get('groups.discourse_title',[
                            'group' => $this->name,
                            'link' => env('APP_URL') . '/group/view/' . $this->idgroups,
                            'help' => 'https://talk.restarters.net/t/how-to-communicate-with-your-repair-group/6293'
                        ],$lang);

                        $langs[] = $lang;
                    }
                }

                // We want the host to create the group, so use their username.  The API key should
                // allow us to do this - see https://meta.discourse.org/t/how-can-an-api-user-create-posts-as-another-user/45968/3.
                $client = app('discourse-client', [
                    'username' => env('DISCOURSE_APIUSER'),
                ]);

                $name = $this->getDiscourseGroupName($unique);

                $params = [
                    'group' => [
                        'name' => "$name$unique",
                        'full_name' => $this->name,
                        'mentionable_level' => 4,
                        'messageable_level' => 4,
                        'visibility_level' => 0,
                        'members_visibility_level' => 0,
                        'automatic_membership_email_domains' => null,
                        'automatic_membership_retroactive' => false,
                        'primary_group' => false,
                        'flair_url' => $this->groupImagePath(),
                        'flair_bg_color' => null,
                        'flair_color' => null,
                        'bio_raw' => $text,
                        'public_admission' => false,
                        'public_exit' => false,
                        'default_notification_level' => 3,
                        'publish_read_state' => true,
                        'owner_usernames' => $host ? $host->username : env('DISCOURSE_APIUSER')
                    ]
                ];

                $endpoint = '/admin/groups.json';

                Log::info('Creating group : '.json_encode($params));
                $response = $client->request(
                    'POST',
                    $endpoint,
                    [
                        'form_params' => $params,
                    ]
                );

                Log::info('Response status: '.$response->getStatusCode());
                Log::info('Response body: '.$response->getBody());

                if ($response->getStatusCode() !== 200) {
                    if (strpos($response->getBody(), 'Name has already been taken') !== false) {
                        // Discourse sometimes seems to have groups stuck in a bad state which are not accessible.
                        // This may be a consequence of testing with multiple Restarters instances against the same
                        // Discourse instance.
                        //
                        // This can result in a create failure, and a group which we cannot then locate to delete.
                        // So skip over it and retry creation with a different name.
                        $retry = true;
                        $unique = $unique ? ($unique + 1) : 1;
                    } else {
                        Log::error('Could not create group ('.$this->idgroups.') thread: '.$response->getReasonPhrase());
                    }
                } else {
                    // We want to save the discourse thread id in the group, so that we can invite people to it later
                    // when they join.
                    $json = json_decode($response->getBody(), true);
                    if (empty($json['basic_group'])) {
                        throw new \Exception('Group not found in create response');
                    }

                    $this->discourse_group = "$name$unique";
                    $this->save();
                    $success = true;
                }
            } catch (\Exception $ex) {
                Log::error('Could not create group ('.$this->idgroups.') thread: '.$ex->getMessage());
            }
    	} while ($retry);

        return $success;
    }

    public function getTimezoneAttribute($value)
    {
        // We might have a timezone attribute on the group.
        if ($value) {
            return $value;
        }

        // See if the network(s) the group is in have a consensus timezone.
        $timezone = null;

        foreach ($this->networks as $network) {
            if ($network->timezone) {
                if ($timezone) {
                    if ($timezone != $network->timezone) {
                        // This should not occur if the networks are set up correctly.
                        \Sentry\captureMessage("Problem getting timezone for group {$this->idgroups} - networks conflict with $timezone and {$network->timezone}.  Will use $timezone.");
                        // TODO Convert to exception once groups have timezones set by Neil.
                        // throw new \Exception("Group does not have own timezone and is in networks with conflicting timezones");
                    }
                } else {
                    // First timezone found.
                    $timezone = $network->timezone;
                }
            }
        }

        if (!$timezone) {
            // This should not occur if the networks are set up correctly.
            // TODO Later on we should throw an exception, but only once this code has gone live, as we rely on
            // the default behaviour during migration.
            //throw new \Exception("Group {$this->idgroups} cannot resolve timezone");
            $timezone = 'Europe/London';
        }

        return $timezone;
    }

    // TODO We've started to refactor into scopes, but this isn't complete yet.
    public function scopeMembers() {
        return User::join('users_groups', 'users_groups.user', '=', 'users.id')
            ->where('users_groups.group', $this->idgroups)
            ->select('users.*');
    }

    public function scopeMembersUndeleted($query) {
        $query = $query->members();
        return $query->whereNull('users_groups.deleted_at');
    }

    public function scopeMembersJoined($query) {
        $query = $query->membersUndeleted();
        return $query->where('users_groups.status', 'like', 1);
    }

    public function scopeMembersRestarters($query) {
        $query = $query->membersJoined();
        return $query->where('users_groups.role', Role::RESTARTER);
    }

    public function scopeMembersHosts($query) {
        $query = $query->membersJoined();
        return $query->where('users_groups.role', Role::HOST);
    }

    public function scopeUnapproved($query) {
        return $query->where('approved', false);
    }

    public function scopeUnapprovedVisibleTo($query, $user_id) {
        $u = User::findOrFail($user_id);
        $unetworks = $u->networks;
        $ret = [];

        if ($u->hasRole('Administrator')) {
            // Can see all.
            $ret = $this->unapproved()->get();
        } else if ($u->hasRole('NetworkCoordinator')) {
            // Can see groups for this network.  Logic doesn't scale well, but we will have few unapproved
            // groups at any one time.
            $groups = $this->unapproved()->get();

            foreach ($groups as $group) {
                foreach ($group->networks as $network) {
                    foreach ($unetworks as $user_network) {
                        if ($network->id == $user_network->id) {
                            $ret[] = $group;
                            break;
                        }
                    }
                }
            }

            $ret = array_unique($ret);
        }

        return $ret;
    }

    /**
     * Get a name for the Discourse group.
     *
     * @return string
     */
    public function getDiscourseGroupName($unique)
    {
        // Restricted characters allowed in name, and only 20 characters.
        //
        // Leave 1 spare for uniqueness.
        $name = str_replace(' ', '_', trim($this->name));
        $name = preg_replace("/[^A-Za-z0-9]/", '', $name);
        $name = substr($name, 0, 19);

        return $name . $unique;
    }
}
