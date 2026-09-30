import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import axios from 'axios'
import LangMixin from 'resources/js/mixins/lang.js'
import FixometerRecordsTable from './FixometerRecordsTable.vue'

jest.mock('axios')

// The real EventDevice pulls in editor components that Jest can't load; the table only needs to hand it the
// right record.
jest.mock('./EventDevice.vue', () => ({
  name: 'EventDevice',
  props: ['id', 'eventid', 'powered', 'edit'],
  template: '<div class="event-device-stub" />',
}))

const localVue = createLocalVue()
localVue.mixin(LangMixin)

const ITEMS = [
  {
    id: 17,
    eventid: 4,
    item_type: 'Kettle',
    brand: 'Acme',
    groupname: 'Alpha Group',
    repair_status: 'Fixed',
    short_problem: 'Would not boil',
    created_at: '2024-03-11T10:00:00+00:00',
    event_date: '2024-03-10T13:00:00+00:00',
    category: { id: 11, name: 'Kettle', powered: true },
  },
  {
    id: 18,
    eventid: 4,
    item_type: 'Trousers',
    brand: null,
    groupname: 'Alpha Group',
    repair_status: 'Repairable',
    short_problem: 'Torn',
    created_at: '2024-03-11T10:00:00+00:00',
    event_date: '2024-03-10T13:00:00+00:00',
    category: { id: 50, name: 'Misc', powered: false },
  },
]

const eventDeviceStub = { name: 'EventDevice' }

function flush() {
  return new Promise(resolve => setTimeout(resolve, 0))
}

async function makeWrapper(props = {}) {
  axios.get.mockResolvedValue({ data: { count: ITEMS.length, items: ITEMS } })

  const wrapper = mount(FixometerRecordsTable, {
    localVue,
    propsData: {
      ...props,
    },
    mocks: {
      $store: { dispatch: jest.fn().mockResolvedValue(null) },
    },
    stubs: {
      ConfirmModal: true,
    },
  })

  await flush()
  await flush()

  return wrapper
}

function lastParams() {
  const calls = axios.get.mock.calls
  return calls[calls.length - 1][1].params
}

beforeEach(() => {
  axios.get.mockReset()
})

test('lists powered and unpowered records together unless filtered', async () => {
  const wrapper = await makeWrapper()

  expect(lastParams().powered).toBeUndefined()
  expect(wrapper.text()).toContain('Kettle')
  expect(wrapper.text()).toContain('Trousers')
})

test('passes a powered filter through', async () => {
  await makeWrapper({ powered: false })

  expect(lastParams().powered).toBe(false)
})

test('asks the server to sort by the column the table is sorted by', async () => {
  const wrapper = await makeWrapper()

  wrapper.vm.items({ currentPage: 1, perPage: 20, sortBy: 'item_type', sortDesc: false }, () => {})
  expect(lastParams().sortBy).toBe('item_type')
  expect(lastParams().sortDesc).toBe('ASC')

  wrapper.vm.items({ currentPage: 1, perPage: 20, sortBy: 'groupname', sortDesc: true }, () => {})
  expect(lastParams().sortBy).toBe('groupname')
  expect(lastParams().sortDesc).toBe('DESC')
})

test('expanding a record shows that record, with its own powered state', async () => {
  const wrapper = await makeWrapper()

  const toggles = wrapper.findAll('.record-details-toggle')
  expect(toggles.length).toBe(2)

  await toggles.at(1).trigger('click')
  await flush()

  const device = wrapper.findComponent(eventDeviceStub)
  expect(device.exists()).toBe(true)
  expect(device.props('id')).toBe(18)
  expect(device.props('eventid')).toBe(4)
  expect(device.props('powered')).toBe(false)
})

test('shows the event date for each record', async () => {
  const wrapper = await makeWrapper()

  expect(wrapper.text()).toContain('10/03/2024')
})
