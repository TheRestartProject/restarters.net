// Whether a login `redirect` is Talk's sign-on: Discourse asks the API's
// /discourse/sso, which sends a browser with no Laravel web session to
// /login?redirect=<that URL> (AuthenticateRedirectingToFrontend). The SPA's
// token login makes no web session, so the way back is the SSO bridge
// (useSsoBridge). Any other absolute URL is not ours to send anyone to.
export function isTalkSignOn(redirect) {
  if (typeof redirect !== 'string' || !/^https?:\/\//i.test(redirect)) {
    return false
  }

  try {
    const url = new URL(redirect)
    const api = new URL(useRuntimeConfig().public.apiBase)

    return url.origin === api.origin && url.pathname === '/discourse/sso'
  } catch {
    return false
  }
}
