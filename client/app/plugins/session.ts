import { useAuthStore } from '~/stores/auth.js'
import { useSessionStore } from '~/stores/session.js'

// Cold-boot session bootstrap: the persisted auth token survives a full page
// load, but the user/config context does not — without this, a reload showed
// a logged-out navbar and role-gated sections never rendered until the next
// explicit login. Awaited deliberately (one API round-trip before first
// paint) so the auth middleware's role/consent checks and the navbar always
// see the real user; a 401 here is handled by the API layer (clears auth for
// /session only).
export default defineNuxtPlugin(async () => {
  const auth = useAuthStore()
  const session = useSessionStore()

  if (auth.token && !session.loaded) {
    try {
      await session.fetch()
    } catch {
      // Invalid/expired token: BaseAPI's 401-on-/session handling has already
      // cleared auth state; the app proceeds as a guest.
    }
  }
})
