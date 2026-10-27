<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Group;
use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    use HasFactory;

    /**
     * The network description is rendered with v-html in NetworkPage.vue, so sanitise it
     * on write for the same reason as Group::setFreeTextAttribute().
     */
    public function setDescriptionAttribute($val)
    {
        $this->attributes['description'] = is_null($val) ? null : \Stevebauman\Purify\Facades\Purify::clean($val);
    }

    /**
     * Get tags belonging to this network.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(GroupTags::class, 'network_id', 'id');
    }

    /**
     * Get all tags available to this network (network-specific + global).
     */
    public function availableTags()
    {
        return GroupTags::availableForNetwork($this->id);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_network', 'network_id', 'group_id');
    }

    public function addGroup($group)
    {
        $this->groups()->attach($group->idgroups);
    }

    public function containsGroup($group)
    {
        return $this->groups->contains($group);
    }

    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_network', 'network_id', 'user_id');
    }

    public function addCoordinator($coordinator)
    {
        $this->coordinators()->syncWithoutDetaching($coordinator->id);

        // Set us to a network coordinator (but don't demote us from admin).
        if ($coordinator->role != Role::ADMINISTRATOR) {
            $coordinator->role = Role::NETWORK_COORDINATOR;
            $coordinator->save();
        }
    }

    public function eventsRequiringModeration()
    {
        $groups = $this->groups;
        $events = [];

        foreach ($groups as $group) {
            $events = array_merge($events, $group->parties()->where('approved', false)->whereNull('deleted_at')->get()->all());
        }

        return $events;
    }

    public function sizedLogo($size)
    {
        $logo = preg_replace('/\\.([^.\\s]{3,4})$/', "-$size.$1", $this->logo);
        return $logo;
    }

    public function groupsNotIn()
    {
        $networkGroupsIds = $this->groups()->withoutGlobalScopes()->without(['networks', 'group_tags'])->pluck('groups.idgroups')->toArray();

        // Only the id and name are needed (for the "add groups" picker).  Skip the member-count global scopes
        // and the networks/tags eager loads, which are expensive across every group on the platform.
        $query = Group::withoutGlobalScopes()->without(['networks', 'group_tags'])->select(['idgroups', 'name'])->orderBy('name');

        if (! empty($networkGroupsIds)) {
            $query->whereNotIn('idgroups', $networkGroupsIds);
        }

        return $query->get();
    }

    /**
     * The approved, active groups in this network nearest to $from (not including it), with their distance in km.
     */
    public function nearestGroups(Group $from, int $count = 5)
    {
        if (is_null($from->latitude) || is_null($from->longitude)) {
            return collect();
        }

        return $this->groups()
            ->select('groups.idgroups', 'groups.name', 'groups.location', 'groups.latitude', 'groups.longitude')
            ->selectRaw(
                '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS dist',
                [$from->latitude, $from->longitude, $from->latitude]
            )
            ->where('groups.idgroups', '!=', $from->idgroups)
            ->where('groups.approved', true)
            ->whereNull('groups.archived_at')
            ->whereNotNull('groups.latitude')
            ->whereNotNull('groups.longitude')
            ->orderBy('dist')
            ->take($count)
            ->get();
    }

    /**
     * The groups in the network nearest to any of the user's own located groups in it, nearest first, with each
     * group listed once at its smallest distance.  The user's own groups are left out.  Null if they have no
     * located group in the network.
     */
    public function nearbyGroupsFor(User $user): ?array
    {
        $mine = Group::join('users_groups', 'users_groups.group', '=', 'groups.idgroups')
            ->join('group_network', 'group_network.group_id', '=', 'groups.idgroups')
            ->where('group_network.network_id', $this->id)
            ->where('users_groups.user', $user->id)
            ->whereNull('users_groups.deleted_at')
            ->whereNotNull('groups.latitude')
            ->whereNotNull('groups.longitude')
            ->orderBy('groups.name')
            ->select('groups.*')
            ->get();

        if ($mine->isEmpty()) {
            return null;
        }

        $mineIds = $mine->pluck('idgroups')->all();
        $nearest = [];

        foreach ($mine as $group) {
            // Ask for extra so that skipping the user's other groups still leaves enough.
            foreach ($this->nearestGroups($group, 5 + count($mineIds)) as $g) {
                if (in_array($g->idgroups, $mineIds)) {
                    continue;
                }

                if (! isset($nearest[$g->idgroups]) || $g->dist < $nearest[$g->idgroups]->dist) {
                    $nearest[$g->idgroups] = $g;
                }
            }
        }

        return [
            'from' => $mine->pluck('name')->implode(', '),
            'groups' => collect($nearest)->sortBy('dist')->take(5)->map(fn ($g) => [
                'id' => $g->idgroups,
                'name' => $g->name,
                'location' => $g->location,
                'distance' => round($g->dist, 1),
            ])->values()->all(),
        ];
    }

    public function stats()
    {
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        // Impact stats include archived groups (their past events still count); the group tally does not.
        $groups = $this->groups()->withoutGlobalScopes()->without(['networks', 'group_tags'])
            ->get(['groups.idgroups', 'groups.archived_at']);

        $allStats = \App\Group::bulkGroupStats($groups, $eEmissionRatio, $uEmissionratio);

        $stats = \App\Group::getGroupStatsArrayKeys();

        foreach ($allStats as $singleGroupStats) {
            foreach ($singleGroupStats as $key => $value) {
                $stats[$key] = $stats[$key] + $value;
            }
        }

        $stats['parties'] = $stats['parties'] ?? 0;
        $stats['groups'] = $groups->whereNull('archived_at')->count();

        return $stats;
    }
}
