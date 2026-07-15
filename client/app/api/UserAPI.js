import BaseAPI from './BaseAPI.js'

/**
 * Thin, hardcoded-path client for the user endpoint family. Full surface
 * (users/me/* profile tabs from PR #868, admin list from PR #866...) lands
 * with the Profile/Admin migration slices (design.md §6.2); this is the
 * scaffold-stage shape.
 */
export default class UserAPI extends BaseAPI {
  get(id) {
    return this.$get(`/api/v2/users/${id}`)
  }

  me() {
    return this.$get('/api/v2/users/me')
  }

  update(id, payload) {
    return this.$patch(`/api/v2/users/${id}`, payload)
  }
}
