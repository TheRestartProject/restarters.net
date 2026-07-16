import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import CookiePolicyPage from '../../../app/pages/about/cookie-policy.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(CookiePolicyPage, {
    global: { plugins: [i18n] },
  })
}

describe('pages/about/cookie-policy', () => {
  it('renders the title and every cookie row', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="cookie-policy-title"]').text()).toBe('Cookie Policy')

    const table = wrapper.find('[data-testid="cookie-policy-table"]')
    expect(table.text()).toContain('restarters_session')
    expect(table.text()).toContain('XSRF-TOKEN')
    expect(table.text()).toContain('UseCDNCache, UseDC')
    expect(table.text()).toContain('_ga, _gat, _gid')
  })

  it('says there are currently no third-party cookies', () => {
    const wrapper = mountPage()
    expect(wrapper.text()).toContain('There are currently no third-party cookies.')
  })
})
