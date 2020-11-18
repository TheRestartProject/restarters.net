<?php

namespace App\Policies;

use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether one user can change the Repair Directory role of another to a specific value.
     *
     * @param  \App\User  $user
     * @param  \App\User  $user
     * @param  int  $role
     * @return mixed
     */
    public function canChangeRepairDirRole(User $perpetrator, User $victim, int $role)
    {
        # We have rules for whether you can change the Repair Directory role.  Code is structured for readability
        # of these rules, rather than a single big if.
        #
        # Default to forbidden.
        $ret = FALSE;
        error_log("Can change role? {$perpetrator->repairdir_role()} . {$victim->id} vs {$perpetrator->id}" );

        if ($perpetrator->repairdir_role() === Role::REPAIR_DIRECTORY_SUPERADMIN) {
            # SuperAdmins can do anything
            $ret = TRUE;
        } else if ($perpetrator->repairdir_role() === Role::REPAIR_DIRECTORY_REGIONAL_ADMIN) {
            # Regional Admins can do some things.
            error_log("Regional admin");
            if ($victim->id === $perpetrator->id) {
                # Operating on themselves.
                error_log("Self");
                if ($role === Role::REPAIR_DIRECTORY_NONE || $role === Role::REPAIR_DIRECTORY_EDITOR) {
                    # Demoting themselves.
                    error_log("Demote");
                    $ret = TRUE;
                }
            } else {
                # Operating on someone else.
                if ($role === Role::REPAIR_DIRECTORY_NONE || $role === Role::REPAIR_DIRECTORY_EDITOR) {
                    # To/From no access and editor.
                    $ret = TRUE;
                }
            }
        }

        return $ret;
    }
}
