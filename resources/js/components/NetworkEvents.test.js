import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import axios from 'axios'
import LangMixin from 'resources/js/mixins/lang.js'
import NetworkEvents from './NetworkEvents.vue'

jest.mock('axios')

const localVue = createLocalVue()
localVue.mixin(LangMixin)

function ev(id, groupId, groupName, start, extra = {}) {
  return {
    id,
    title: 'Venue ' + id,
    start,
    end: start,
    timezone: 'Europe/London',
    online: false,
    cancelled: false,
    group: { id: groupId, name: groupName },
    ...extra
  }
}

const future = n => new Date(Date.now() + n * 86400000).toISOString()

function makeWrapper() {
  return mount(NetworkEvents, {
    localVue,
    propsData: { networkId: 5 },
    stubs: { CalendarAddModal: true }
  })
}

const flush = () => new Promise(resolve => setTimeout(resolve, 0))

beforeEach(() => {
  window.localStorage.clear()
  axios.get.mockResolvedValue({
    data: {
      data: [
        ev(1, 1, 'Alpha Group', future(2)),
        ev(2, 2, 'Beta Group', future(2)),
        ev(3, 1, 'Alpha Group', future(4))
      ]
    }
  })
})

test('fetches the network events and renders the list by default', async () => {
  const wrapper = makeWrapper()
  await flush()

  expect(axios.get).toHaveBeenCalledWith('/api/v2/networks/5/events', expect.anything())
  expect(wrapper.findAll('.event-row')).toHaveLength(3)
})

test('toggling to calendar shows the grid and remembers the choice', async () => {
  const wrapper = makeWrapper()
  await flush()

  await wrapper.find('.view-calendar').trigger('click')
  await flush()

  expect(wrapper.find('table.calendar-grid').exists()).toBe(true)
  expect(window.localStorage.getItem('networkEventsView')).toBe('calendar')
  expect(wrapper.find('.view-calendar').attributes('aria-pressed')).toBe('true')
})

test('flags a same-day clash between different groups', async () => {
  const wrapper = makeWrapper()
  await flush()

  // Events 1 and 2 are on the same day for different groups.
  expect(wrapper.findAll('.clash-badge').length).toBeGreaterThanOrEqual(2)
})

test('group filter narrows the events and can be cleared', async () => {
  const wrapper = makeWrapper()
  await flush()

  wrapper.vm.groupIds = [2]
  await wrapper.vm.$nextTick()
  expect(wrapper.findAll('.event-row')).toHaveLength(1)

  await wrapper.find('.clear-filters').trigger('click')
  expect(wrapper.findAll('.event-row')).toHaveLength(3)
})
