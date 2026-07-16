import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import NetworkStats from '../../../app/components/networks/NetworkStats.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(NetworkStats, {
    props: { stats: null, groupsCount: null, ...props },
    global: { plugins: [i18n] },
  })
}

function stats(overrides = {}) {
  return {
    co2_total: 6000,
    co2_powered: 4000,
    co2_unpowered: 2000,
    waste_total: 1500,
    waste_powered: 1000,
    waste_unpowered: 500,
    fixed_devices: 300,
    fixed_powered: 200,
    fixed_unpowered: 100,
    participants: 120,
    hours_volunteered: 8766,
    parties: 12,
    ...overrides,
  }
}

describe('components/networks/NetworkStats', () => {
  it('shows a loading state', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="network-stats-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="network-stats-groups"]').exists()).toBe(false)
  })

  it('shows the unavailable state on error or missing stats', () => {
    const wrapper = mountComponent({ error: true })
    expect(wrapper.find('[data-testid="network-stats-unavailable"]').exists()).toBe(true)

    const wrapper2 = mountComponent({ stats: null })
    expect(wrapper2.find('[data-testid="network-stats-unavailable"]').exists()).toBe(true)
  })

  it('renders the groups and parties counts alongside the reused ImpactStats tiles', () => {
    const wrapper = mountComponent({ stats: stats(), groupsCount: 4 })

    expect(wrapper.find('[data-testid="network-stats-groups"]').text()).toContain('4')
    expect(wrapper.find('[data-testid="network-stats-parties"]').text()).toContain('12')
    // Reuses ImpactStats.vue rather than re-implementing its markup.
    expect(wrapper.find('[data-testid="impact-stats"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="impact-stat-co2"]').exists()).toBe(true)
  })

  it('defaults groupsCount to 0 when not supplied', () => {
    const wrapper = mountComponent({ stats: stats(), groupsCount: null })
    expect(wrapper.find('[data-testid="network-stats-groups"]').text()).toContain('0')
  })
})
