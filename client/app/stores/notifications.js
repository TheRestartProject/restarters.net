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
    // The /notifications page: the paginated in-app notification list.
    list: { items: [], page: 1, lastPage: 1, total: 0, unread: 0, loading: false, error: null },
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

    async fetchList(page = 1) {
      const { $api } = useNuxtApp()
      this.list.loading = true
      this.list.error = null

      try {
        const res = await $api.user.myNotifications({ page })
        this.list.items = res.data
        this.list.page = res.meta.current_page
        this.list.lastPage = res.meta.last_page
        this.list.total = res.meta.total
        this.list.unread = res.meta.unread
      } catch (error) {
        this.list.error = error
        throw error
      } finally {
        this.list.loading = false
      }
    },

    // Mark one notification (by id) or all (id omitted) as read, then reflect
    // the change locally and refresh the navbar badge count.
    async markRead(id = null) {
      const { $api } = useNuxtApp()
      const res = await $api.user.markNotificationsRead(id)

      this.list.items = this.list.items.map((n) => (id === null || n.id === id ? { ...n, read: true } : n))
      this.list.unread = res.data.unread
      this.restarters = res.data.unread
      return res.data
    },
  },
})
