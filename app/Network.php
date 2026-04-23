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
        $networkGroupsIds = $this->groups()->pluck('idgroups')->toArray();

        if (empty($networkGroupsIds)) {
            return Group::orderBy('name')->get();
        }

        return Group::whereNotIn('idgroups', $networkGroupsIds)->orderBy('name')->get();
    }

    public function stats()
    {
        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();
        $displacementFactor = \App\Device::getDisplacementFactor();

        // Get group IDs for this network
        $groupIds = $this->groups()->pluck('idgroups')->toArray();

        if (empty($groupIds)) {
            return \App\Group::getGroupStatsArrayKeys();
        }

        // Single aggregate query for all device stats
        $deviceStats = \DB::table('devices')
            ->join('events', 'devices.event', '=', 'events.idevents')
            ->join('categories', 'devices.category', '=', 'categories.idcategories')
            ->whereIn('events.group', $groupIds)
            ->where('events.event_start_utc', '<=', now())
            ->whereNull('events.deleted_at')
            ->select(\DB::raw("
                SUM(CASE WHEN categories.powered = 1 THEN 1 ELSE 0 END) as devices_powered,
                SUM(CASE WHEN categories.powered = 0 THEN 1 ELSE 0 END) as devices_unpowered,
                SUM(CASE WHEN devices.repair_status = 1 THEN 1 ELSE 0 END) as fixed_devices,
                SUM(CASE WHEN devices.repair_status = 1 AND categories.powered = 1 THEN 1 ELSE 0 END) as fixed_powered,
                SUM(CASE WHEN devices.repair_status = 1 AND categories.powered = 0 THEN 1 ELSE 0 END) as fixed_unpowered,
                SUM(CASE WHEN devices.repair_status = 2 THEN 1 ELSE 0 END) as repairable_devices,
                SUM(CASE WHEN devices.repair_status = 3 THEN 1 ELSE 0 END) as dead_devices,
                SUM(CASE WHEN devices.repair_status = 0 OR devices.repair_status IS NULL THEN 1 ELSE 0 END) as unknown_repair_status,
                SUM(CASE WHEN devices.repair_status = 1 AND categories.powered = 1 THEN COALESCE(categories.weight, 0) ELSE 0 END) as waste_powered,
                SUM(CASE WHEN devices.repair_status = 1 AND categories.powered = 0 THEN COALESCE(categories.weight, 0) ELSE 0 END) as waste_unpowered,
                SUM(CASE WHEN categories.powered = 1 AND COALESCE(categories.weight, 0) = 0 THEN 1 ELSE 0 END) as no_weight_powered,
                SUM(CASE WHEN categories.powered = 0 AND COALESCE(categories.weight, 0) = 0 THEN 1 ELSE 0 END) as no_weight_unpowered
            "))
            ->first();

        // Get event stats (participants, volunteers, hours)
        $eventStats = \DB::table('events')
            ->whereIn('events.group', $groupIds)
            ->where('events.event_start_utc', '<=', now())
            ->whereNull('events.deleted_at')
            ->select(\DB::raw("
                COUNT(*) as parties,
                SUM(COALESCE(pax, 0)) as participants,
                SUM(COALESCE(volunteers, 0)) as volunteers,
                SUM(COALESCE(hours, 3) * COALESCE(volunteers, 0)) as hours_volunteered
            "))
            ->first();

        // Calculate CO2 values
        $wastePowered = $deviceStats->waste_powered ?? 0;
        $wasteUnpowered = $deviceStats->waste_unpowered ?? 0;
        $co2Powered = $wastePowered * $eEmissionRatio * $displacementFactor;
        $co2Unpowered = $wasteUnpowered * $uEmissionRatio * $displacementFactor;

        return [
            'co2_powered' => round($co2Powered, 2),
            'co2_unpowered' => round($co2Unpowered, 2),
            'co2_total' => round($co2Powered + $co2Unpowered, 2),
            'waste_powered' => round($wastePowered, 2),
            'waste_unpowered' => round($wasteUnpowered, 2),
            'waste_total' => round($wastePowered + $wasteUnpowered, 2),
            'fixed_devices' => (int) ($deviceStats->fixed_devices ?? 0),
            'fixed_powered' => (int) ($deviceStats->fixed_powered ?? 0),
            'fixed_unpowered' => (int) ($deviceStats->fixed_unpowered ?? 0),
            'repairable_devices' => (int) ($deviceStats->repairable_devices ?? 0),
            'dead_devices' => (int) ($deviceStats->dead_devices ?? 0),
            'unknown_repair_status' => (int) ($deviceStats->unknown_repair_status ?? 0),
            'devices_powered' => (int) ($deviceStats->devices_powered ?? 0),
            'devices_unpowered' => (int) ($deviceStats->devices_unpowered ?? 0),
            'no_weight_powered' => (int) ($deviceStats->no_weight_powered ?? 0),
            'no_weight_unpowered' => (int) ($deviceStats->no_weight_unpowered ?? 0),
            'participants' => (int) ($eventStats->participants ?? 0),
            'volunteers' => (int) ($eventStats->volunteers ?? 0),
            'hours_volunteered' => (int) ($eventStats->hours_volunteered ?? 0),
            'invited' => 0,
            'parties' => (int) ($eventStats->parties ?? 0),
        ];
    }
}
