import { defineStore } from 'pinia'
import { useAuthStore } from './auth.js'

/**
 * GET /api/v2/session - the single client-bootstrap call (design.md §4.4 /
 * §6): user, server config and feature flags in one shape.
 */
export const useSessionStore = defineStore('session', {
  state: () => ({
    user: null,
    config: null,
    flags: null,
    loaded: false,
  }),

  actions: {
    async fetch() {
      const { $api } = useNuxtApp()
      const { data } = await $api.session.fetch()

      this.user = data.user
      this.config = data.config
      this.flags = data.flags
      this.loaded = true

      // Keep the auth store's user summary in step with the session's
      // authoritative view (design.md §4.4).
      const authStore = useAuthStore()
      authStore.user = data.user

      return data
    },
  },
})
