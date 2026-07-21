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
    yourGroups: { type: Array, default: () => [] },
    networks: { type: Array, default: null },
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
    network: { type: Number, default: null },
    showFilters: { type: Boolean, default: false },
    canManageTags: { type: Boolean, default: false },
    availableTags: { type: Array, default: () => [] },
    networks: { type: Array, default: null },
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
      networks: [{ id: 10, name: 'Test' }],
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

test('forwards showTags / networks / allGroupTags to the inner GroupsTable so tag badges render on the "your groups" tab', async () => {
  const networks = [{ id: 10, name: 'Test' }]
  const allGroupTags = [{ id: 1, tag_name: 'Foo' }, { id: 2, tag_name: 'Bar' }]
  const wrapper = makeWrapper({ showTags: true, networks, allGroupTags })
  await flushTabs(wrapper)

  const table = wrapper.findComponent(groupsTableStub)
  expect(table.exists()).toBe(true)
  expect(table.props('showTags')).toBe(true)
  expect(table.props('networks')).toEqual(networks)
  expect(table.props('allGroupTags')).toEqual(allGroupTags)
})

test('forwards yourArea (bound, not the literal string "yourArea") to GroupsTable', async () => {
  const wrapper = makeWrapper({ yourArea: 'London' })
  await flushTabs(wrapper)

  const table = wrapper.findComponent(groupsTableStub)
  expect(table.props('yourArea')).toBe('London')
})

// Regression: the Your Groups tab table wasn't given yourGroups, so yourGroup()
// was always false and every row showed "Follow group" for groups the user
// already follows.
test('forwards yourGroups to the Your Groups table so the follow button state is right', async () => {
  const wrapper = makeWrapper()
  await flushTabs(wrapper)

  const table = wrapper.findComponent(groupsTableStub)
  expect(table.props('yourGroups')).toEqual([1, 2])
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
  // On a network view the list is already scoped, so the network dropdown is
  // pointless — don't offer it.
  expect(map.props('networks')).toBeNull()
})

test('forwards yourArea to the map list so the place search is preloaded', async () => {
  const wrapper = makeWrapper({ tab: 'other', nearbyGroups: WORLD_BOUNDS, yourArea: 'Ulverston' })
  await flushTabs(wrapper)

  expect(wrapper.findComponent(groupMapStub).props('yourArea')).toBe('Ulverston')
})

test('passes the networks list for the network filter on the plain groups page', async () => {
  const networks = [{ id: 10, name: 'Test' }]
  const wrapper = makeWrapper({ tab: 'other', nearbyGroups: WORLD_BOUNDS, networks })
  await flushTabs(wrapper)

  const map = wrapper.findComponent(groupMapStub)
  expect(map.props('networks')).toEqual(networks)
  expect(map.props('showFilters')).toBe(true)
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
