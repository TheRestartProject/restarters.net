import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ConsentPage from '../../../app/pages/user/consent.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// Pins the consent checkboxes to the DB fields they set. A prior version bound
// the "Historical Repair Data" text to consent_gdpr and the "Personal Data"
// text to consent_past_data - swapped - so a user consenting to one thing set a
// different field. Legacy register-new.blade.php (07e6abd7cc^) pairs:
//   consent_gdpr        <-> reg-step-4-label1  (Personal Data)
//   consent_future_data <-> reg-step-4-label2  (Repair Data)
//   consent_past_data   <-> reg-step-4-label3  (Historical Repair Data)

// Minimal stubs that render the default slot and keep the data-testid, so the
// v-html label text is queryable per checkbox.
const passthrough = (tag = 'div') => ({
  inheritAttrs: true,
  template: `<${tag} v-bind="$attrs"><slot /></${tag}>`,
})

function mountConsent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(ConsentPage, {
    global: {
      plugins: [i18n],
      mocks: {
        $api: { auth: { consent: vi.fn() } },
      },
      stubs: {
        BForm: passthrough('form'),
        BFormGroup: passthrough('div'),
        BFormSelect: { template: '<select><slot /></select>' },
        BFormInput: passthrough('input'),
        BFormCheckbox: passthrough('label'),
        BButton: passthrough('button'),
        BAlert: passthrough('div'),
      },
    },
  })
}

describe('pages/user/consent', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useNuxtApp', () => ({ $api: { auth: { consent: vi.fn() } } }))
    vi.stubGlobal('navigateTo', vi.fn())
    vi.stubGlobal('useRoute', () => ({ query: {}, params: {}, fullPath: '/user/consent' }))
  })

  it('pairs each consent checkbox with the correct legal notice', () => {
    const wrapper = mountConsent()

    const gdpr = wrapper.get('[data-testid="consent-gdpr"]').text()
    const past = wrapper.get('[data-testid="consent-past-data"]').text()
    const future = wrapper.get('[data-testid="consent-future-data"]').text()

    expect(gdpr).toContain('Personal Data')
    expect(gdpr).not.toContain('Historical Repair Data')

    expect(past).toContain('Historical Repair Data')

    expect(future).toContain('Repair Data')
    expect(future).not.toContain('Personal Data')
    expect(future).not.toContain('Historical')
  })

  it('renders the step-4 intro copy above the checkboxes', () => {
    const wrapper = mountConsent()
    expect(wrapper.get('[data-testid="consent-intro"]').text()).toContain(
      en.registration['reg-step-4']
    )
  })

  it('offers ages from 18 (not 16), matching register.vue and legacy', () => {
    const wrapper = mountConsent()
    const currentYear = new Date().getFullYear()
    const years = wrapper.findAll('#consent-age option').map((o) => Number(o.attributes('value')))

    expect(Math.max(...years)).toBe(currentYear - 18)
    expect(Math.min(...years)).toBe(currentYear - 99)
  })
})
