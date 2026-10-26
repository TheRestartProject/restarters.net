import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import NetworkStats from '../../../app/components/networks/NetworkStats.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// lang/en/networks.php gained `stats.waste_diverted`/`stats.co2_prevented`
// alongside this Nuxt work (parity-v2/networks.md gap #5) but
// client/i18n/locales/en.json is a generated, checked-in artifact this
// change intentionally leaves untouched (php artisan
// translations:export-client) - overlay the new keys here so the spec
// doesn't depend on regenerating it.
function mountComponent(props = {}) {
  const messages = {
    en: {
      ...en,
      ...clientEn,
      networks: {
        ...en.networks,
        stats: { ...en.networks.stats, waste_diverted: 'Waste Diverted', co2_prevented: 'CO2 Prevented' },
      },
    },
  }
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(NetworkStats, {
    props: { stats: null, groupsCount: null, ...props },
    global: { plugins: [i18n] },
  })
}

function stats(overrides = {}) {
  return {
    co2_total: 6000,
    waste_total: 1500,
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

  // Legacy NetworkPage.vue's Impact section is ONE unified 4-tile grid -
  // Groups / Events / Waste diverted / CO2 prevented - not the
  // Fixometer-wide participants/years-volunteered/powered-unpowered tiles
  // (parity-v2/networks.md gap #5).
  it('renders exactly the 4 legacy tiles: groups, events, waste diverted, CO2 prevented', () => {
    const wrapper = mountComponent({ stats: stats(), groupsCount: 4 })

    expect(wrapper.find('[data-testid="network-stats-groups"]').text()).toContain('4')
    expect(wrapper.find('[data-testid="network-stats-parties"]').text()).toContain('12')

    const waste = wrapper.find('[data-testid="network-stats-waste"]')
    expect(waste.text()).toContain('1.5 t')
    expect(waste.text()).toContain('Waste Diverted')

    const co2 = wrapper.find('[data-testid="network-stats-co2"]')
    expect(co2.text()).toContain('6.0 t')
    expect(co2.text()).toContain('CO2 Prevented')

    // No longer reuses the Fixometer-wide ImpactStats component (which
    // pulled in participants/years-volunteered/powered-unpowered tiles and
    // a "Latest Repairs" banner with no place on this page).
    expect(wrapper.find('[data-testid="impact-stats"]').exists()).toBe(false)
  })

  it('formats weights under 1000kg in kg, not tonnes', () => {
    const wrapper = mountComponent({ stats: stats({ waste_total: 400, co2_total: 0 }), groupsCount: 1 })

    expect(wrapper.find('[data-testid="network-stats-waste"]').text()).toContain('400 kg')
    expect(wrapper.find('[data-testid="network-stats-co2"]').text()).toContain('0 kg')
  })

  it('defaults groupsCount to 0 when not supplied', () => {
    const wrapper = mountComponent({ stats: stats(), groupsCount: null })
    expect(wrapper.find('[data-testid="network-stats-groups"]').text()).toContain('0')
  })

  it('renders all 4 tiles as uniform bordered stat-box cards', () => {
    const wrapper = mountComponent({ stats: stats(), groupsCount: 4 })

    expect(wrapper.get('[data-testid="network-stats-groups"]').classes()).toContain('stat-box')
    expect(wrapper.get('[data-testid="network-stats-parties"]').classes()).toContain('stat-box')
    expect(wrapper.get('[data-testid="network-stats-waste"]').classes()).toContain('stat-box')
    expect(wrapper.get('[data-testid="network-stats-co2"]').classes()).toContain('stat-box')
  })
})
