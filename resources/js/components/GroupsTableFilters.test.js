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

function placeholdersOf(wrapper) {
  return wrapper.findAll('.stub-multiselect').wrappers
    .map(w => w.attributes('data-placeholder'))
}

// Searching by place is what the map's own "Search for a place..." box is for,
// and country is a narrower version of the same thing. A network filter only
// makes sense to someone who already knows the networks, and the network pages
// scope the list themselves.
test('does not offer location, country or network filters', () => {
  const wrapper = mountFilters({ networks: [{ id: 1, name: 'Restarters' }], showTags: true, allGroupTags: [] })

  const placeholders = placeholdersOf(wrapper)
  expect(placeholders).not.toContain('networks.network')
  expect(placeholders).not.toContain('groups.search_country_placeholder')

  const inputPlaceholders = wrapper.findAll('b-form-input-stub').wrappers
    .map(w => w.attributes('placeholder'))
  expect(inputPlaceholders).not.toContain('groups.search_location_placeholder')
})

test('searches by name', () => {
  const inputs = mountFilters().findAll('b-form-input-stub')

  expect(inputs).toHaveLength(1)
  expect(inputs.at(0).attributes('placeholder')).toBe('groups.search_name_placeholder')
})

test('emits the name to filter on', async () => {
  const wrapper = mountFilters()

  wrapper.vm.searchName = 'Ulverston'
  await wrapper.vm.$nextTick()

  expect(wrapper.emitted('update:name').pop()).toEqual(['Ulverston'])
})

// Tags are only visible to admins and network coordinators.
test('offers the tag filter only to those who can see tags', () => {
  expect(placeholdersOf(mountFilters({ showTags: false }))).toEqual([])
  expect(placeholdersOf(mountFilters({ showTags: true, allGroupTags: [] })))
    .toEqual(['groups.search_tags_placeholder'])
})
