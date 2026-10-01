import Vue from 'vue'
import Vuex from 'vuex'
import { BootstrapVue } from 'bootstrap-vue'
import { createLocalVue, shallowMount } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupActions from './GroupActions.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
Vue.use(BootstrapVue)

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
    mixins: [LangMixin],
    propsData: { idgroups: 42, canPerformArchive: true },
  })

  await wrapper.vm.archiveConfirmed()

  expect(edit).toHaveBeenCalledTimes(1)
  const payload = edit.mock.calls[0][1]
  expect(Object.keys(payload).sort()).toEqual(['archived_at', 'id'])
  expect(payload.id).toBe(42)
  expect(isNaN(Date.parse(payload.archived_at))).toBe(false)
})
