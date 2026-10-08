import { defineStore } from 'pinia'

/**
 * GET /api/v2/dashboard (api-contracts-phase-b.md B1): your groups, nearby
 * groups, newly-added nearby groups and upcoming events for the /dashboard
 * page.
 */
export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    data: null,
    loading: false,
    error: null,
  }),

  actions: {
    async fetch() {
      const { $api } = useNuxtApp()

      this.loading = true
      this.error = null

      try {
        const { data } = await $api.dashboard.fetch()
        this.data = data
        return data
      } catch (error) {
        this.error = error
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
