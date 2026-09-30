import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import FixometerGlobalImpact from './FixometerGlobalImpact.vue'

const localVue = createLocalVue()
localVue.mixin(LangMixin)

function makeWrapper() {
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
      },
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
