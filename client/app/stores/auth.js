import { defineStore } from 'pinia'
import { useSessionStore } from './session.js'

/**
 * Auth token + user summary. Only the token is persisted (see
 * pinia-plugin-persistedstate config below) - the user summary is
 * refetched from /api/v2/session on load (design.md §4.4).
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null,
    user: null,
  }),

  getters: {
    loggedIn: (state) => !!state.token,
  },

  actions: {
    async login(credentials) {
      const { $api } = useNuxtApp()
      const { data } = await $api.auth.login(credentials)

      this.token = data.token
      this.user = data.user

      // The navbar and role gates render from the session store; refresh it
      // now that requests carry the new token.
      await useSessionStore().fetch()

      return data
    },

    async register(payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.auth.register(payload)

      this.token = data.token
      this.user = data.user

      await useSessionStore().fetch()

      return data
    },

    async logout() {
      const { $api } = useNuxtApp()

      if (this.token) {
        try {
          await $api.auth.logout()
        } catch {
          // Already logged out server-side (e.g. expired token) - fine,
          // we're clearing local state regardless.
        }
      }

      this.clear()
    },

    async fetchSession() {
      const { $api } = useNuxtApp()
      const { data } = await $api.session.fetch()

      this.user = data.user

      return data
    },

    forgotPassword(payload) {
      const { $api } = useNuxtApp()
      return $api.auth.forgotPassword(payload)
    },

    resetPassword(payload) {
      const { $api } = useNuxtApp()
      return $api.auth.resetPassword(payload)
    },

    // Clears local auth state without calling the API. Used both by
    // logout() and by the API plugin when a /session call comes back 401
    // (design.md §4.4: only /session's 401 means "log me out").
    clear() {
      this.token = null
      this.user = null

      // Downgrade the session context to a guest view. Config/flags keep
      // their last values (they are not user-specific).
      useSessionStore().user = null
    },
  },

  persist: {
    pick: ['token'],
  },
})
