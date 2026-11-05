import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useEventPermissions, HOST_ROLE } from '../../app/composables/useEventPermissions.js'
import { useAuthStore } from '../../app/stores/auth.js'
import { useGroupsStore } from '../../app/stores/groups.js'

// Mirrors app/Helpers/Fixometer::userCanCreateEvents (07e6abd7cc^):
// Root/Administrator/NetworkCoordinator, or host (role 3) of any group. Host
// status must come from the UNCAPPED memberships list, not the dashboard's
// your_groups (capped at 5) - a host of a 6th+ group was wrongly denied.

describe('composables/useEventPermissions', () => {
  let authStore
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    authStore = useAuthStore()
    groupsStore = useGroupsStore()
    groupsStore.memberships = []
    groupsStore.fetchMemberships = vi.fn(function () {
      return Promise.resolve(this.memberships)
    })
  })

  it('lets Administrators create events with no hosted groups', () => {
    authStore.user = { role_name: 'Administrator' }
    expect(useEventPermissions().canCreateEvents.value).toBe(true)
  })

  it('lets NetworkCoordinators create events', () => {
    authStore.user = { role_name: 'NetworkCoordinator' }
    expect(useEventPermissions().canCreateEvents.value).toBe(true)
  })

  it('lets Root create events (wildcard role)', () => {
    authStore.user = { role_name: 'Root' }
    expect(useEventPermissions().canCreateEvents.value).toBe(true)
  })

  it('lets a plain host of any group create events', () => {
    authStore.user = { role_name: 'Restarter' }
    groupsStore.memberships = [{ id: 3, name: 'G', role: HOST_ROLE, archived: false }]
    expect(useEventPermissions().canCreateEvents.value).toBe(true)
  })

  it('denies a non-host, non-admin user', () => {
    authStore.user = { role_name: 'Restarter' }
    groupsStore.memberships = [{ id: 3, name: 'G', role: 1, archived: false }]
    expect(useEventPermissions().canCreateEvents.value).toBe(false)
  })

  it('reads host status from the uncapped memberships list, not a 5-item cap', () => {
    // 8 hosted groups - the dashboard your_groups source would have capped at 5
    // and (alphabetically) could exclude the one being checked.
    authStore.user = { role_name: 'Restarter' }
    groupsStore.memberships = Array.from({ length: 8 }, (_, i) => ({
      id: i + 1, name: `G${i + 1}`, role: HOST_ROLE, archived: false,
    }))
    const perms = useEventPermissions()
    expect(perms.hostedGroupIds.value).toHaveLength(8)
    expect(perms.canManageEventForGroup(8)).toBe(true)
  })

  it('canManageEventForGroup: admin manages any group, host only their own', () => {
    authStore.user = { role_name: 'Host' }
    groupsStore.memberships = [{ id: 9, name: 'Mine', role: HOST_ROLE, archived: false }]
    const perms = useEventPermissions()
    expect(perms.canManageEventForGroup(9)).toBe(true)
    expect(perms.canManageEventForGroup(10)).toBe(false)

    authStore.user = { role_name: 'Administrator' }
    expect(useEventPermissions().canManageEventForGroup(10)).toBe(true)
  })
})
