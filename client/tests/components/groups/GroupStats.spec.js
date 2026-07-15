import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupStats from '../../../app/components/groups/GroupStats.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupStats, {
    props,
    global: { plugins: [i18n] },
  })
}

const STATS = {
  group_stats: { parties: 4, participants: 20, hours_volunteered: 30 },
  device_stats: { fixed: 5, repairable: 3, dead: 2 },
  top_devices: [{ name: 'Laptop', counter: 5 }, { name: 'Toaster', counter: 3 }],
  cluster_stats: {
    1: {
      fixed: 2,
      repairable: 1,
      dead: 0,
      total: 3,
      most_seen: { name: 'Laptop', count: 3 },
      most_repaired: { name: 'Laptop', count: 2 },
      least_repaired: { name: 'Mouse', count: 0 },
    },
  },
}

describe('components/groups/GroupStats', () => {
  it('shows a loading skeleton', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="group-stats-loading"]').exists()).toBe(true)
  })

  it('shows an unavailable message when there is an error', () => {
    const wrapper = mountComponent({ error: true })
    expect(wrapper.find('[data-testid="group-stats-unavailable"]').exists()).toBe(true)
  })

  it('shows an unavailable message when stats is null (endpoint not implemented yet)', () => {
    const wrapper = mountComponent({ stats: null })
    expect(wrapper.find('[data-testid="group-stats-unavailable"]').exists()).toBe(true)
  })

  it('renders group facts, device stats and totals', () => {
    const wrapper = mountComponent({ stats: STATS })

    expect(wrapper.find('[data-testid="group-stats-parties"]').text()).toContain('4')
    expect(wrapper.find('[data-testid="group-stats-participants"]').text()).toContain('20')
    expect(wrapper.find('[data-testid="group-stats-hours"]').text()).toContain('30')
    expect(wrapper.find('[data-testid="group-stats-fixed"]').text()).toContain('5')
    expect(wrapper.find('[data-testid="group-stats-repairable"]').text()).toContain('3')
    expect(wrapper.find('[data-testid="group-stats-dead"]').text()).toContain('2')
    expect(wrapper.find('[data-testid="group-stats-total"]').text()).toContain('10')
  })

  it('renders the top 3 devices', () => {
    const wrapper = mountComponent({ stats: STATS })

    expect(wrapper.find('[data-testid="group-stats-top-device-0"]').text()).toContain('5')
    expect(wrapper.find('[data-testid="group-stats-top-device-1"]').text()).toContain('3')
  })

  it('renders cluster breakdown for populated clusters only', () => {
    const wrapper = mountComponent({ stats: STATS })

    expect(wrapper.find('[data-testid="group-stats-cluster-1"]').text()).toContain('3')
    // Cluster 2 has no data in cluster_stats - the section still renders (heading), but no numbers.
    expect(wrapper.find('[data-testid="group-stats-cluster-2"]').exists()).toBe(true)
  })
})
