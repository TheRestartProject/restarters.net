import { useSessionStore } from '~/stores/session.js'

/**
 * Discourse/Wiki link-outs from the Nuxt SPA (design.md §4.3): the
 * token-authenticated SPA carries no Laravel web session, but Discourse SSO
 * (GET /discourse/sso) and MediaWiki's cookie listener both need one for a
 * top-level browser navigation. `POST /api/v2/auth/sso-ticket` issues a
 * one-time, 60-second ticket; the browser is then sent top-level to the
 * returned `bridge_url` (GET /auth/bridge), which consumes the ticket,
 * establishes the web session (firing Login -> LogInToWiki, so wiki cookies
 * land first-party) and 302s on to `redirect` - which must be on the
 * server's allowlist (BridgeController::safeRedirect: /discourse/sso, the
 * wiki base URL, the Discourse URL itself, or FRONTEND_URL).
 *
 * Every Talk/Wiki link-out in the app (AppNavbar, AppFooter, the
 * notifications badge's Discourse link) should go through this rather than
 * a plain <a href> - a plain href would land the user on Discourse/the wiki
 * unauthenticated, since no cookie has ever been set for them. That includes
 * links already in Discourse's /session/sso form: Discourse then asks
 * Laravel, which has no web session for the SPA user until the bridge makes
 * one. goToTalk() is the shorthand for any Talk page.
 */
export function useSsoBridge() {
  // `newTab` opens the destination in a new tab, for links that would have
  // had target="_blank".
  async function goTo(redirect, { newTab = false } = {}) {
    if (!redirect) {
      return
    }

    // Open the tab now, while still inside the click: browsers block
    // window.open() once we've awaited the ticket. If it's blocked anyway,
    // fall back to this tab rather than going nowhere.
    let target = window
    if (newTab) {
      const tab = window.open('', '_blank')
      if (tab) {
        tab.opener = null
        target = tab
      }
    }

    const { $api } = useNuxtApp()

    try {
      const { data } = await $api.auth.ssoTicket()
      const url = new URL(data.bridge_url)
      url.searchParams.set('ticket', data.ticket)
      url.searchParams.set('redirect', redirect)
      target.location.href = url.toString()
    } catch {
      // Best-effort: land on the target directly rather than trap the user
      // behind a broken link - they'll just arrive unauthenticated, same as
      // clicking a plain link ever did before the bridge existed.
      target.location.href = redirect
    }
  }

  function talkBase() {
    return (useSessionStore().config?.discourse_url || '').replace(/\/+$/, '')
  }

  // Whether `url` is on Talk (the session's configured Discourse).
  function isTalkUrl(url) {
    const base = talkBase()
    return !!base && !!url && (url === base || url.startsWith(`${base}/`) || url.startsWith(`${base}?`))
  }

  // Talk only logs someone in when they arrive through its /session/sso
  // endpoint: a direct link to a topic, group or preferences page that needs
  // an account shows "Access Denied" instead (#966). So send the user via
  // that endpoint, which then returns them to `url`. A `url` that is already
  // a /session/sso link is used as it is.
  function goToTalk(url, options) {
    const base = talkBase()

    if (!base || !url || url.startsWith(`${base}/session/sso`)) {
      return goTo(url, options)
    }

    return goTo(`${base}/session/sso?return_path=${encodeURIComponent(url)}`, options)
  }

  return { goTo, goToTalk, isTalkUrl }
}
