import Vue from "vue";
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupVolunteer from './GroupVolunteer.vue'

const localVue = createLocalVue()
localVue.use(Vuex)

const mockVolunteer = {
  id: 1,
  user: 42,
  name: 'Test Volunteer',
  host: false,
  image: '/images/placeholder-avatar.png',
  skills: [],
}

function makeStore() {
  return new Vuex.Store({
    modules: {
      volunteers: {
        namespaced: true,
        getters: {
          byIDGroup: () => () => mockVolunteer,
        }
      }
    }
  })
}

test('shows pencil icon in dropdown toggle when canedit is true', () => {
  const store = makeStore()
  const wrapper = mount(GroupVolunteer, {
    localVue,
    store,
    mixins: [LangMixin],
    propsData: {
      id: 1,
      canedit: true,
      candemote: false,
    },
    stubs: {
      ConfirmModal: { template: '<div />' },
    }
  })

  const dropdown = wrapper.find('.edit-dropdown')
  expect(dropdown.exists()).toBe(true)

  // The dropdown toggle should contain a pencil/edit icon image
  const icon = dropdown.find('img')
  expect(icon.exists()).toBe(true)
  expect(icon.attributes('src')).toContain('edit_ico_green.svg')
})

test('does not show dropdown when canedit is false', () => {
  const store = makeStore()
  const wrapper = mount(GroupVolunteer, {
    localVue,
    store,
    mixins: [LangMixin],
    propsData: {
      id: 1,
      canedit: false,
      candemote: false,
    },
    stubs: {
      ConfirmModal: { template: '<div />' },
    }
  })

  expect(wrapper.find('.edit-dropdown').exists()).toBe(false)
})
