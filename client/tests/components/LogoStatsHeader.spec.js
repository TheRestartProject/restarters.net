import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LogoStatsHeader from '../../app/components/LogoStatsHeader.vue'
import { useFixometerStore } from '../../app/stores/fixometer.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

// The logged-out header stats bar (#logostats-header / includes/info.blade).
// Four figures from GET /api/homepage_data: items fixed, CO2e, waste, events
// held; weights shown in tonnes past 1000 kg else kg.

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountHeader() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  return mount(LogoStatsHeader, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, IconLogo: { template: '<svg />' } },
    },
  })
}

describe('components/LogoStatsHeader', () => {
  let fixometerStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useNuxtApp', () => ({ $api: { config: { homepageData: vi.fn() } } }))
    fixometerStore = useFixometerStore()
    fixometerStore.fetchImpactData = vi.fn().mockResolvedValue({})
  })

  it('renders the four impact figures including events held', async () => {
    fixometerStore.impactData.data = {
      items_fixed: 44538,
      co2_total: 3934000, // kg -> 3,934 tonnes
      waste_total: 455000, // kg -> 455 tonnes
      events_held: 19055,
    }

    const wrapper = mountHeader()
    const bar = wrapper.get('[data-testid="logo-stats-figures"]').text()

    expect(bar).toContain('44,538')
    expect(bar).toContain('3,934') // co2 tonnes
    expect(bar).toContain('455') // waste tonnes
    expect(bar).toContain('19,055') // events held
    expect(bar).toContain('Items fixed')
    expect(bar).toContain('Events held')
  })

  it('shows kg (not tonnes) for weights under 1000', () => {
    fixometerStore.impactData.data = { items_fixed: 1, co2_total: 500, waste_total: 200, events_held: 0 }
    const wrapper = mountHeader()
    const bar = wrapper.get('[data-testid="logo-stats-figures"]').text()
    expect(bar).toContain('500 kg')
    expect(bar).toContain('200 kg')
  })

  it('fetches impact data on mount', () => {
    mountHeader()
    expect(fixometerStore.fetchImpactData).toHaveBeenCalled()
  })

  it('renders the logo and no figures before data loads', () => {
    fixometerStore.impactData.data = null
    const wrapper = mountHeader()
    expect(wrapper.find('[data-testid="logo-stats-logo"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="logo-stats-figures"]').exists()).toBe(false)
  })
})
