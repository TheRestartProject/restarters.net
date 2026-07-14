import Vue from 'vue'
import Vuex from 'vuex'
import { BootstrapVue } from 'bootstrap-vue'
import { createLocalVue, shallowMount } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupInfoModal from './GroupInfoModal.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
Vue.use(BootstrapVue)

function makeStore () {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: {
          get: () => (id) => ({
            id,
            name: 'Test Group',
            image: null,
            location: { location: 'Townsville' },
            next_event: null,
          }),
        },
      },
    },
  })
}

// Neil's PR feedback: the logo and name should navigate to the group, not
// just the "Go to group" button.
test('modal title logo and name link to the group page', () => {
  const wrapper = shallowMount(GroupInfoModal, {
    localVue,
    mixins: [LangMixin],
    store: makeStore(),
    propsData: { id: 7 },
    stubs: { 'b-modal': { template: '<div><slot name="modal-title" /></div>' } },
  })

  const link = wrapper.find('a.group-link')
  expect(link.exists()).toBe(true)
  expect(link.attributes('href')).toBe('/group/view/7')
  expect(link.text()).toContain('Test Group')
})
