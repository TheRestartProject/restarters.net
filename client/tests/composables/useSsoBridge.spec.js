import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useSsoBridge } from '../../app/composables/useSsoBridge.js'

describe('composables/useSsoBridge', () => {
  let mockApi
  let originalHref

  beforeEach(() => {
    mockApi = {
      auth: {
        ssoTicket: vi.fn(),
      },
    }
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))

    // happy-dom's window.location isn't directly assignable; redefine it
    // per-test like other suites in this codebase that need to observe a
    // top-level navigation.
    originalHref = window.location.href
    delete window.location
    window.location = { href: '' }
  })

  afterEach(() => {
    window.location = { href: originalHref }
  })

  it('goTo() is a no-op for an empty redirect', async () => {
    const { goTo } = useSsoBridge()
    await goTo('')

    expect(mockApi.auth.ssoTicket).not.toHaveBeenCalled()
    expect(window.location.href).toBe('')
  })

  it('goTo() mints a ticket and navigates to the bridge URL carrying ticket + redirect', async () => {
    mockApi.auth.ssoTicket.mockResolvedValueOnce({
      data: { ticket: 'tok-abc', bridge_url: 'http://localhost:8001/auth/bridge' },
    })

    const { goTo } = useSsoBridge()
    await goTo('https://talk.example.com/session/sso?return_path=https://talk.example.com')

    expect(window.location.href).toBe(
      'http://localhost:8001/auth/bridge?ticket=tok-abc&redirect=' +
        encodeURIComponent('https://talk.example.com/session/sso?return_path=https://talk.example.com')
    )
  })

  it('goTo() falls back to navigating directly to the redirect target if the ticket call fails', async () => {
    mockApi.auth.ssoTicket.mockRejectedValueOnce({ status: 401 })

    const { goTo } = useSsoBridge()
    await goTo('https://wiki.example.com')

    expect(window.location.href).toBe('https://wiki.example.com')
  })
})
