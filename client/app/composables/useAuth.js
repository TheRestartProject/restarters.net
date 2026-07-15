import { computed } from 'vue'
import { useAuthStore } from '../stores/auth.js'

/**
 * Sugar over stores/auth.js: {user, loggedIn, hasRole, login, logout}.
 *
 * --- Role-check semantics (must match the Laravel app exactly) ---
 *
 * The Laravel codebase actually has TWO hasRole() implementations, and they
 * do NOT behave identically:
 *
 *   1. app/User.php::hasRole($roleName) - the one used by every controller,
 *      policy and view in the app (Group.php, UserController, EventController,
 *      NetworkPolicy, GroupController, ApiController, ...):
 *
 *        $usersRole = $this->role()->first()->role;
 *        if ($usersRole == 'Root') return true;      // Root satisfies every check
 *        if ($usersRole == $roleName) return true;    // otherwise EXACT match
 *        return false;
 *
 *   2. app/Helpers/Fixometer.php::hasRole($user, $role) - an older static
 *      helper, still present but with much sparser call sites, that title-cases
 *      its argument before comparing ($usersRole == ucwords($role)) so callers
 *      can pass lowercase role names. Same Root wildcard, same lack of any
 *      hierarchy beyond Root.
 *
 * Both agree on the only semantics that matter here: 'Root' (role id 1, see
 * app/Role.php::ROOT) satisfies *every* hasRole() check, and every other role
 * is checked by exact, case-sensitive string equality against the role name
 * (Administrator, Host, Restarter, Guest, NetworkCoordinator - see
 * app/Role.php + database/migrations/2018_04_19_102108_initialise_fixometer_db.php
 * and .../2020_04_19_095514_add_networkcoordinator_to_roles_table.php).
 *
 * IMPORTANT: there is NO hierarchy beyond the Root wildcard. hasRole('Administrator')
 * being true does NOT imply hasRole('Host') or hasRole('NetworkCoordinator') is
 * also true - each role is checked independently, exactly as the PHP does. This
 * mirrors app/User.php::hasRole() (the canonical, actually-enforced version), not
 * a hypothetical "roles form a hierarchy" reading of the Fixometer helper's
 * ucwords() leniency (which only forgives casing, not role identity).
 *
 * The session payload (GET /api/v2/session) carries the role name as
 * `user.role_name` (design.md contract) - that is the field compared here.
 */
export function useAuth() {
  const authStore = useAuthStore()

  const user = computed(() => authStore.user)
  const loggedIn = computed(() => authStore.loggedIn)

  function hasRole(role) {
    const roleName = authStore.user?.role_name

    if (!roleName) {
      return false
    }

    if (roleName === 'Root') {
      return true
    }

    return roleName === role
  }

  function login(credentials) {
    return authStore.login(credentials)
  }

  function logout() {
    return authStore.logout()
  }

  return { user, loggedIn, hasRole, login, logout }
}
