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

      // Resolved, but with no user - a token that no longer maps to an
      // account (common on preview apps, whose DB is periodically reset).
      // Without this the token stays and loggedIn (=!!token) remains true, so
      // the auth guard lets the dead session onto /dashboard, which then 401s
      // and shows an error while the navbar - reading the null user - already
      // says logged out.
      if (!session.user) {
        auth.clear()
      }
    } catch {
      // Any failure establishing who we are means we are not logged in. Only a
      // /session 401 clears auth via BaseAPI; a 500 (or the token mapping to a
      // deleted user) does not, and would otherwise leave the token in place
      // with loggedIn true. Clear it here so the whole app - guard, navbar,
      // guest pages - treats it consistently as a guest.
      auth.clear()
    }
  }
})
