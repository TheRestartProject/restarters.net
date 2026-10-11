import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupActions from './GroupActions.vue'

const localVue = createLocalVue()
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
