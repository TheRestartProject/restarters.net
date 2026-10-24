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
     * Determine whether the user can view the network page with its coordinator-only parts hidden.  That is
     * anyone who can fully view it, plus the volunteers and hosts of a group in the network.
     */
    public function viewPublic(User $user, Network $network): bool
    {
        if ($this->view($user, $network)) {
            return true;
        }

        return \App\UserGroups::where('user', $user->id)
            ->where(fn ($q) => $q->where('status', 1)->orWhereNull('status'))
            ->whereIn('group', \DB::table('group_network')->where('network_id', $network->id)->select('group_id'))
            ->exists();
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
