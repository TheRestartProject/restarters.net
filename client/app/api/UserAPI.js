import BaseAPI from './BaseAPI.js'

/**
 * Thin, hardcoded-path client for the user endpoint family. Full surface
 * (users/me/* profile tabs from PR #868, admin list from PR #866...) lands
 * with the Profile/Admin migration slices (design.md §6.2); this is the
 * scaffold-stage shape.
 */
export default class UserAPI extends BaseAPI {
  // NOT YET IMPLEMENTED server-side (confirmed by reading routes/api.php
  // directly - no `Route::get('{id}', ...)` under `/users`) - see
  // docs/nuxt-migration/api-gaps.md Phase D. Two callers, two different
  // needs against the same documented shape: stores/profile.js#fetchTargetUser
  // (D1, best-effort - only reads `.name`, swallows failure) and
  // stores/users.js#fetchPublicProfile (D2's /profile/[id] public page -
  // reads the full {id, name, avatar_url, role_name, location, groups,
  // skills, biography} resource and surfaces a real not-found/error state).
  get(id) {
    return this.$get(`/api/v2/users/${id}`)
  }

  me() {
    return this.$get('/api/v2/users/me')
  }

  // GET /api/v2/users (UserController::listUsersv2, Administrator-only,
  // already implemented server-side - design.md §6.2 Phase D task D3, PR
  // #866's UsersPage.vue is the functional spec). params: name/email/
  // location/country/role filters, sort/sortdir, page - all optional,
  // passed straight through as query params. Response:
  // {data: UserAdmin[], meta: {current_page, last_page, per_page, total,
  // from, to}}.
  list(params) {
    return this.$get('/api/v2/users', params)
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
  // flags.onboarding false's out - see SessionController@indexv2 and
  // UserController::onboardingCompletev2, both already implemented). The
  // session store swallows failures so the modal still dismisses locally
  // for the session even if this call fails.
  dismissOnboarding() {
    return this.$post('/api/v2/users/me/onboarding-complete')
  }

  // GET /api/users/{id}/notifications (v1, NOT under /api/v2 - see
  // routes/api.php's comment on this route about it being unauthenticated
  // by id: "issue with exposing the number of outstanding notifications" is
  // a pre-existing, acknowledged issue, not something this port
  // introduces). UserController::notifications - unlike every v2 endpoint,
  // the response has no `{data: ...}` envelope: it's the legacy flat shape
  // `{success: 'success', restarters: <int>, discourse: <int>}` straight
  // off resources/js/components/Notifications.vue's axios call, kept as-is
  // rather than "fixed" to match v2 conventions since this route isn't v2.
  notifications(id) {
    return this.$get(`/api/users/${id}/notifications`)
  }

  // GET /api/v2/users/me/notifications - paginated list of the user's in-app
  // (Restarters) notifications (UserController::getMyNotificationsv2),
  // backing the /notifications page.
  myNotifications(params) {
    return this.$get('/api/v2/users/me/notifications', params)
  }

  // POST /api/v2/users/me/notifications/read - mark one (by id) or all
  // notifications as read.
  markNotificationsRead(id) {
    return this.$post('/api/v2/users/me/notifications/read', id ? { id } : {})
  }

  // users/me/* family backing /profile/edit/[[id]] (design.md §6.2 Phase D,
  // PR #868's UserController - all already implemented server-side, see
  // app/Http/Controllers/API/UserController.php). Every one of these always
  // operates on Auth::user() - there is no id parameter on any of them -
  // see the deliberate "me-only tabs are self-only, even from another
  // user's edit URL" note in stores/profile.js.
  getEmailPreferences() {
    return this.$get('/api/v2/users/me/preferences')
  }

  updateEmailPreferences(payload) {
    return this.$patch('/api/v2/users/me/preferences', payload)
  }

  getCalendars() {
    return this.$get('/api/v2/users/me/calendars')
  }

  getLanguage() {
    return this.$get('/api/v2/users/me/language')
  }

  updateLanguage(payload) {
    return this.$patch('/api/v2/users/me/language', payload)
  }

  getProfileInfo() {
    return this.$get('/api/v2/users/me/profile')
  }

  updateProfileInfo(payload) {
    return this.$patch('/api/v2/users/me/profile', payload)
  }

  getSkills() {
    return this.$get('/api/v2/users/me/skills')
  }

  updateSkills(payload) {
    return this.$patch('/api/v2/users/me/skills', payload)
  }

  updatePassword(payload) {
    return this.$patch('/api/v2/users/me/password', payload)
  }

  // Attaches a completed tus upload (see TusImageUpload.vue) as the
  // authenticated user's profile photo.
  updatePhoto(uploadKey) {
    return this.$post('/api/v2/users/me/photo', { upload_key: uploadKey })
  }

  deleteAccount() {
    return this.$del('/api/v2/users/me')
  }

  // id-scoped family: these DO take the target user's id, and are safe to
  // call while editing someone else's profile (policy-gated server-side).
  getRepairDirectoryOptions(id) {
    return this.$get(`/api/v2/users/${id}/repair-directory-options`)
  }

  updateRepairDirectoryRole(id, role) {
    return this.$patch(`/api/v2/users/${id}/repair-directory-role`, { role })
  }

  getAdminSettings(id) {
    return this.$get(`/api/v2/users/${id}/admin-settings`)
  }

  updateAdminSettings(id, payload) {
    return this.$patch(`/api/v2/users/${id}/admin-settings`, payload)
  }
}
