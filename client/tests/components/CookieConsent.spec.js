import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it } from 'vitest'
import CookieConsent from '../../app/components/CookieConsent.vue'
import clientEn from '../../i18n/locales/client-en.json'
import { useCookieConsent } from '../../app/composables/useCookieConsent.js'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

// Renders the modal body inline so the categories can be asserted without
// driving bootstrap-vue-next's real modal; @ok fires from the stub's save
// button, as BModal would.
const BModalStub = {
  props: ['modelValue', 'title', 'okTitle'],
  emits: ['ok', 'update:modelValue'],
  template: `<div v-if="modelValue">
    <slot />
    <button data-testid="modal-ok" @click="$emit('ok')">{{ okTitle }}</button>
  </div>`,
}
const BFormCheckboxStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: `<input type="checkbox" :checked="modelValue"
    v-bind="$attrs" @change="$emit('update:modelValue', $event.target.checked)">`,
}

function mountBanner() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...clientEn } } })
  return mount(CookieConsent, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BModal: BModalStub, BFormCheckbox: BFormCheckboxStub },
    },
  })
}

function storedConsent() {
  const row = document.cookie.split('; ').find((c) => c.startsWith('gdprcookienotice='))
  return row ? JSON.parse(decodeURIComponent(row.slice('gdprcookienotice='.length))) : null
}

describe('components/CookieConsent', () => {
  beforeEach(() => {
    document.cookie = 'gdprcookienotice=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/'
    // The composable holds its parsed cookie in module state, so clearing the
    // cookie alone leaves a previous test's consent in memory.
    useCookieConsent().reopen()
  })

  it('shows the banner with the notice text, settings control and OK button', () => {
    const wrapper = mountBanner()
    expect(wrapper.find('[data-testid="cookie-consent"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('We use cookies')
    expect(wrapper.find('[data-testid="cookie-consent-settings"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="cookie-consent-accept"]').exists()).toBe(true)
  })

  // The defect this replaces: "Cookie settings" navigated to the policy
  // article, so there was no way to decline analytics. That article link is
  // develop's separate "statement" link and belongs inside the dialog.
  it('opens a settings dialog rather than navigating to the policy article', async () => {
    const wrapper = mountBanner()
    const settings = wrapper.find('[data-testid="cookie-consent-settings"]')

    expect(settings.attributes('href')).toBeUndefined()
    expect(wrapper.find('[data-testid="cookie-consent-modal"]').exists()).toBe(false)

    await settings.trigger('click')

    expect(wrapper.find('[data-testid="cookie-consent-modal"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="cookie-consent-statement"]').attributes('href'))
      .toBe('/about/cookie-policy')
  })

  it('lists every category, with essential always on and no switch', async () => {
    const wrapper = mountBanner()
    await wrapper.find('[data-testid="cookie-consent-settings"]').trigger('click')

    for (const c of ['essential', 'performace', 'analytics', 'marketing']) {
      expect(wrapper.find(`[data-testid="cookie-category-${c}"]`).exists()).toBe(true)
    }

    expect(wrapper.find('[data-testid="cookie-toggle-performace"]').exists()).toBe(true)
    // Essential cannot be switched off - develop shows "Always on" instead.
    expect(wrapper.find('[data-testid="cookie-toggle-essential"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="cookie-category-essential"]').text()).toContain('Always on')
  })

  it('persists per-category choices, so analytics can be declined', async () => {
    const wrapper = mountBanner()
    await wrapper.find('[data-testid="cookie-consent-settings"]').trigger('click')

    await wrapper.find('[data-testid="cookie-toggle-analytics"]').setValue(false)
    await wrapper.find('[data-testid="modal-ok"]').trigger('click')

    const stored = storedConsent()
    expect(stored.analytics).toBe(false)
    // Essential is forced on regardless of what was submitted.
    expect(stored.necessary).toBe(true)
    expect(wrapper.find('[data-testid="cookie-consent"]').exists()).toBe(false)
  })

  it("OK accepts develop's defaults - marketing off, analytics on", async () => {
    const wrapper = mountBanner()
    await wrapper.find('[data-testid="cookie-consent-accept"]').trigger('click')

    const stored = storedConsent()
    expect(stored).toMatchObject({ necessary: true, performace: true, analytics: true, marketing: false })
    expect(wrapper.find('[data-testid="cookie-consent"]').exists()).toBe(false)
  })
})
