import Vue from "vue";
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupWebsite from './GroupWebsite.vue'

test('renders the help text and no error when hasError is false', () => {
  const wrapper = mount(GroupWebsite, {
    mixins: [LangMixin],
    propsData: { website: null, hasError: false },
  })

  expect(wrapper.find('.group-website-error').exists()).toBe(false)
  // hasError class should not be present on the input
  expect(wrapper.find('input').classes()).not.toContain('hasError')
})

test('renders an inline error message and hasError styling when hasError is true', () => {
  const wrapper = mount(GroupWebsite, {
    mixins: [LangMixin],
    propsData: { website: 'EDP', hasError: true },
  })

  const error = wrapper.find('.group-website-error')
  expect(error.exists()).toBe(true)
  expect(error.text().length).toBeGreaterThan(0)
  expect(wrapper.find('input').classes()).toContain('hasError')
})
