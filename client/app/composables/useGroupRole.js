// Role ints per app/Role.php (api-contracts-phase-b.md B1 `your_groups[].role`).
// Not exported anywhere client-side yet (the session's user.role_name is a
// string, not this int) - pinned here and shared between
// DashboardYourGroups.vue-style badges and GroupsTable.vue's "mine" column.
const ROLE_LABEL_KEYS = {
  1: 'client.dashboard.role_root',
  2: 'client.dashboard.role_administrator',
  3: 'client.dashboard.role_host',
  4: 'client.dashboard.role_restarter',
  6: 'client.dashboard.role_network_coordinator',
}

const ROLE_VARIANTS = {
  1: 'dark',
  2: 'dark',
  3: 'primary',
  4: 'secondary',
  6: 'info',
}

export function useGroupRole() {
  function roleLabelKey(role) {
    return ROLE_LABEL_KEYS[role] || null
  }

  function roleVariant(role) {
    return ROLE_VARIANTS[role] || 'secondary'
  }

  return { roleLabelKey, roleVariant }
}
