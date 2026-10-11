import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import FixometerGlobalImpact from './FixometerGlobalImpact.vue'

const localVue = createLocalVue()
localVue.mixin(LangMixin)

function makeWrapper(props = {}) {
  return mount(FixometerGlobalImpact, {
    localVue,
    propsData: {
      latestData: {},
      impactData: {
        waste_total: 10000,
        co2_total: 20000,
        participants: 300,
        hours_volunteered: 8766 * 2,
        fixed_powered: 40,
        fixed_unpowered: 50,
        events: 1234,
        total_items: 345,
      },
      ...props,
    },
    methods: {
      // Needs pluralised translations, which the lang mock doesn't provide.
      equivalent_consumer: () => '',
    },
    stubs: {
      FixometerLatestData: true,
      StatsValue: {
        props: ['count', 'title'],
        template: '<div class="stats-value" :data-title="title">{{ count }}</div>',
      },
    },
  })
}

function stat(wrapper, title) {
  return wrapper.find(`.stats-value[data-title="${title}"]`)
}

test('shows the number of events held', () => {
  const wrapper = makeWrapper()

  expect(stat(wrapper, 'devices.events_held').text()).toBe('1234')
})

test('makes clear the powered and unpowered figures are items fixed', () => {
  const wrapper = makeWrapper()

  expect(stat(wrapper, 'devices.powered_items_fixed').text()).toBe('40')
  expect(stat(wrapper, 'devices.unpowered_items_fixed').text()).toBe('50')
})

test('labels volunteering as years of time volunteered', () => {
  const wrapper = makeWrapper()

  expect(stat(wrapper, 'groups.years_of_time_volunteered').text()).toBe('2')
})

test('shows the total number of items seen', () => {
  const wrapper = makeWrapper()

  expect(stat(wrapper, 'devices.items_seen').text()).toBe('345')
})

// Some installs (e.g. iFixit) have no further reports, so the sentence only
// appears when there's somewhere to send people.
test('links to further reports when there are some', () => {
  const wrapper = makeWrapper({ reportingUrl: 'https://reports.example.org/d/1' })

  const link = wrapper.find('a.fixometer-reporting-link')
  expect(link.exists()).toBe(true)
  expect(link.attributes('href')).toBe('https://reports.example.org/d/1')
  expect(link.attributes('target')).toBe('_blank')
})

test('has no further reports sentence without a reports address', () => {
  const wrapper = makeWrapper()

  expect(wrapper.find('a.fixometer-reporting-link').exists()).toBe(false)
  expect(wrapper.text()).not.toContain('partials.see_further_reports')
})
