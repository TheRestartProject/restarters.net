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
})
