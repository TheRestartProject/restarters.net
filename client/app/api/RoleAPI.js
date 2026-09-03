import BaseAPI from './BaseAPI.js'

/**
 * /api/v2/roles + /api/v2/permissions (API\RoleController, Administrator-
 * only throughout, already implemented server-side). `list()` was added by
 * D3 (/user/all's role filter dropdown); this task (design.md §6.2 Phase D
 * task D4 - PR #863's RolesPage.vue is the functional spec) adds the rest of
 * RoleController's surface: single-role get, the permissions catalogue, and
 * replacing a role's granted permissions.
 */
export default class RoleAPI extends BaseAPI {
  list() {
    return this.$get('/api/v2/roles')
  }

  get(id) {
    return this.$get(`/api/v2/roles/${id}`)
  }

  // GET /api/v2/permissions - {id, name}[], the full permission catalogue
  // used to render the role-edit checkbox matrix.
  listPermissions() {
    return this.$get('/api/v2/permissions')
  }

  // PUT /api/v2/roles/{id}/permissions {permissions: [id, ...]} - replaces
  // (not merges) the role's granted permission set.
  updatePermissions(id, permissions) {
    return this.$put(`/api/v2/roles/${id}/permissions`, { permissions })
  }
}
