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
// Live shows the user's 10 most recent notifications in the navbar panel.
export const PANEL_SIZE = 10

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    restarters: null,
    discourse: null,
    loading: false,
    error: null,
    // The /notifications page: the paginated in-app notification list.
    list: { items: [], page: 1, lastPage: 1, total: 0, unread: 0, loading: false, error: null },
    // The navbar bell's dropdown: the latest PANEL_SIZE notifications. Kept
    // apart from `list` so opening the panel never disturbs the page the
    // /notifications page is on.
    panel: { items: [], loading: false, error: null },
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

    // Load the latest PANEL_SIZE notifications for the navbar panel (the API
    // pages at 20, newest first, so the first page is enough).
    async fetchPanel() {
      const { $api } = useNuxtApp()
      this.panel.loading = true

      try {
        const res = await $api.user.myNotifications({ page: 1 })
        this.panel.items = res.data.slice(0, PANEL_SIZE)
        this.panel.error = null
      } catch (error) {
        this.panel.error = error
      } finally {
        this.panel.loading = false
      }
    },

    // Mark one notification (by id) or all (id omitted) as read, then reflect
    // the change locally and refresh the navbar badge count.
    async markRead(id = null) {
      const { $api } = useNuxtApp()
      const res = await $api.user.markNotificationsRead(id)

      const mark = (n) => (id === null || n.id === id ? { ...n, read: true } : n)
      this.list.items = this.list.items.map(mark)
      this.panel.items = this.panel.items.map(mark)
      this.list.unread = res.data.unread
      this.restarters = res.data.unread
      return res.data
    },
  },
})
