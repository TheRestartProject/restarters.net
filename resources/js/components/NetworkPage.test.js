import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import NetworkPage from './NetworkPage.vue'

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
