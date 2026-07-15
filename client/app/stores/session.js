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

    // Dismisses the onboarding modal (design.md §6.2 task brief: session
    // flags.onboarding). Flips the flag immediately so the modal closes
    // regardless of the API call's outcome - the dismiss endpoint is a
    // recorded gap (docs/nuxt-migration/api-gaps.md) and does not exist
    // server-side yet, so a failure here must not trap the user behind the
    // modal.
    async dismissOnboarding() {
      if (this.flags) {
        this.flags.onboarding = false
      }

      const { $api } = useNuxtApp()

      try {
        await $api.user.dismissOnboarding()
      } catch {
        // Best-effort - see comment above.
      }
    },
  },
})
