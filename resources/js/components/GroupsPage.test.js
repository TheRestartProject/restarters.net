import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupsPage from './GroupsPage.vue'

const localVue = createLocalVue()
localVue.use(Vuex)

function makeStore() {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: { list: () => [] },
        actions: { fetch: () => Promise.resolve() },
      },
    },
  })
}

const groupsTableStub = {
  name: 'GroupsTable',
  props: {
    groupids: { type: Array },
    tab: { type: Number, default: 0 },
    yourArea: { type: String, default: null },
    allGroupTags: { type: Array, default: null },
    showTags: { type: Boolean, default: false },
  },
  template: '<div class="stub-groups-table" />',
}

const groupMapStub = {
  name: 'GroupMapAndList',
  props: {
    initialBounds: { type: Array },
    yourGroups: { type: Array, default: () => [] },
    yourArea: { type: String, default: '' },
    yourLat: { type: Number, default: null },
    yourLng: { type: Number, default: null },
    network: { type: Number, default: null },
    showFilters: { type: Boolean, default: false },
    canManageTags: { type: Boolean, default: false },
    availableTags: { type: Array, default: () => [] },
  },
  template: '<div class="stub-map" />',
}

function makeWrapper(props = {}) {
  return mount(GroupsPage, {
    localVue,
    store: makeStore(),
    mixins: [LangMixin],
    propsData: {
      yourGroups: [1, 2],
      nearbyGroups: [],
      allGroupTags: [{ id: 1, tag_name: 'Foo' }],
      ...props,
    },
    stubs: { GroupsTable: groupsTableStub, GroupMapAndList: groupMapStub },
  })
}

async function flushTabs(wrapper) {
  // b-tab has `lazy`, so the tab's content only renders after the tab activates
  // on the first Vue tick.
  await wrapper.vm.$nextTick()
  await wrapper.vm.$nextTick()
}

test('forwards showTags / allGroupTags to the inner GroupsTable so tag badges render on the "your groups" tab', async () => {
  const allGroupTags = [{ id: 1, tag_name: 'Foo' }, { id: 2, tag_name: 'Bar' }]
  const wrapper = makeWrapper({ showTags: true, allGroupTags })
  await flushTabs(wrapper)

  const table = wrapper.findComponent(groupsTableStub)
  expect(table.exists()).toBe(true)
  expect(table.props('showTags')).toBe(true)
  expect(table.props('allGroupTags')).toEqual(allGroupTags)
})

test('forwards yourArea (bound, not the literal string "yourArea") to GroupsTable', async () => {
  const wrapper = makeWrapper({ yourArea: 'London' })
  await flushTabs(wrapper)

  const table = wrapper.findComponent(groupsTableStub)
  expect(table.props('yourArea')).toBe('London')
})

const WORLD_BOUNDS = [[90, 180], [-90, -180]]

test('forwards network + filter context to the map list on a network view', async () => {
  const wrapper = makeWrapper({
    tab: 'other',
    network: 99,
    nearbyGroups: WORLD_BOUNDS,
    showTags: true,
  })
  await flushTabs(wrapper)

  const map = wrapper.findComponent(groupMapStub)
  expect(map.exists()).toBe(true)
  // Regression: /group/network/{id} showed ALL groups because the network
  // prop stopped at GroupsPage.
  expect(map.props('network')).toBe(99)
  expect(map.props('showFilters')).toBe(true)
  expect(map.props('canManageTags')).toBe(true)
  expect(map.props('availableTags')).toEqual([{ id: 1, tag_name: 'Foo' }])
})

test('forwards yourArea to the map list so the place search is preloaded', async () => {
  const wrapper = makeWrapper({ tab: 'other', nearbyGroups: WORLD_BOUNDS, yourArea: 'Ulverston' })
  await flushTabs(wrapper)

  expect(wrapper.findComponent(groupMapStub).props('yourArea')).toBe('Ulverston')
})

test('forwards the user\'s own coordinates to the map list', async () => {
  const wrapper = makeWrapper({ tab: 'other', nearbyGroups: WORLD_BOUNDS, yourLat: 54.19, yourLng: -3.09 })
  await flushTabs(wrapper)

  const map = wrapper.findComponent(groupMapStub)
  expect(map.props('yourLat')).toBe(54.19)
  expect(map.props('yourLng')).toBe(-3.09)
})

// Regression: both tabs were plain `lazy`, which destroys content when the tab
// is hidden — every switch back to Other Groups threw the map away and
// re-fetched everything.
test('keeps the map mounted when switching back to Your Groups', async () => {
  const wrapper = makeWrapper({ tab: 'other', nearbyGroups: WORLD_BOUNDS })
  await flushTabs(wrapper)
  expect(wrapper.find('.stub-map').exists()).toBe(true)

  wrapper.vm.currentTab = 0
  await flushTabs(wrapper)
  expect(wrapper.find('.stub-map').exists()).toBe(true)
})
