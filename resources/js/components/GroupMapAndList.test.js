import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupMapAndList from './GroupMapAndList.vue'
import { indexGroup, hydratedGroup } from '../testFixtures/groups'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

// The shape the map is actually drawn from - see testFixtures/groups.js.
const GROUPS = [
  indexGroup({ id: 1, name: 'Alpha', networks: [5] }),
  indexGroup({ id: 2, name: 'Beta', networks: [6] }),
]

// The summary shape, which the same components see once rows are hydrated.
const HYDRATED_GROUPS = [
  hydratedGroup({ id: 1, name: 'Alpha', networks: [{ id: 5 }] }),
  hydratedGroup({ id: 2, name: 'Beta', networks: [{ id: 6 }] }),
]

function makeStore(groups = GROUPS) {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: {
          list: () => groups,
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
    yourArea: { type: String, default: '' },
    yourLat: { type: Number, default: null },
    yourLng: { type: Number, default: null },
    hover: { type: Number, default: null },
    groupids: { type: Array, default: null },
    frameRequest: { type: Number, default: 0 },
  },
  template: '<div class="stub-groupmap" />',
}

const groupsTableStub = {
  name: 'GroupsTable',
  props: {
    groupids: { type: Array },
    search: { type: Boolean, default: false },
    allGroupTags: { type: Array, default: null },
    showTags: { type: Boolean, default: false },
    centre: { type: Object, default: null },
  },
  template: '<div class="stub-table" />',
}

