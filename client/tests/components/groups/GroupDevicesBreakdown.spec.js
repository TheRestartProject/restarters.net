import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupDevicesBreakdown from '../../../app/components/groups/GroupDevicesBreakdown.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupDevicesBreakdown, { props, global: { plugins: [i18n], stubs: GROUP_VIEW_STUBS } })
}

const CLUSTER_STATS = {
  1: {
    fixed: 2,
    repairable: 1,
    dead: 0,
    total: 3,
    most_seen: { name: 'Laptop', count: 3 },
    most_repaired: { name: 'Laptop', count: 2 },
    least_repaired: { name: 'Mouse', count: 0 },
  },
}

describe('components/groups/GroupDevicesBreakdown', () => {
  it('renders nothing when clusterStats is null', () => {
    const wrapper = mountComponent({ clusterStats: null })
    expect(wrapper.find('[data-testid="group-stats-clusters"]').exists()).toBe(false)
  })

  it('renders a tabbed interface on desktop rather than four simultaneous boxes (gap 7)', () => {
    const wrapper = mountComponent({ clusterStats: CLUSTER_STATS })

    expect(wrapper.find('[data-testid="group-stats-clusters-tabs"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-stats-cluster-1"]').text()).toContain('3')
    // Cluster 2 has no data - the tab still renders (heading), but no numbers.
    expect(wrapper.find('[data-testid="group-stats-cluster-2"]').exists()).toBe(true)
  })

  it('renders a mobile accordion counterpart', () => {
    const wrapper = mountComponent({ clusterStats: CLUSTER_STATS })
    expect(wrapper.find('[data-testid="group-stats-cluster-1-mobile"]').exists()).toBe(true)
  })

  it('shows most-seen/most-repaired/least-repaired as icon-topped stats, not plain text (gap 8)', () => {
    const wrapper = mountComponent({ clusterStats: CLUSTER_STATS })

    const panel = wrapper.find('[data-testid="group-stats-cluster-1"]')
    expect(panel.find('img[src="/images/most-seen_ico.svg"]').exists()).toBe(true)
    expect(panel.find('img[src="/images/most-repaired_ico.svg"]').exists()).toBe(true)
    expect(panel.find('img[src="/images/least-repaired_ico.svg"]').exists()).toBe(true)
  })

  it('shows fixed/repairable/dead as a percentage of the cluster total, not a word label (parity: breakdown figures)', () => {
    const wrapper = mountComponent({ clusterStats: CLUSTER_STATS })

    // fixed: 2, repairable: 1, dead: 0, total: 3.
    const panel = wrapper.find('[data-testid="group-stats-cluster-1"]')
    expect(panel.text()).toContain('66.67%')
    expect(panel.text()).toContain('33.33%')
    expect(panel.text()).not.toContain('Fixed')
    expect(panel.text()).not.toContain('Repairable')
    expect(panel.text()).not.toContain('End-of-life')

    // most-seen/most-repaired/least-repaired keep their device-name subtitle.
    expect(panel.text()).toContain('Laptop')
  })

  it('shows a divider between the fixed/repairable/dead trio and the most-seen/repaired trio (parity: divider)', () => {
    const wrapper = mountComponent({ clusterStats: CLUSTER_STATS })
    expect(wrapper.find('[data-testid="group-stats-cluster-1-divider"]').exists()).toBe(true)
  })

  // StatsValue.vue's printableCount(): cluster-panel counts get thousand
  // separators (finding 52).
  it('formats cluster-panel counts with thousand separators', () => {
    const wrapper = mountComponent({
      clusterStats: {
        1: {
          fixed: 1234,
          repairable: 0,
          dead: 0,
          most_seen: { name: 'Laptop', count: 2345 },
          most_repaired: { name: 'Laptop', count: 0 },
          least_repaired: { name: 'Mouse', count: 0 },
        },
      },
    })

    const panel = wrapper.find('[data-testid="group-stats-cluster-1"]')
    expect(panel.text()).toContain('1,234')
    expect(panel.text()).toContain('2,345')
  })
})
