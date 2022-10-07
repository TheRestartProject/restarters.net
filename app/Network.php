<?php

namespace App;

use App\Group;
use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    public function groups()
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

    public function coordinators()
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
        $events = collect([]);

        foreach ($groups as $group) {
            $events->push($group->upcomingParties()->whereNull('wordpress_post_id')->whereNull('deleted_at'));
        }

        return $events->flatten(1);
    }

    public function logo()
    {
        return url('/uploads/' . $this->logo);
    }

    public function groupsNotIn()
    {
        $networkGroupsIds = $this->groups->pluck('idgroups')->toArray();

        return Group::all()->filter(function ($group) use ($networkGroupsIds) {
            return ! in_array($group->idgroups, $networkGroupsIds);
        });
    }

    public function stats()
    {
        $stats = \App\Group::getGroupStatsArrayKeys();

        $eEmissionRatio = \App\Helpers\LcaStats::getEmissionRatioPowered();
        $uEmissionratio = \App\Helpers\LcaStats::getEmissionRatioUnpowered();

        foreach ($this->groups as $group) {
            $singleGroupStats = $group->getGroupStats($eEmissionRatio, $uEmissionratio);

            foreach ($singleGroupStats as $key => $value) {
                $stats[$key] = $stats[$key] + $value;
            }
        }

        return $stats;
    }
}
