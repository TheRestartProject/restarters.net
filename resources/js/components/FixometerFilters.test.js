import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import FixometerFilters from './FixometerFilters.vue'

// These pull in a typeahead that Jest can't load; the filters only need to place them.
jest.mock('./DeviceBrand.vue', () => ({ name: 'DeviceBrand', template: '<div class="brand-stub" />' }))
jest.mock('./DeviceCategorySelect.vue', () => ({
  name: 'DeviceCategorySelect',
  props: ['iconVariant'],
  template: '<div class="category-stub" />',
}))

const localVue = createLocalVue()
localVue.mixin(LangMixin)

function makeWrapper(props = {}) {
  return mount(FixometerFilters, {
    localVue,
    propsData: { clusters: [], brands: [], startExpandedItems: true, ...props },
    stubs: {
      multiselect: {
        props: ['value', 'options'],
        template: '<div class="multiselect-stub">{{ value && value.text }}</div>',
      },
    },
  })
}

function labels(wrapper) {
  return wrapper.findAll('#collapse-item legend, #collapse-item label').wrappers
    .map(l => l.text())
    .filter(t => t.startsWith('devices.'))
}

test('item filters are in the order people look for things', () => {
  expect(labels(makeWrapper()).slice(0, 7)).toEqual([
    'devices.powered_or_unpowered',
    'devices.category',
    'devices.model_or_type',
    'devices.brand',
    'devices.model',
    'devices.search_assessment_comments',
    'devices.repair_status',
  ])
})

test('powered or unpowered is a select defaulting to both', () => {
  const wrapper = makeWrapper()

  expect(wrapper.find('.powered-filter').text()).toBe('devices.both')
  expect(wrapper.find('input[type=radio]').exists()).toBe(false)
})

// Brand and model can be recorded for unpowered items too.
test('brand and model filters stay for unpowered items', () => {
  const wrapper = makeWrapper({ powered: false })

  expect(wrapper.findComponent({ name: 'DeviceBrand' }).exists()).toBe(true)
  expect(wrapper.find('.model-filter').exists()).toBe(true)
})

test('the item filter asks what it is, and the filters have no info icons', () => {
  const wrapper = makeWrapper()

  expect(wrapper.find('.item-filter input').attributes('placeholder')).toBe('devices.item_type')
  expect(wrapper.find('img.icon.clickable').exists()).toBe(false)
  expect(wrapper.findComponent({ name: 'DeviceCategorySelect' }).props('iconVariant')).toBe('none')
})
