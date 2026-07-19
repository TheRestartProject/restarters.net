import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it } from 'vitest'
import CookieConsent from '../../app/components/CookieConsent.vue'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountBanner() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...clientEn } } })
  return mount(CookieConsent, { global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } } })
}

describe('components/CookieConsent', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('shows the banner with the notice text, settings link and OK button when not accepted', () => {
    const wrapper = mountBanner()
    expect(wrapper.find('[data-testid="cookie-consent"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('We use cookies')
    expect(wrapper.find('[data-testid="cookie-consent-settings"]').attributes('href')).toBe('/about/cookie-policy')
    expect(wrapper.find('[data-testid="cookie-consent-accept"]').exists()).toBe(true)
  })

  it('hides the banner and persists consent when OK is clicked', async () => {
    const wrapper = mountBanner()
    await wrapper.find('[data-testid="cookie-consent-accept"]').trigger('click')

    expect(wrapper.find('[data-testid="cookie-consent"]').exists()).toBe(false)
    expect(localStorage.getItem('restarters_cookie_consent')).toBe('accepted')
  })
})