async function makeWrapper(props = {}, groups = GROUPS) {
  const wrapper = mount(GroupMapAndList, {
    localVue,
    store: makeStore(groups),
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
  const tags = [{ id: 1, tag_name: 'Foo' }]
  const wrapper = await makeWrapper({
    network: 5,
    showFilters: true,
    availableTags: tags,
    canManageTags: true,
  })

  expect(wrapper.findComponent(groupMapStub).props('network')).toBe(5)

  const table = wrapper.findComponent(groupsTableStub)
  expect(table.props('search')).toBe(true)
  expect(table.props('allGroupTags')).toEqual(tags)
  expect(table.props('showTags')).toBe(true)
})

// The search box is preloaded with the user's area, so the town has to survive
// the whole way down from the page to the map.
test('forwards yourArea to the map so the search box can be preloaded', async () => {
  const wrapper = await makeWrapper({ yourArea: 'Ulverston' })

  expect(wrapper.findComponent(groupMapStub).props('yourArea')).toBe('Ulverston')
})

// The map only zooms to the groups nearest the user if it knows where they are.
test('forwards the user\'s own coordinates to the map', async () => {
  const wrapper = await makeWrapper({ yourLat: 54.19, yourLng: -3.09 })

  const map = wrapper.findComponent(groupMapStub)
  expect(map.props('yourLat')).toBe(54.19)
  expect(map.props('yourLng')).toBe(-3.09)
})

// The list is ordered by distance from the middle of the map, so it has to know
// where that is, and follow it as the map moves.
test('passes the map centre through to the table', async () => {
  const wrapper = await makeWrapper()

  wrapper.findComponent({ name: 'GroupMap' }).vm.$emit('update:centre', { lat: 54.19, lng: -3.09 })
  await wrapper.vm.$nextTick()

  expect(wrapper.findComponent(groupsTableStub).props('centre')).toEqual({ lat: 54.19, lng: -3.09 })
})

// Filtering used to change only the list, so the map still showed every pin and
// the count still claimed every group. Searching or filtering has to move the
// map too, or the two disagree about what you're looking at.
describe('filters drive the map', () => {
  test('the map only gets the groups that match the filter', async () => {
    const wrapper = await makeWrapper({ showFilters: true })

    wrapper.findComponent({ name: 'GroupsTable' }).vm.$emit('update:filters', { name: 'Alpha' })
    await wrapper.vm.$nextTick()

    expect(wrapper.findComponent(groupMapStub).props('groupids')).toEqual([1])
  })

  test('the list is narrowed to the filtered groups even when the map has more in view', async () => {
    const wrapper = await makeWrapper({ showFilters: true })
    wrapper.vm.groupsChanged([1, 2])

    wrapper.findComponent({ name: 'GroupsTable' }).vm.$emit('update:filters', { name: 'Beta' })
    await wrapper.vm.$nextTick()

    expect(wrapper.vm.effectiveGroupIds).toEqual([2])
  })

  test('clearing the filter puts every group back on the map', async () => {
    const wrapper = await makeWrapper({ showFilters: true })

    wrapper.findComponent({ name: 'GroupsTable' }).vm.$emit('update:filters', { name: 'Alpha' })
    await wrapper.vm.$nextTick()
    wrapper.findComponent({ name: 'GroupsTable' }).vm.$emit('update:filters', { name: null })
    await wrapper.vm.$nextTick()

    expect(wrapper.findComponent(groupMapStub).props('groupids')).toEqual([1, 2])
  })
})

// Typing a name fires a filter change per keystroke. Narrowing the list and the
// pins each time is cheap and expected, but moving the viewport each time makes
// the map lurch around under the user while they are still typing.
describe('reframing waits for the typing to stop', () => {
  beforeEach(() => jest.useFakeTimers())
  afterEach(() => jest.useRealTimers())

  function type(wrapper, name) {
    wrapper.findComponent({ name: 'GroupsTable' }).vm.$emit('update:filters', { name })
  }

  test('does not move the map between keystrokes', async () => {
    const wrapper = await makeWrapper({ showFilters: true })
    const before = wrapper.findComponent(groupMapStub).props('frameRequest')

    type(wrapper, 'B')
    type(wrapper, 'Be')
    type(wrapper, 'Bet')
    await wrapper.vm.$nextTick()

    expect(wrapper.findComponent(groupMapStub).props('frameRequest')).toBe(before)
  })

  // This is also what takes you to a group that isn't currently on screen,
  // rather than reporting no results for something that is just off the edge.
  test('moves the map once, after the typing stops', async () => {
    const wrapper = await makeWrapper({ showFilters: true })
    const before = wrapper.findComponent(groupMapStub).props('frameRequest')

    type(wrapper, 'B')
    type(wrapper, 'Be')
    type(wrapper, 'Beta')
    jest.runAllTimers()
    await wrapper.vm.$nextTick()

    expect(wrapper.findComponent(groupMapStub).props('frameRequest')).toBe(before + 1)
  })

  // Only the viewport move waits: the list and the pins should keep up with
  // what's being typed.
  test('narrows the list and the pins straight away', async () => {
    const wrapper = await makeWrapper({ showFilters: true })

    type(wrapper, 'Beta')
    await wrapper.vm.$nextTick()

    expect(wrapper.findComponent(groupMapStub).props('groupids')).toEqual([2])
    expect(wrapper.vm.effectiveGroupIds).toEqual([2])
  })

  test('a pending reframe does not fire after the component goes away', async () => {
    const wrapper = await makeWrapper({ showFilters: true })

    type(wrapper, 'Beta')
    wrapper.destroy()

    expect(() => jest.runAllTimers()).not.toThrow()
  })
})

// Regression: the network pages showed an empty map and an empty list, because
// the scoping check only understood the summary API's {id} objects while the
// map is drawn from the names index, which sends plain ids.
describe('network scoping', () => {
  test('finds the network\'s groups when networks are plain ids', async () => {
    const wrapper = await makeWrapper({ network: 5 }, GROUPS)

    expect(wrapper.vm.matchingGroupIds).toEqual([1])
    expect(wrapper.findComponent(groupMapStub).props('groupids')).toEqual([1])
  })

  test('still finds them when networks are objects', async () => {
    const wrapper = await makeWrapper({ network: 5 }, HYDRATED_GROUPS)

    expect(wrapper.vm.matchingGroupIds).toEqual([1])
  })
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

// Pin hover flows back up from the map and down into the table, so the matching
// row highlights.
test('map update:hover lands in the table hover prop', async () => {
  const wrapper = await makeWrapper()
  wrapper.findComponent({ name: 'GroupMap' }).vm.$emit('update:hover', 42)
  await wrapper.vm.$nextTick()
  expect(wrapper.vm.hover).toBe(42)

  wrapper.findComponent({ name: 'GroupMap' }).vm.$emit('update:hover', null)
  await wrapper.vm.$nextTick()
  expect(wrapper.vm.hover).toBe(null)
})
