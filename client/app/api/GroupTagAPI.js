import BaseAPI from './BaseAPI.js'

/**
 * /api/v2/group-tags (API\GroupTagController, already implemented
 * server-side - design.md §6.2 Phase D task D4, PR #863's GroupTagsPage.vue
 * is the functional spec). Global (cross-network) tags only - network-scoped
 * tags are a separate family under /api/v2/networks/{id}/tags (design.md §
 * 6.2 Phase E task E1, GroupAPI already has its own role-filtered `tags()`
 * reader for the group-edit form's tag picker; this is the distinct
 * Administrator-only global CRUD surface). List/get are public;
 * create/update/delete require Administrator.
 */
export default class GroupTagAPI extends BaseAPI {
  list() {
    return this.$get('/api/v2/group-tags')
  }

  get(id) {
    return this.$get(`/api/v2/group-tags/${id}`)
  }

  create(payload) {
    return this.$post('/api/v2/group-tags', payload)
  }

  update(id, payload) {
    return this.$put(`/api/v2/group-tags/${id}`, payload)
  }

  del(id) {
    return this.$del(`/api/v2/group-tags/${id}`)
  }
}
