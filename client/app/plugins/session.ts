import { useAuthStore } from '~/stores/auth.js'
import { useSessionStore } from '~/stores/session.js'

// Cold-boot session bootstrap: the persisted auth token survives a full page
// load, but the user/config context does not — without this, a reload showed
// a logged-out navbar and role-gated sections never rendered until the next
// explicit login. Awaited deliberately (one API round-trip before first
// paint) so the auth middleware's role/consent checks and the navbar always
// see the real user; a 401 here is handled by the API layer (clears auth for
// /session only).
//
// Guests fetch it too: it says which network's site this is
// (repairtogether.restarters.net etc.), for the logo, the newsletter sign-up
// and the default language.
export default defineNuxtPlugin(async () => {
  const auth = useAuthStore()
  const session = useSessionStore()

  if (session.loaded) {
    return
  }

  try {
    await session.fetch()

    // Resolved, but with no user - a token that no longer maps to an
    // account (common on preview apps, whose DB is periodically reset).
    // Without this the token stays and loggedIn (=!!token) remains true, so
    // the auth guard lets the dead session onto /dashboard, which then 401s
    // and shows an error while the navbar - reading the null user - already
    // says logged out.
    if (auth.token && !session.user) {
      auth.clear()
    }
  } catch {
    // Any failure establishing who we are means we are not logged in. Only a
    // /session 401 clears auth via BaseAPI; a 500 (or the token mapping to a
    // deleted user) does not, and would otherwise leave the token in place
    // with loggedIn true. Clear it here so the whole app - guard, navbar,
    // guest pages - treats it consistently as a guest.
    auth.clear()
    return
  }

  await applyNetworkLanguage(session.site)
})

// Live's CheckForRepairNetwork: on a network's own site, a visitor who hasn't
// chosen a language gets the network's language rather than their browser's.
async function applyNetworkLanguage(site: { shortname?: string, default_language?: string } | null) {
  const language = site?.default_language

  if (!language || site?.shortname === 'restarters' || useState('localeChosen').value) {
    return
  }

  const { $i18n } = useNuxtApp()

  if ($i18n.localeCodes.value.includes(language)) {
    await $i18n.setLocale(language)
  }
}
