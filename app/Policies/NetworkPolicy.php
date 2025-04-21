<?php

namespace App\Policies;

use App\Network;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NetworkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the network.
     */
    public function view(User $user, Network $network): bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->hasRole('NetworkCoordinator') && $user->networks->contains($network)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create networks.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the network.
     */
    public function update(User $user, Network $network): bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->hasRole('NetworkCoordinator') && $user->networks->contains($network)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can associate groups to networks.
     *
     * @return mixed
     */
    public function associateGroups(User $user, Network $network)
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->hasRole('NetworkCoordinator') && $user->networks->contains($network)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the network.
     */
    public function delete(User $user, Network $network): bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }
    }

    /**
     * Determine whether the user can restore the network.
     */
    public function restore(User $user, Network $network): bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }
    }

    /**
     * Determine whether the user can permanently delete the network.
     */
    public function forceDelete(User $user, Network $network): bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }
    }
}
