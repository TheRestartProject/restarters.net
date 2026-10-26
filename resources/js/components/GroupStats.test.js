import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupStats from './GroupStats.vue'

const localVue = createLocalVue()
localVue.mixin(LangMixin)

function makeWrapper(props = {}) {
  return mount(GroupStats, {
    localVue,
    propsData: {
      idgroups: 3,
      stats: {},
      deviceStats: { fixed: 1, repairable: 2, dead: 3 },
      topDevices: [],
      ...props,
    },
    mocks: {
      $store: { getters: { 'groups/get': () => ({ id: 3, name: 'Test' }) } },
    },
    stubs: {
      GroupStatsFacts: { template: '<div class="stub-facts" />' },
      StatsImpact: { template: '<div class="stub-impact" />' },
      GroupDevicesWorkedOn: { template: '<div class="stub-worked-on" />' },
      GroupDevicesMostRepaired: { template: '<div class="stub-most-repaired" />' },
    },
  })
}

test('shows items worked on and most repaired items alongside the group achievements', () => {
  const wrapper = makeWrapper()

  expect(wrapper.find('.stub-facts').exists()).toBe(true)
  expect(wrapper.find('.stub-impact').exists()).toBe(true)
  expect(wrapper.find('.stub-worked-on').exists()).toBe(true)
  expect(wrapper.find('.stub-most-repaired').exists()).toBe(true)
})

test('links to the group reports when there are some', () => {
  const wrapper = makeWrapper({ reportingUrl: 'https://reports.example.org/d/3?group_id=3' })

  const link = wrapper.find('a.group-reporting-link')
  expect(link.exists()).toBe(true)
  expect(link.attributes('href')).toBe('https://reports.example.org/d/3?group_id=3')
})

test('has no reports link otherwise', () => {
  const wrapper = makeWrapper()

  expect(wrapper.find('a.group-reporting-link').exists()).toBe(false)
})

test('can show just the headline figures', () => {
  const wrapper = makeWrapper({ part: 'headline', reportingUrl: 'https://reports.example.org/d/3' })

  expect(wrapper.find('.stub-facts').exists()).toBe(true)
  expect(wrapper.find('.stub-worked-on').exists()).toBe(false)
  // The reports link goes with the items, wherever they are.
  expect(wrapper.find('a.group-reporting-link').exists()).toBe(false)
})

test('can show just the items', () => {
  const wrapper = makeWrapper({ part: 'items', reportingUrl: 'https://reports.example.org/d/3' })

  expect(wrapper.find('.stub-facts').exists()).toBe(false)
  expect(wrapper.find('.stub-most-repaired').exists()).toBe(true)
  expect(wrapper.find('a.group-reporting-link').exists()).toBe(true)
})

test('the compact arrangement leaves out the note on what the impact excludes', () => {
  const wrapper = mount(GroupStats, {
    localVue,
    propsData: { idgroups: 3, stats: {}, deviceStats: {}, topDevices: [], compact: true },
    stubs: {
      GroupStatsFacts: true,
      StatsImpact: { name: 'StatsImpact', props: ['showNotIncluded'], template: '<div />' },
      GroupDevicesWorkedOn: true,
      GroupDevicesMostRepaired: true,
    },
  })

  expect(wrapper.find('.stats-compact').exists()).toBe(true)
  expect(wrapper.findComponent({ name: 'StatsImpact' }).props('showNotIncluded')).toBe(false)
})
