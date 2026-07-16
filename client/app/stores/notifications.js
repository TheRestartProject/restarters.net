import { defineStore } from 'pinia'
import { useAuthStore } from './auth.js'

/**
 * Restarters + Discourse unread-notification counts for the navbar badge
 * (resources/js/components/Notifications.vue is the functional spec: it
 * polls GET /api/users/{id}/notifications - v1, unauthenticated-by-id, no
 * `{data: ...}` envelope, see api/UserAPI.js#notifications - and shows two
 * counts). Best-effort throughout: a failed poll leaves the previous counts
 * on screen rather than surfacing an error UI (there is nothing useful to
 * show the user about a background badge failing to refresh).
 */
export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    restarters: null,
    discourse: null,
    loading: false,
    error: null,
  }),

  actions: {
    async fetch() {
      const authStore = useAuthStore()
      const userId = authStore.user?.id

      if (!userId) {
        return
      }

      const { $api } = useNuxtApp()

      this.loading = true

      try {
        const result = await $api.user.notifications(userId)
        this.restarters = result.restarters
        this.discourse = result.discourse
        this.error = null
      } catch (error) {
        // Best-effort - see class doc comment. Deliberately not rethrown:
        // a background poll failing must not turn into an unhandled
        // rejection every 60s.
        this.error = error
      } finally {
        this.loading = false
      }
    },
  },
})
