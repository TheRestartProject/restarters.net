import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import NetworkPage from './NetworkPage.vue'
import axios from 'axios'

jest.mock('axios')

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

function makeStore() {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: { getModerate: () => ({}) },
      },
      events: {
        namespaced: true,
        getters: { getModerate: () => ({}) },
      },
    },
  })
}

const groupMapStub = {
  name: 'GroupMapAndList',
  props: {
    initialBounds: { type: Array },
    network: { type: Number, default: null },
    showFilters: { type: Boolean, default: false },
    canManageTags: { type: Boolean, default: false },
    availableTags: { type: Array, default: () => [] },
    showAllButton: { type: Boolean, default: false },
    networks: { type: Array, default: null },
  },
  template: '<div class="stub-map" />',
}

function makeWrapper(props = {}) {
  return mount(NetworkPage, {
    localVue,
    store: makeStore(),
    propsData: {
      network: { id: 5, name: 'Test Network', coordinators: [] },
      // Non-empty stats and tags so mounted() doesn't hit the network.
      initialStats: { groups: 2 },
      initialTags: [{ id: 1, name: 'Solder', description: null, groups_count: 2 }],
      canManageTags: true,
      isLoggedIn: true,
      ...props,
    },
    stubs: {
      GroupsRequiringModeration: true,
      EventsRequiringModeration: true,
      GroupMapAndList: groupMapStub,
    },
  })
}

// The network page must show the map + list of the network's groups
// (previously it only linked out to /group/network/{id}).
test('embeds the group map and list, scoped to this network, with filters', () => {
  const wrapper = makeWrapper()

  const map = wrapper.findComponent(groupMapStub)
  expect(map.exists()).toBe(true)
  expect(map.props('network')).toBe(5)
  expect(map.props('showFilters')).toBe(true)
  expect(map.props('canManageTags')).toBe(true)
  // Start zoomed out to show every group in the network: an inverted world
  // box makes GroupMap frame all (network-filtered) groups.
  expect(map.props('initialBounds')).toEqual([[90, 180], [-90, -180]])
})

test('passes network tags to the list in the shape the tag filter expects (tag_name)', () => {
  const wrapper = makeWrapper()

  const tags = wrapper.findComponent(groupMapStub).props('availableTags')
  expect(tags).toHaveLength(1)
  expect(tags[0].id).toBe(1)
  expect(tags[0].tag_name).toBe('Solder')
  expect(tags[0].name).toBe('Solder')
})

test('does not render the removed groups_count/view_groups_link lang keys', () => {
  const wrapper = makeWrapper()

  // These keys were removed from the lang files; rendering them would show
  // the literal key strings to users.
  expect(wrapper.text()).not.toContain('networks.show.groups_count')
  expect(wrapper.text()).not.toContain('networks.show.view_groups_link')
})

test('does not offer the tag filter to users who cannot see tags', () => {
  const wrapper = makeWrapper({ canManageTags: false, initialTags: [] })

  const map = wrapper.findComponent(groupMapStub)
  expect(map.props('canManageTags')).toBe(false)
  expect(map.props('availableTags')).toEqual([])
})

test('links to the network reporting dashboard from the actions menu', () => {
  const wrapper = makeWrapper({
    canModerate: true,
    network: { id: 5, name: 'Test Network', coordinators: [], reporting_url: 'https://reports.example.org/dashboard/7' },
  })

  const link = wrapper.find('a.network-reporting-link')
  expect(link.exists()).toBe(true)
  expect(link.attributes('href')).toBe('https://reports.example.org/dashboard/7')
  expect(link.attributes('target')).toBe('_blank')
})

test('has no reporting link when the network has no dashboard', () => {
  const wrapper = makeWrapper()

  expect(wrapper.find('a.network-reporting-link').exists()).toBe(false)
  expect(wrapper.find('a.network-reporting-inline').exists()).toBe(false)
})

// The same "see more" link the group page has, where people are already
// looking at the network's figures.
test('links to the network reports from under the impact figures', () => {
  const wrapper = makeWrapper({
    network: { id: 5, name: 'Test Network', coordinators: [], reporting_url: 'https://reports.example.org/dashboard/7' },
  })

  const link = wrapper.find('.network-stats a.network-reporting-inline')
  expect(link.exists()).toBe(true)
  expect(link.attributes('href')).toBe('https://reports.example.org/dashboard/7')
  expect(link.attributes('target')).toBe('_blank')
  expect(link.text()).toBe('networks.general.see_network_reports')
})

// The tags section sits below the groups, so the list mustn't keep growing as
// you scroll towards it (#918).
test('shows a page of groups and a button for the rest, not an infinite scroll', () => {
  const wrapper = makeWrapper()

  expect(wrapper.findComponent(groupMapStub).props('showAllButton')).toBe(true)
})

const flush = () => new Promise(resolve => setTimeout(resolve, 0))

test('the period toggle fetches the last 12 completed months and says which months they are', async () => {
  axios.get.mockResolvedValue({
    data: { groups: 2, parties: 7, waste_total: 10, co2_total: 20, period: { start: '2025-10-01', end: '2026-09-30' } }
  })
  const wrapper = makeWrapper()

  expect(wrapper.find('.period-all').classes()).toContain('active')
  await wrapper.find('.period-last12').trigger('click')
  await flush()

  expect(axios.get).toHaveBeenCalledWith('/api/v2/networks/5/stats', { params: { period: 'last_12_months' } })
  expect(wrapper.find('.period-last12').classes()).toContain('active')
  expect(wrapper.find('.stat-events .stat-value').text()).toBe('7')
  // Jest has no translations loaded, so check the label is there and built from the period's months.
  expect(wrapper.find('.stats-period').exists()).toBe(true)
  expect(wrapper.vm.periodLabel).toBe('networks.stats.period_range')
  expect(wrapper.vm.stats.period).toEqual({ start: '2025-10-01', end: '2026-09-30' })

  // Back to all time uses the stats already on the page.
  axios.get.mockClear()
  await wrapper.find('.period-all').trigger('click')
  await flush()
  expect(axios.get).not.toHaveBeenCalled()
  expect(wrapper.find('.stats-period').exists()).toBe(false)
})

test('lists the nearest groups in the network with their distance', () => {
  const wrapper = makeWrapper({
    nearbyGroups: {
      from: 'Hackney Fixers',
      groups: [
        { id: 7, name: 'Islington Repair Cafe', location: 'Islington', distance: 5.6 },
        { id: 8, name: 'Camden Fixers', location: 'Camden', distance: 8.1 }
      ]
    }
  })

  const rows = wrapper.findAll('.nearby-group')
  expect(rows).toHaveLength(2)
  expect(rows.at(0).find('a').attributes('href')).toBe('/group/view/7')
  expect(rows.at(0).text()).toContain('Islington Repair Cafe')
  expect(rows.at(1).text()).toContain('Camden')
})

test('has no nearby groups section without a located group of your own', () => {
  expect(makeWrapper().find('.nearby-groups').exists()).toBe(false)
  expect(makeWrapper({ nearbyGroups: { from: 'X', groups: [] } }).find('.nearby-groups').exists()).toBe(false)
})
