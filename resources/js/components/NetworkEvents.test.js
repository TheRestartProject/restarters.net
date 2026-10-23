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

function makeWrapper(props = {}) {
  return mount(NetworkEvents, {
    localVue,
    propsData: { networkId: 5, ...props },
    stubs: { CalendarAddModal: true }
  })
}

const flush = () => new Promise(resolve => setTimeout(resolve, 0))

beforeEach(() => {
  window.localStorage.clear()
  axios.get.mockClear()
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

test('asks for unapproved and archived groups, with the token, so coordinators see them', async () => {
  makeWrapper({ apiToken: 'tok' })
  await flush()

  const params = axios.get.mock.calls[0][1].params
  expect(params.include_unapproved).toBe(1)
  expect(params.api_token).toBe('tok')
})

test('does not send a token it does not have', async () => {
  makeWrapper()
  await flush()

  expect(axios.get.mock.calls[0][1].params).not.toHaveProperty('api_token')
})

test('badges events on unapproved and archived groups, and has no clash badge', async () => {
  axios.get.mockResolvedValue({
    data: {
      data: [
        ev(1, 1, 'Alpha Group', future(2), { group: { id: 1, name: 'Alpha Group', approved: false, archived_at: null } }),
        ev(2, 2, 'Beta Group', future(2), { group: { id: 2, name: 'Beta Group', approved: true, archived_at: '2020-01-01T00:00:00+00:00' } }),
        ev(3, 3, 'Gamma Group', future(2), { group: { id: 3, name: 'Gamma Group', approved: true, archived_at: null } })
      ]
    }
  })
  const wrapper = makeWrapper()
  await flush()

  expect(wrapper.findAll('.unapproved-badge')).toHaveLength(1)
  expect(wrapper.findAll('.archived-badge')).toHaveLength(1)
  expect(wrapper.find('.clash-badge').exists()).toBe(false)
})

test('cancelled events are struck through in the list', async () => {
  axios.get.mockResolvedValue({ data: { data: [ev(1, 1, 'Alpha Group', future(2), { cancelled: true })] } })
  const wrapper = makeWrapper()
  await flush()

  expect(wrapper.find('.event-row').classes()).toContain('cancelled')
})

test('past events are only shown on request', async () => {
  axios.get.mockResolvedValue({
    data: { data: [ev(1, 1, 'Alpha Group', future(-3)), ev(2, 1, 'Alpha Group', future(3))] }
  })
  const wrapper = makeWrapper()
  await flush()

  expect(wrapper.findAll('.event-row')).toHaveLength(1)

  wrapper.vm.includePast = true
  await flush()
  expect(wrapper.findAll('.event-row')).toHaveLength(2)
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
