import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { afterEach, describe, expect, it, vi } from 'vitest'
import LatestRepairs from '../../../app/components/fixometer/LatestRepairs.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(LatestRepairs, {
    props: { latestData: null, ...props },
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } },
  })
}

describe('components/fixometer/LatestRepairs', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.stubGlobal('useRuntimeConfig', () => ({ public: {} }))
  })

  it('shows a loading skeleton while loading', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="latest-repairs-loading"]').exists()).toBe(true)
  })

  // Gap fix: legacy FixometerLatestData.vue's `latestData` prop was
  // `required: true` - it never handled a null/missing latest-repaired-event,
  // so the grid cell rendered nothing at all (no card, no text). A prior
  // version showed a "No repairs recorded yet." message here instead, which
  // develop's rendered output doesn't have.
  it('shows an empty placeholder with no visible text when there is no finished-event-with-repairs yet', () => {
    const wrapper = mountComponent({ latestData: null })
    const empty = wrapper.find('[data-testid="latest-repairs-empty"]')
    expect(empty.exists()).toBe(true)
    expect(empty.text()).toBe('')
  })

  it('renders the group link and the waste-prevented string with the event id and rounded-up amount', () => {
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'https://api.example.com' } }))

    const wrapper = mountComponent({
      latestData: { id: 42, waste_prevented: 12.3, group: { id: 7, name: 'Chiswick Restarters', image: null } },
    })

    const link = wrapper.find('[data-testid="latest-repairs-group-link"]')
    expect(link.attributes('href')).toBe('/group/view/7')
    expect(link.text()).toBe('Chiswick Restarters')

    // devices.group_prevented rounds up (Math.ceil) and embeds the event id
    // in the href baked into the translation string.
    const html = wrapper.find('[data-testid="latest-repairs-content"]').html()
    expect(html).toContain('/party/view/42')
    expect(html).toContain('13 kg')
  })

  it('shows the group image when present', () => {
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'https://api.example.com' } }))

    const wrapper = mountComponent({
      latestData: { id: 1, waste_prevented: 1, group: { id: 2, name: 'G', image: 'mid_x.png' } },
    })

    const img = wrapper.find('[data-testid="latest-repairs-group-image"]')
    expect(img.exists()).toBe(true)
    expect(img.attributes('src')).toBe('https://api.example.com/uploads/mid_x.png')
  })
})
