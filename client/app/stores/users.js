import { defineStore } from 'pinia'

/**
 * Backs two Phase D task-D2/D3 pages that both deal with *other* users'
 * data (as opposed to stores/profile.js, which is the authenticated user's
 * own editable profile plus the two id-scoped admin/repair-directory
 * families):
 *
 *  - /user/all (design.md §6.2 Phase D task D3; PR #866's UsersPage.vue +
 *    resources/views/user/all.blade.php are the functional spec).
 *    GET /api/v2/users (UserController::listUsersv2, Administrator-only,
 *    already implemented server-side) does its own filtering/sorting/
 *    pagination - unlike stores/groups.js's client-side All Groups tab,
 *    `fetchList` is a thin pass-through: the page owns filter/sort/page
 *    state and calls it with whatever query params it wants; the store just
 *    tracks the last response + loading/error. The legacy page's
 *    "Create new user" modal (`includes/modals/create-user`, posting to the
 *    legacy web route `/user/create`) is deliberately not ported - there is
 *    no v2 API for it (confirmed by reading routes/api.php: no POST under
 *    `/users`), and building one is a separate feature, not part of this
 *    slice's brief.
 *
 *  - /profile/[id] and /profile (design.md §6.2 Phase D task D2;
 *    resources/views/user/profile-new.blade.php is the functional spec).
 *    GET /api/v2/users/{id} does NOT exist yet (confirmed by reading
 *    routes/api.php directly - see docs/nuxt-migration/api-gaps.md Phase D
 *    for the documented shape this is built against).
 *    `fetchPublicProfile` is written against that shape and, unlike
 *    stores/profile.js#fetchTargetUser (which swallows the failure for a
 *    best-effort admin-edit heading), surfaces it - a real not-found/error
 *    state is the whole point of this page.
 */
export const useUsersStore = defineStore('users', {
  state: () => ({
    list: {
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 30, total: 0, from: 0, to: 0 },
      loading: false,
      error: null,
    },

    roles: { data: [], loading: false, error: null },

    // One profile at a time (like stores/groups.js's `current`) - keyed by
    // targetId so switching between /profile/[id] routes re-fetches rather
    // than showing stale data while the new one loads.
    publicProfile: { targetId: null, data: null, loading: false, error: null },
  }),

  actions: {
    async fetchList(params) {
      const { $api } = useNuxtApp()

      this.list.loading = true
      this.list.error = null

      try {
        const { data, meta } = await $api.user.list(params)
        this.list.data = data
        this.list.meta = meta
        return data
      } catch (error) {
        this.list.error = error
        throw error
      } finally {
        this.list.loading = false
      }
    },

    // GET /api/v2/roles (already implemented server-side) - backs the
    // role filter dropdown. Not re-fetched per filter change: the role list
    // itself doesn't change while the page is open.
    async fetchRoles() {
      const { $api } = useNuxtApp()

      this.roles.loading = true
      this.roles.error = null

      try {
        const { data } = await $api.role.list()
        this.roles.data = data
        return data
      } catch (error) {
        this.roles.error = error
        throw error
      } finally {
        this.roles.loading = false
      }
    },

    async fetchPublicProfile(id) {
      const { $api } = useNuxtApp()

      this.publicProfile.targetId = id
      this.publicProfile.loading = true
      this.publicProfile.error = null

      try {
        const { data } = await $api.user.get(id)
        this.publicProfile.data = data
        return data
      } catch (error) {
        this.publicProfile.error = error
        this.publicProfile.data = null
        throw error
      } finally {
        this.publicProfile.loading = false
      }
    },
  },
})
