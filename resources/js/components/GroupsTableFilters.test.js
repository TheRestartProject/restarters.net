import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { shallowMount, createLocalVue } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupsTableFilters from './GroupsTableFilters.vue'

const localVue = createLocalVue()
localVue.mixin(LangMixin)

const multiselectStub = {
  name: 'multiselect',
  props: { placeholder: { type: String, default: '' } },
  template: '<div class="stub-multiselect" :data-placeholder="placeholder" />',
}

function mountFilters(props = {}) {
  return shallowMount(GroupsTableFilters, {
    localVue,
    propsData: { groups: [], ...props },
    stubs: { multiselect: multiselectStub },
  })
}

// On a network-scoped view the list is already filtered to one network, so an
// empty network dropdown is just confusing — hide it when there are no
// networks to choose from.
test('hides the network dropdown when no networks are supplied', () => {
  const wrapper = mountFilters({ networks: null })
  const placeholders = wrapper.findAll('.stub-multiselect').wrappers
    .map(w => w.attributes('data-placeholder'))
  expect(placeholders).not.toContain('networks.network')
})

test('shows the network dropdown when networks are supplied', () => {
  const wrapper = mountFilters({ networks: [{ id: 1, name: 'Restarters' }] })
  const placeholders = wrapper.findAll('.stub-multiselect').wrappers
    .map(w => w.attributes('data-placeholder'))
  expect(placeholders).toContain('networks.network')
})
