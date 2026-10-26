import Vue from 'vue'
import Vuex from 'vuex'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, shallowMount, createLocalVue } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupActions from './GroupActions.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

function makeWrapper(props = {}, canedit = true) {
  return mount(GroupActions, {
    localVue,
    propsData: {
      idgroups: 3,
      ...props,
    },
    computed: {
      canedit: () => canedit,
      ingroup: () => true,
    },
    mocks: {
      $store: { getters: { 'groups/get': () => ({ id: 3, name: 'Test', archived_at: null }) } },
    },
    stubs: { ConfirmModal: true },
  })
}

test.each([true, false])('offers the group reports in the actions menu (canedit %s)', (canedit) => {
  const wrapper = makeWrapper({ reportingUrl: 'https://reports.example.org/d/3?group_id=3' }, canedit)

  const link = wrapper.find('a.group-reporting-action')
  expect(link.exists()).toBe(true)
  expect(link.attributes('href')).toBe('https://reports.example.org/d/3?group_id=3')
})

test('does not offer reports when there is no link', () => {
  const wrapper = makeWrapper()

  expect(wrapper.find('a.group-reporting-action').exists()).toBe(false)
})

function makeStore (edit, fetch) {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: {
          // The store's copy of a group, including object-valued fields.
          get: () => (id) => ({
            idgroups: id,
            name: 'Test Group',
            free_text: 'About us',
            network_data: { foo: 'bar' },
            networks: [{ id: 1 }],
          }),
        },
        actions: { edit, fetch },
      },
    },
  })
}

test('archiving sends only the group id and archive date', async () => {
  const edit = jest.fn()
  const wrapper = shallowMount(GroupActions, {
    localVue,
    store: makeStore(edit, jest.fn()),
    propsData: { idgroups: 42, canPerformArchive: true },
  })

  await wrapper.vm.archiveConfirmed()

  expect(edit).toHaveBeenCalledTimes(1)
  const payload = edit.mock.calls[0][1]
  expect(Object.keys(payload).sort()).toEqual(['archived_at', 'id'])
  expect(payload.id).toBe(42)
  expect(isNaN(Date.parse(payload.archived_at))).toBe(false)
})
