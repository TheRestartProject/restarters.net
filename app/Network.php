<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Group;
use App\Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Network extends Model
{
    use HasFactory;

    /**
     * Networks with their own site, keyed by a word in the site's host name, with the logo that site shows in
     * place of the Restarters wordmark.  Every other host is the Restarters site.
     */
    private const SITES = [
        'repairshare' => ['shortname' => 'repairshare', 'logo' => 'images/logos/repairshare.png'],
        'repairtogether' => ['shortname' => 'repairtogether', 'logo' => 'images/logos/repairtogether.png'],
        'hauts-de-france' => ['shortname' => 'hauts-de-france', 'logo' => 'images/logos/RC-Hauts-de-France.png'],
        'test-restarters' => ['shortname' => 'test', 'logo' => null],
    ];

    /**
     * The network whose site is served on this host.
     */
    public static function forHost(string $host): ?Network
    {
        foreach (self::SITES as $word => $site) {
            if (str_contains($host, $word)) {
                return self::where('shortname', $site['shortname'])->first();
            }
        }

        return self::where('shortname', 'like', 'restarters')->first();
    }

    /**
     * The network whose site the browser making this request is on.  The client calls the API on restarters.net
     * from every network's site, so the browser's Origin names the site; a same-origin GET has no Origin, and then
     * the request's own host does.
     */
    public static function forRequest(Request $request): ?Network
    {
        $originHost = parse_url((string) $request->headers->get('Origin'), PHP_URL_HOST);

        return self::forHost($originHost ?: $request->getHost());
    }

    /**
     * The logo this network's site shows in place of the Restarters wordmark, if it has one.
     */
    public function siteLogo(): ?string
    {
        foreach (self::SITES as $site) {
            if ($site['shortname'] === $this->shortname) {
                return $site['logo'];
            }
        }

        return null;
    }

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
        // One query for all of the network's groups rather than one per group.  Events are ordered by group
        // (in the network's group order) to match the previous per-group concatenation.
        $groups = $this->groups;
        $groupIds = $groups->pluck('idgroups')->all();

        if (empty($groupIds)) {
            return [];
        }

        $byGroup = Party::where('approved', false)
            ->whereNull('deleted_at')
            ->whereIn('events.group', $groupIds)
            ->with(['theGroup.groupImage.image', 'allDevices'])
            ->withCount('allInvited')
            ->get()
            ->groupBy('group');

        $events = [];

        foreach ($groups as $group) {
            foreach ($byGroup->get($group->idgroups, []) as $event) {
                $events[] = $event;
            }
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
