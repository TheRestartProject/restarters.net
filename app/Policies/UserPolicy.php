<?php

namespace App\Policies;

use App\Helpers\Fixometer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether one user can change the Repair Directory role of another to a specific value.
     *
     * @param  \App\Models\User  $user  The authenticated user
     * @param  \App\Models\User  $victim  The user being modified
     * @param  int  $role  The new role
     * @return mixed
     */
    public function changeRepairDirRole(User $user, User $victim, int $role)
    {
        // We have rules for whether you can change the Repair Directory role.  Code is structured for readability
        // of these rules, rather than a single big if.
        //
        // Default to forbidden.
        $ret = false;

        if ($user->repairdir_role() === Role::REPAIR_DIRECTORY_SUPERADMIN) {
            // SuperAdmins can do anything
            $ret = true;
        } elseif ($user->repairdir_role() === Role::REPAIR_DIRECTORY_REGIONAL_ADMIN) {
            // Regional Admins can do some things.
            if ($victim->id === $user->id) {
                // Operating on themselves.
                if ($role === Role::REPAIR_DIRECTORY_NONE || $role === Role::REPAIR_DIRECTORY_EDITOR) {
                    // Demoting themselves.
                    $ret = true;
                }
            } else {
                // Operating on someone else.
                if ($role === Role::REPAIR_DIRECTORY_NONE || $role === Role::REPAIR_DIRECTORY_EDITOR) {
                    // To/From no access and editor.
                    $ret = true;
                }
            }
        }

        return $ret;
    }

    /**
     * Determine whether this user can view the Repair Directory settings for users
     *
     * @return mixed
     */
    public function viewRepairDirectorySettings(User $user)
    {
        return $user && ($user->isRepairDirectoryRegionalAdmin() || $user->isRepairDirectorySuperAdmin());
    }

    /**
     * Determine whether this user can see the Admin menu.
     *
     * @return mixed
     */
    public function viewAdminMenu(User $user)
    {
        return $user &&
            (Fixometer::hasRole($user, 'Administrator') ||
              Fixometer::hasPermission('verify-translation-access') ||
              Fixometer::hasRole($user, 'NetworkCoordinator') ||
              $this->accessRepairDirectory($user));
    }

    /**
     * Determine whether this user can access the Repair Directory via the menu.
     *
     * @return mixed
     */
    public function accessRepairDirectory(User $user)
    {
        return $user && ($user->isRepairDirectoryEditor() || $user->isRepairDirectoryRegionalAdmin() || $user->isRepairDirectorySuperAdmin());
    }
}
