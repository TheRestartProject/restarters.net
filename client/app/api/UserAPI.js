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

  // Dismisses the onboarding modal server-side (mirrors legacy GET
  // /user/onboarding-complete, which bumped number_of_logins so
  // flags.onboarding false's out - see SessionController@indexv2). Endpoint
  // does not exist yet - recorded as a gap in docs/nuxt-migration/api-gaps.md;
  // the dashboard store swallows failures so the modal still dismisses
  // locally for the session.
  dismissOnboarding() {
    return this.$post('/api/v2/users/me/onboarding-complete')
  }
}
