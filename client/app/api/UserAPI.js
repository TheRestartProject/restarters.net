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

  // GET /api/v2/users/me/groups (UserController::getMyGroupsv2 - already
  // implemented server-side on this branch, confirmed by reading
  // routes/api.php + the controller directly). {data:[{id, name, role,
  // archived, image_url}]}. Backs the event create/edit/duplicate group
  // picker (api-contracts-phase-c.md C4 task brief): `role` is the
  // app/Role.php int on the `users_groups` pivot (HOST=3 is
  // User::groupsInChargeOf()'s base case - "groups I'm in charge of").
  // Not yet adopted by stores/groups.js#fetchMine() (that B4 code predates
  // this endpoint landing and still falls back to the dashboard's
  // your_groups - see docs/nuxt-migration/api-gaps.md); left alone here,
  // out of scope for this slice.
  myGroups() {
    return this.$get('/api/v2/users/me/groups')
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
