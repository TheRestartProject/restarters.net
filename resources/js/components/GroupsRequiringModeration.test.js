import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupsRequiringModeration from './GroupsRequiringModeration.vue'

const localVue = createLocalVue()
localVue.use(Vuex)

function makeStore(moderate) {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: {
          getModerate: () => moderate || {},
        },
        actions: {
          getModerationRequired: () => Promise.resolve(),
        }
      }
    }
  })
}

async function flush(wrapper) {
  // mounted() awaits a store dispatch then calls $nextTick to flip `loaded`.
  // Wait long enough for both to settle.
  await new Promise(resolve => setTimeout(resolve, 0))
  await wrapper.vm.$nextTick()
  await wrapper.vm.$nextTick()
}

const groupsTableStub = {
  name: 'GroupsTable',
  // Declare approve as Boolean so Vue applies its boolean prop coercion
  // (presence-without-value becomes true) — matches the real component.
  props: { groupids: { type: Array }, approve: { type: Boolean, default: false } },
  template: '<div class="stub-groups-table" />'
}

test('renders GroupsTable (not literal TODO) when there are groups awaiting moderation', async () => {
  const store = makeStore({
    1: { idgroups: 1, name: 'G1', networks: [] },
    2: { idgroups: 2, name: 'G2', networks: [] },
  })
  const wrapper = mount(GroupsRequiringModeration, {
    localVue,
    store,
    mixins: [LangMixin],
    stubs: { GroupsTable: groupsTableStub },
  })

  await flush(wrapper)

  // Must not display the literal "TODO" placeholder
  expect(wrapper.text()).not.toContain('TODO')

  // Must render a GroupsTable with the moderation group ids and approve flag
  const stub = wrapper.findComponent(groupsTableStub)
  expect(stub.exists()).toBe(true)
  expect(stub.props('groupids')).toEqual([1, 2])
  expect(stub.props('approve')).toBe(true)
})

test('renders nothing when there are no groups to moderate', async () => {
  const store = makeStore({})
  const wrapper = mount(GroupsRequiringModeration, {
    localVue,
    store,
    mixins: [LangMixin],
    stubs: { GroupsTable: groupsTableStub },
  })

  await flush(wrapper)

  expect(wrapper.findComponent(groupsTableStub).exists()).toBe(false)
  expect(wrapper.text()).not.toContain('TODO')
})

test('filters groups by the networks prop when supplied', async () => {
  const store = makeStore({
    1: { idgroups: 1, name: 'In',   networks: [{ id: 10 }] },
    2: { idgroups: 2, name: 'Out',  networks: [{ id: 99 }] },
    3: { idgroups: 3, name: 'Also in', networks: [{ id: 10 }, { id: 20 }] },
  })
  const wrapper = mount(GroupsRequiringModeration, {
    localVue,
    store,
    mixins: [LangMixin],
    propsData: { networks: [10] },
    stubs: { GroupsTable: groupsTableStub },
  })

  await flush(wrapper)

  const stub = wrapper.findComponent(groupsTableStub)
  expect(stub.exists()).toBe(true)
  expect(stub.props('groupids').sort()).toEqual([1, 3])
})
