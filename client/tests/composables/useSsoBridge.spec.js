import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useSsoBridge } from '../../app/composables/useSsoBridge.js'
import { useSessionStore } from '../../app/stores/session.js'

describe('composables/useSsoBridge', () => {
  let mockApi
  let originalHref

  beforeEach(() => {
    setActivePinia(createPinia())
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

  describe('opening in a new tab', () => {
    let openSpy

    afterEach(() => {
      openSpy.mockRestore()
    })

    it('opens the tab before awaiting the ticket, cuts its opener, and sends the tab (not this one) through the bridge', async () => {
      const tab = { opener: window, location: { href: '' } }
      openSpy = vi.spyOn(window, 'open').mockReturnValue(tab)
      mockApi.auth.ssoTicket.mockResolvedValueOnce({
        data: { ticket: 'tok-abc', bridge_url: 'http://localhost:8001/auth/bridge' },
      })

      const { goTo } = useSsoBridge()
      const going = goTo('https://talk.example.com/session/sso?return_path=x', { newTab: true })

      // Synchronously, inside the click - before the ticket call resolves.
      expect(openSpy).toHaveBeenCalledWith('', '_blank')
      await going

      expect(tab.opener).toBe(null)
      expect(tab.location.href).toBe(
        'http://localhost:8001/auth/bridge?ticket=tok-abc&redirect=' +
          encodeURIComponent('https://talk.example.com/session/sso?return_path=x')
      )
      expect(window.location.href).toBe('')
    })

    it('falls back to this tab when the new one is blocked', async () => {
      openSpy = vi.spyOn(window, 'open').mockReturnValue(null)
      mockApi.auth.ssoTicket.mockResolvedValueOnce({
        data: { ticket: 'tok-abc', bridge_url: 'http://localhost:8001/auth/bridge' },
      })

      const { goTo } = useSsoBridge()
      await goTo('https://talk.example.com', { newTab: true })

      expect(window.location.href).toBe(
        'http://localhost:8001/auth/bridge?ticket=tok-abc&redirect=' + encodeURIComponent('https://talk.example.com')
      )
    })
  })

  // #966: Talk only logs someone in when they arrive via its /session/sso
  // endpoint; a direct link to a page needing an account shows "Access Denied".
  describe('goToTalk()', () => {
    beforeEach(() => {
      useSessionStore().config = { discourse_url: 'https://talk.example.com' }
      mockApi.auth.ssoTicket.mockResolvedValue({
        data: { ticket: 'tok-abc', bridge_url: 'http://localhost:8001/auth/bridge' },
      })
    })

    function redirect() {
      return new URL(window.location.href).searchParams.get('redirect')
    }

    it('sends a Talk page through Talk\'s SSO, returning to that page', async () => {
      const { goToTalk } = useSsoBridge()
      await goToTalk('https://talk.example.com/t/meet-and-greet/44')

      expect(redirect()).toBe(
        'https://talk.example.com/session/sso?return_path=' + encodeURIComponent('https://talk.example.com/t/meet-and-greet/44')
      )
    })

    it('uses a link that is already a /session/sso one as it is', async () => {
      const sso = 'https://talk.example.com/session/sso?return_path=https://talk.example.com/t/kit/324'
      const { goToTalk } = useSsoBridge()
      await goToTalk(sso)

      expect(redirect()).toBe(sso)
    })

    it('is a no-op for an empty url', async () => {
      const { goToTalk } = useSsoBridge()
      await goToTalk(null)

      expect(mockApi.auth.ssoTicket).not.toHaveBeenCalled()
      expect(window.location.href).toBe('')
    })
  })

  describe('followTalkLink()', () => {
    let openSpy
    let tab

    beforeEach(() => {
      useSessionStore().config = { discourse_url: 'https://talk.example.com' }
      mockApi.auth.ssoTicket.mockResolvedValue({
        data: { ticket: 'tok-abc', bridge_url: 'http://localhost:8001/auth/bridge' },
      })
      tab = { opener: window, location: { href: '' } }
      openSpy = vi.spyOn(window, 'open').mockReturnValue(tab)
    })

    afterEach(() => {
      openSpy.mockRestore()
    })

    function click(modifiers = {}, target = '') {
      const link = document.createElement('a')
      link.href = 'https://talk.example.com/t/kit/324'
      link.target = target
      const event = new MouseEvent('click', { cancelable: true, ...modifiers })
      Object.defineProperty(event, 'target', { value: link })
      return event
    }

    it('stops the plain link and goes through Talk\'s SSO in this tab', async () => {
      const event = click()
      const { followTalkLink } = useSsoBridge()
      await followTalkLink(event, 'https://talk.example.com/t/kit/324')

      expect(event.defaultPrevented).toBe(true)
      expect(openSpy).not.toHaveBeenCalled()
      expect(new URL(window.location.href).searchParams.get('redirect')).toBe(
        'https://talk.example.com/session/sso?return_path=' + encodeURIComponent('https://talk.example.com/t/kit/324')
      )
    })

    it.each([
      ['Ctrl-click', { ctrlKey: true }, ''],
      ['Cmd-click', { metaKey: true }, ''],
      ['Shift-click', { shiftKey: true }, ''],
      ['a target="_blank" link', {}, '_blank'],
    ])('opens a new tab for %s, as a plain link would', async (_, modifiers, target) => {
      const { followTalkLink } = useSsoBridge()
      await followTalkLink(click(modifiers, target), 'https://talk.example.com/t/kit/324')

      expect(openSpy).toHaveBeenCalledWith('', '_blank')
      expect(new URL(tab.location.href).searchParams.get('redirect')).toBe(
        'https://talk.example.com/session/sso?return_path=' + encodeURIComponent('https://talk.example.com/t/kit/324')
      )
      expect(window.location.href).toBe('')
    })

    it('finds the link when the click lands on something inside it', async () => {
      const link = document.createElement('a')
      link.href = 'https://talk.example.com/t/kit/324'
      link.target = '_blank'
      const badge = document.createElement('span')
      link.appendChild(badge)
      const event = new MouseEvent('click', { cancelable: true })
      Object.defineProperty(event, 'target', { value: badge })

      const { followTalkLink } = useSsoBridge()
      await followTalkLink(event)

      expect(openSpy).toHaveBeenCalledWith('', '_blank')
      expect(new URL(tab.location.href).searchParams.get('redirect')).toBe(
        'https://talk.example.com/session/sso?return_path=' + encodeURIComponent('https://talk.example.com/t/kit/324')
      )
    })

    it('defaults to the clicked link\'s own href', async () => {
      const { followTalkLink } = useSsoBridge()
      await followTalkLink(click())

      expect(new URL(window.location.href).searchParams.get('redirect')).toBe(
        'https://talk.example.com/session/sso?return_path=' + encodeURIComponent('https://talk.example.com/t/kit/324')
      )
    })
  })

  describe('isTalkUrl()', () => {
    it('recognises the configured Talk origin and pages under it', () => {
      useSessionStore().config = { discourse_url: 'https://talk.example.com' }
      const { isTalkUrl } = useSsoBridge()

      expect(isTalkUrl('https://talk.example.com')).toBe(true)
      expect(isTalkUrl('https://talk.example.com/t/kit/324')).toBe(true)
      expect(isTalkUrl('https://talk.example.com/session/sso?return_path=x')).toBe(true)
    })

    it('rejects other sites, including lookalike hosts', () => {
      useSessionStore().config = { discourse_url: 'https://talk.example.com' }
      const { isTalkUrl } = useSsoBridge()

      expect(isTalkUrl('https://talk.example.com.evil.test/t/1')).toBe(false)
      expect(isTalkUrl('https://wiki.example.com/Main_Page')).toBe(false)
      expect(isTalkUrl('')).toBe(false)
    })

    it('copes with a configured Talk URL that ends in a slash', () => {
      useSessionStore().config = { discourse_url: 'https://talk.example.com/' }
      const { isTalkUrl } = useSsoBridge()

      expect(isTalkUrl('https://talk.example.com/t/kit/324')).toBe(true)
    })

    it('is false for everything when no Talk URL is configured', () => {
      const { isTalkUrl } = useSsoBridge()

      expect(isTalkUrl('https://talk.example.com/t/1')).toBe(false)
    })
  })
})
