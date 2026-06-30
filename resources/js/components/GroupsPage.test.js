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
    networks: { type: Array, default: null },
    allGroupTags: { type: Array, default: null },
    showTags: { type: Boolean, default: false },
  },
  template: '<div class="stub-groups-table" />',
}

const groupMapStub = { name: 'GroupMapAndList', template: '<div class="stub-map" />' }

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
