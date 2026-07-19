import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import DashboardWhatsHappening from '../../../app/components/dashboard/DashboardWhatsHappening.vue'
import { useSessionStore } from '../../../app/stores/session.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountComponent() {
  // dashboard.whats_happening_heading/see_all are new lang/en/dashboard.php
  // keys (this rebuild) not yet in the generated client i18n JSON - the main
  // agent regenerates that centrally, so they're overlaid here inline rather
  // than editing client/i18n/locales/*.json directly.
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        dashboard: {
          ...en.dashboard,
          whats_happening_heading: "What's happening",
          whats_happening_see_all: 'see all',
        },
      },
    },
  })

  return mount(DashboardWhatsHappening, {
    global: { plugins: [i18n] },
  })
}

describe('components/dashboard/DashboardWhatsHappening', () => {
  let originalHref

  beforeEach(() => {
    setActivePinia(createPinia())

    // happy-dom's window.location isn't directly assignable; redefine it per
    // test like useSsoBridge.spec.js does, so a real navigation is observable.
    originalHref = window.location.href
    delete window.location
    window.location = { href: '' }
  })

  afterEach(() => {
    window.location = { href: originalHref }
  })

  it('renders the heading and a "see all" link', () => {
    const wrapper = mountComponent()

    expect(wrapper.text()).toContain("What's happening")
    expect(wrapper.find('[data-testid="whats-happening-see-all"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="whats-happening-see-all"]').text()).toBe('see all')
  })

  it('clicking "see all" is a no-op with no discourse_url configured', async () => {
    const wrapper = mountComponent()

    await wrapper.find('[data-testid="whats-happening-see-all"]').trigger('click')

    expect(window.location.href).toBe('')
  })

  it('targets /latest on the session\'s configured discourse_url', async () => {
    useSessionStore().config = { discourse_url: 'https://talk.restarters.net' }

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="whats-happening-see-all"]').trigger('click')

    expect(window.location.href).toBe('https://talk.restarters.net/latest')
  })
})
