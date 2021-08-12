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
    }

    public function eventsRequiringModeration()
    {
        $groups = $this->groups;
        $events = collect([]);

        foreach ($groups as $group) {
            $events->push($group->parties()->whereNull('wordpress_post_id')->whereNull('deleted_at')->get());
        }

        return $events->flatten(1);
    }

    public function logo()
    {
        return $this->hasOne(\App\Xref::class, 'reference', 'id')
                    ->where('reference_type', config('restarters.xref_types.networks'))
                    ->where('object_type', 5);
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
        $stats = [
            'pax' => 0,
            'hours' => 0,
            'parties' => 0,
            'co2' => 0,
            'waste' => 0,
            'ewaste' => 0,
            'unpowered_waste' => 0,
            'fixed_devices' => 0,
            'fixed_powered' => 0,
            'fixed_unpowered' => 0,
            'repairable_devices' => 0,
            'dead_devices' => 0,
            'no_weight' => 0,
            'devices_powered' => 0,
            'devices_unpowered' => 0,
        ];

        $emissionRatio = \App\Helpers\FootprintRatioCalculator::calculateRatio();
        foreach ($this->groups as $group) {
            $singleGroupStats = $group->getGroupStats($emissionRatio);

            foreach ($singleGroupStats as $key => $value) {
                $stats[$key] = $stats[$key] + $value;
            }
        }

        return $stats;
    }
}
