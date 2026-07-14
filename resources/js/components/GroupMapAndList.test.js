import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupMapAndList from './GroupMapAndList.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

const GROUPS = [
  { id: 1, name: 'Alpha', networks: [{ id: 5, name: 'N5' }] },
  { id: 2, name: 'Beta', networks: [{ id: 6, name: 'N6' }] },
]

function makeStore() {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: {
          list: () => GROUPS,
        },
        actions: {
          list: () => Promise.resolve(),
        },
      },
    },
  })
}

const groupMapStub = {
  name: 'GroupMap',
  props: {
    initialBounds: { type: Array },
    network: { type: Number, default: null },
    yourGroups: { type: Array, default: () => [] },
    hover: { type: Number, default: null },
  },
  template: '<div class="stub-groupmap" />',
}

const groupsTableStub = {
  name: 'GroupsTable',
  props: {
    groupids: { type: Array },
    search: { type: Boolean, default: false },
    networks: { type: Array, default: null },
    allGroupTags: { type: Array, default: null },
    showTags: { type: Boolean, default: false },
    yourGroups: { type: Array, default: () => [] },
  },
  template: '<div class="stub-table" />',
}

async function makeWrapper(props = {}) {
  const wrapper = mount(GroupMapAndList, {
    localVue,
    store: makeStore(),
    propsData: {
      initialBounds: [[90, 180], [-90, -180]],
      ...props,
    },
    stubs: {
      GroupMap: groupMapStub,
      GroupsTable: groupsTableStub,
      'v-icon': true,
    },
  })

  // mounted() awaits the groups/list dispatch before clearing `loading`.
  await wrapper.vm.$nextTick()
  await wrapper.vm.$nextTick()
  await wrapper.vm.$nextTick()

  return wrapper
}

test('forwards network and filter context to the map and the table', async () => {
  const networks = [{ id: 5, name: 'N5' }]
  const tags = [{ id: 1, tag_name: 'Foo' }]
  const wrapper = await makeWrapper({
    network: 5,
    showFilters: true,
    networks,
    availableTags: tags,
    canManageTags: true,
  })

  expect(wrapper.findComponent(groupMapStub).props('network')).toBe(5)

  const table = wrapper.findComponent(groupsTableStub)
  expect(table.props('search')).toBe(true)
  expect(table.props('networks')).toEqual(networks)
  expect(table.props('allGroupTags')).toEqual(tags)
  expect(table.props('showTags')).toBe(true)
})

describe('effectiveGroupIds', () => {
  test('falls back to the full (network-filtered) list before the map has reported', async () => {
    const wrapper = await makeWrapper({ network: 5 })
    expect(wrapper.vm.effectiveGroupIds).toEqual([1])
  })

  // Regression: an empty array was treated the same as "no report yet", so
  // panning to an area with no groups listed EVERY group under the map.
  test('shows an empty list when the map reports no groups in view', async () => {
    const wrapper = await makeWrapper()
    wrapper.vm.groupsChanged([])
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.effectiveGroupIds).toEqual([])
  })

  test('shows exactly the groups the map reports in view', async () => {
    const wrapper = await makeWrapper()
    wrapper.vm.groupsChanged([2])
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.effectiveGroupIds).toEqual([2])
  })
})

// Neil's PR feedback: pin hover flows back up from the map and down into the
// table, so the matching row highlights.
test('map update:hover lands in the table hover prop', async () => {
  const wrapper = await makeWrapper()
  wrapper.findComponent({ name: 'GroupMap' }).vm.$emit('update:hover', 42)
  await wrapper.vm.$nextTick()
  expect(wrapper.vm.hover).toBe(42)

  wrapper.findComponent({ name: 'GroupMap' }).vm.$emit('update:hover', null)
  await wrapper.vm.$nextTick()
  expect(wrapper.vm.hover).toBe(null)
})
