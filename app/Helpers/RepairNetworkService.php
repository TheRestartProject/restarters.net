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
        if (! $user->hasRole('Administrator') &&
            ! ($user->hasRole('NetworkCoordinator') && $user->networks->contains($network))) {
            throw new \Exception('Only Administrators and Network Coordinators can add groups to networks');
        }

        $network->addGroup($group);
    }
}
