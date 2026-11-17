import { expect, vi } from 'vitest'

// For a test that clicks a Talk/Wiki link: stubs the SSO bridge's ticket call
// and window.location, so the test can see where the bridge was asked to send
// the user once it has logged them in. Call restore() when done.
export function stubSsoBridge() {
  const previousNuxtApp = globalThis.useNuxtApp
  const previousLocation = window.location

  const ssoTicket = vi.fn().mockResolvedValue({
    data: { ticket: 'tok', bridge_url: 'http://api.test/auth/bridge' },
  })
  vi.stubGlobal('useNuxtApp', () => ({ $api: { auth: { ssoTicket } } }))

  delete window.location
  window.location = { href: '' }

  return {
    ssoTicket,
    // The `redirect` the bridge was given, once the click has navigated.
    async redirect() {
      await vi.waitFor(() => expect(window.location.href).toContain('http://api.test/auth/bridge'))
      return new URL(window.location.href).searchParams.get('redirect')
    },
    restore() {
      vi.stubGlobal('useNuxtApp', previousNuxtApp)
      window.location = previousLocation
    },
  }
}

// What a Talk link should be sent through the bridge as: Talk's SSO endpoint,
// returning to `target` (#966).
export function talkSso(base, target) {
  return `${base}/session/sso?return_path=${encodeURIComponent(target)}`
}
