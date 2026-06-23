import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupsTable from './GroupsTable.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

function makeStore(groups) {
  const list = {}
  groups.forEach(g => { list[g.id] = g })
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: {
          list: () => Object.values(list),
          get: () => id => list[id],
        },
        actions: {
          fetch: () => Promise.resolve(),
        },
      },
    },
  })
}

const group = {
  id: 1,
  name: 'Test Group',
  hosts: 5,
  restarters: 12,
  location: { location: 'Townsville', country: 'United Kingdom', distance: null },
  image: null,
}

function mountTable() {
  return mount(GroupsTable, {
    localVue,
    store: makeStore([group]),
    propsData: { groupids: [1] },
    stubs: {
      GroupsTableFilters: true,
      ConfirmModal: true,
      GroupArchivedBadge: true,
      InfiniteLoading: true,
    },
  })
}

describe('GroupsTable column headers', () => {
  // The hosts/restarters columns should show icons in the header, like the
  // location and next-event columns do. Regression: the header-slot names did
  // not match the field keys, so b-table fell back to the text labels
  // "Hosts" / "Restarters".
  test('renders the hosts column header as the user icon, not text', () => {
    const thead = mountTable().find('thead').html()
    expect(thead).toContain('user_ico')
  })

  test('renders the restarters column header as the volunteer icon, not text', () => {
    const thead = mountTable().find('thead').html()
    expect(thead).toContain('volunteer_ico-thick')
  })
})
