import BaseAPI from './BaseAPI.js'

/**
 * Thin, hardcoded-path client for the group endpoint family. Full surface
 * (join/leave, invites, images, stats, nearby...) lands with the Groups
 * migration slice (design.md §5.3, §6.2); this is the scaffold-stage shape.
 */
export default class GroupAPI extends BaseAPI {
  list(params) {
    return this.$get('/api/v2/groups', params)
  }

  get(id) {
    return this.$get(`/api/v2/groups/${id}`)
  }

  create(payload) {
    return this.$post('/api/v2/groups', payload)
  }

  update(id, payload) {
    return this.$patch(`/api/v2/groups/${id}`, payload)
  }
}
