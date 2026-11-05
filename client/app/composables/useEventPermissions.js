import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuth } from '~/composables/useAuth.js'
import { useGroupsStore } from '~/stores/groups.js'

// Host = UserGroups.role 3 (app/Role.php HOST). Kept as a named constant so the
// three event pages that gate on it stop re-declaring a bare `3`.
export const HOST_ROLE = 3

/**
 * Client-side mirror of app/Helpers/Fixometer::userCanCreateEvents /
 * userHasEditPartyPermission, used to gate the "Add event" CTA and the
 * edit/duplicate pages.
 *
 * Sourced from the UNCAPPED GET /api/v2/users/me/groups membership list
 * (groupsStore.memberships) - NOT the dashboard's your_groups, which is capped
 * at 5 alphabetically, so a host of a 6th+ group was wrongly denied. Call
 * `ensureLoaded()` from onMounted before reading the computeds.
 *
 * Legacy userCanCreateEvents (app/Helpers/Fixometer.php:245-270 at 07e6abd7cc^):
 * true for Root/Administrator/NetworkCoordinator, or any user who hosts (role 3)
 * at least one group.
 */
export function useEventPermissions() {
  const { hasRole } = useAuth()
  const groupsStore = useGroupsStore()
  const { memberships } = storeToRefs(groupsStore)

  const isSuperCreator = computed(
    () => hasRole('Administrator') || hasRole('NetworkCoordinator')
    // hasRole already treats Root as satisfying every check.
  )

  const hostedGroupIds = computed(() =>
    (memberships.value || [])
      .filter((g) => g.role === HOST_ROLE)
      .map((g) => g.id)
  )

  const canCreateEvents = computed(
    () => isSuperCreator.value || hostedGroupIds.value.length > 0
  )

  // Can this user edit/duplicate an event belonging to `groupId`?
  // Administrators (and Root) can touch any event; hosts only their own groups'.
  function canManageEventForGroup(groupId) {
    return hasRole('Administrator') || (groupId != null && hostedGroupIds.value.includes(groupId))
  }

  function ensureLoaded() {
    return groupsStore.fetchMemberships()
  }

  return {
    isSuperCreator,
    hostedGroupIds,
    canCreateEvents,
    canManageEventForGroup,
    ensureLoaded,
  }
}
