import { defineStore } from 'pinia'

let nextId = 1

/**
 * Minimal toast queue for API-error surfacing (design.md §6 composables/
 * useToast()). Deliberately not tied to bootstrap-vue-next's own useToast()
 * composable (which requires a live <BApp> setup() context and is awkward to
 * unit test) - a plain Pinia store keeps the "what to show" logic trivially
 * testable, and a small ToastContainer component (built alongside the navbar
 * shell) can render `toasts` through BToast/BApp later.
 */
export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),

  actions: {
    push({ message, variant = 'info', timeout = 5000 }) {
      const id = nextId++
      this.toasts.push({ id, message, variant, timeout })
      return id
    },

    // Surfaces an APIError (or any {message}-shaped error, or a plain
    // string) as a danger toast. Falls back to a generic message so a
    // network failure / unexpected shape never renders "undefined".
    error(errorOrMessage) {
      const message =
        (typeof errorOrMessage === 'string' && errorOrMessage) ||
        errorOrMessage?.data?.message ||
        errorOrMessage?.message ||
        'Something went wrong. Please try again.'

      return this.push({ message, variant: 'danger' })
    },

    success(message) {
      return this.push({ message, variant: 'success' })
    },

    dismiss(id) {
      this.toasts = this.toasts.filter((toast) => toast.id !== id)
    },

    clear() {
      this.toasts = []
    },
  },
})
