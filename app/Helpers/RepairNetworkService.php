<?php

namespace App\Helpers;

use App\Group;
use App\Network;
use App\Role;
use App\User;

class RepairNetworkService
{
    public function addGroupToNetwork($user, $group, $network)
    {
        // TODO: Network leads will have permissions once this role is added.
        if (! $user->hasRole('Administrator')) {
            throw new \Exception('Only Adminstrators can add groups to networks');
        }

        $network->addGroup($group);
    }
}
