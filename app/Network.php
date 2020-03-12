<?php

namespace App;

use App\Group;

use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    public function addGroup($group)
    {
        $this->groups()->attach($group->idgroups);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_network', 'network_id', 'group_id');
    }

    public function containsGroup($group)
    {
        return $this->groups->contains($group);
    }
}
