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
    props: { stats: null, ...props },
    global: { plugins: [i18n] },
  })
}

function stats(overrides = {}) {
  return {
    co2_total: 6000,
    waste_total: 1500,
    parties: 12,
    groups: 4,
    ...overrides,
  }
}

const TILES = ['groups', 'parties', 'waste', 'co2']

function values(wrapper) {
  return TILES.map((tile) => wrapper.get(`[data-testid="network-stats-${tile}"] .stat-value`).text())
}

describe('components/networks/NetworkStats', () => {
  // The stats arrive after the rest of the page (they are slow for a large network), so the tiles are there from
  // the start with an ellipsis in place of each figure, as develop's NetworkPage.vue.
  it('shows the tiles with an ellipsis until the stats arrive', () => {
    const wrapper = mountComponent({ stats: null })
    expect(values(wrapper)).toEqual(['…', '…', '…', '…'])
    expect(wrapper.text()).toContain('Waste Diverted')
  })

  it('shows a dash in each tile if the stats fail to load', () => {
    const wrapper = mountComponent({ stats: null, error: true })
    expect(values(wrapper)).toEqual(['–', '–', '–', '–'])
  })

  it('renders exactly the 4 tiles: groups, events, waste diverted, CO2 prevented', () => {
    const wrapper = mountComponent({ stats: stats() })

    expect(values(wrapper)).toEqual(['4', '12', '1.5 t', '6.0 t'])
    expect(wrapper.get('[data-testid="network-stats-waste"]').text()).toContain('Waste Diverted')
    expect(wrapper.get('[data-testid="network-stats-co2"]').text()).toContain('CO2 Prevented')
    expect(wrapper.find('[data-testid="impact-stats"]').exists()).toBe(false)
  })

  it('formats weights under 1000kg in kg, not tonnes', () => {
    const wrapper = mountComponent({ stats: stats({ waste_total: 400, co2_total: 0 }) })

    expect(wrapper.find('[data-testid="network-stats-waste"]').text()).toContain('400 kg')
    expect(wrapper.find('[data-testid="network-stats-co2"]').text()).toContain('0 kg')
  })

  it('counts no groups as 0', () => {
    const wrapper = mountComponent({ stats: stats({ groups: 0 }) })
    expect(wrapper.get('[data-testid="network-stats-groups"] .stat-value').text()).toBe('0')
  })

  it('renders all 4 tiles as uniform bordered stat-box cards', () => {
    const wrapper = mountComponent({ stats: stats() })

    for (const tile of TILES) {
      expect(wrapper.get(`[data-testid="network-stats-${tile}"]`).classes()).toContain('stat-box')
    }
  })
})
