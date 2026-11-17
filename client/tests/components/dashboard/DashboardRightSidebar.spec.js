import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import DashboardRightSidebar from '../../../app/components/dashboard/DashboardRightSidebar.vue'
import { useSessionStore } from '../../../app/stores/session.js'
import { stubSsoBridge, talkSso } from '../../helpers/ssoBridge.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const TALK = 'https://talk.example.com'

// The bullets are translated HTML; these stand-ins carry the three kinds of
// link they hold: a plain Talk link, one already in Talk's /session/sso form,
// and one elsewhere.
function mountSidebar() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        dashboard: {
          ...en.dashboard,
          getting_the_most_bullet1: `<a href="${TALK}/t/meet-and-greet/44" target="_blank">introduce yourself</a>`,
          getting_the_most_bullet2: `<a href="${TALK}/session/sso?return_path=${TALK}/t/kit/324" target="_blank">event kit</a>`,
          getting_the_most_bullet3: '<a href="https://wiki.example.com/Main_Page" target="_blank">wiki</a>',
        },
      },
    },
  })

  return mount(DashboardRightSidebar, { global: { plugins: [i18n] } })
}

// The bullet links open in a new tab, so the bridge sends that tab on.
function linkIn(wrapper, text) {
  return wrapper.findAll('a').find((a) => a.text() === text)
}

describe('components/dashboard/DashboardRightSidebar', () => {
  let bridge
  let tab
  let openSpy

  beforeEach(() => {
    setActivePinia(createPinia())
    useSessionStore().config = { discourse_url: TALK }
    bridge = stubSsoBridge()
    tab = { opener: window, location: { href: '' } }
    openSpy = vi.spyOn(window, 'open').mockReturnValue(tab)
  })

  afterEach(() => {
    openSpy.mockRestore()
    bridge.restore()
  })

  async function redirectFrom(tabOpened) {
    await vi.waitFor(() => expect(tabOpened.location.href).toContain('http://api.test/auth/bridge'))
    return new URL(tabOpened.location.href).searchParams.get('redirect')
  }

  // #966: a plain Talk link left someone not yet logged in to Talk at
  // "Access Denied" for a topic that needs an account.
  it('sends a plain Talk link through Talk\'s SSO, in a new tab like its target="_blank"', async () => {
    await linkIn(mountSidebar(), 'introduce yourself').trigger('click')

    expect(openSpy).toHaveBeenCalledWith('', '_blank')
    expect(await redirectFrom(tab)).toBe(talkSso(TALK, `${TALK}/t/meet-and-greet/44`))
    expect(window.location.href).toBe('')
  })

  it('sends a link already in Talk\'s /session/sso form through the bridge as it is', async () => {
    await linkIn(mountSidebar(), 'event kit').trigger('click')

    expect(await redirectFrom(tab)).toBe(`${TALK}/session/sso?return_path=${TALK}/t/kit/324`)
  })

  it('leaves links to anywhere else alone', async () => {
    const wrapper = mountSidebar()

    // Note whether the sidebar's own handler stopped the click by the time it
    // bubbles out of the component, then stop it ourselves so the test
    // doesn't really follow the link.
    let preventedBySidebar = null
    wrapper.element.addEventListener('click', (event) => {
      preventedBySidebar = event.defaultPrevented
      event.preventDefault()
    })

    linkIn(wrapper, 'wiki').element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }))

    expect(preventedBySidebar).toBe(false)
    expect(openSpy).not.toHaveBeenCalled()
    expect(bridge.ssoTicket).not.toHaveBeenCalled()
  })
})
