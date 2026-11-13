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
 * unauthenticated, since no cookie has ever been set for them.
 */
export function useSsoBridge() {
  async function goTo(redirect) {
    if (!redirect) {
      return
    }

    const { $api } = useNuxtApp()

    try {
      const { data } = await $api.auth.ssoTicket()
      const url = new URL(data.bridge_url)
      url.searchParams.set('ticket', data.ticket)
      url.searchParams.set('redirect', redirect)
      window.location.href = url.toString()
    } catch {
      // Best-effort: land on the target directly rather than trap the user
      // behind a broken link - they'll just arrive unauthenticated, same as
      // clicking a plain link ever did before the bridge existed.
      window.location.href = redirect
    }
  }

  return { goTo }
}
