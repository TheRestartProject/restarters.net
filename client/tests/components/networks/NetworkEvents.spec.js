import { flushPromises, mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkEvents from '../../../app/components/networks/NetworkEvents.vue'
import { BAlertStub, BButtonStub, BDropdownStub, BModalStub, NuxtLinkStub } from '../../helpers/stubs.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NOW = new Date('2038-02-10T12:00:00Z')

function event(id, overrides = {}) {
  return {
    id,
    title: `Event ${id}`,
    start: '2038-02-12T10:00:00Z',
    end: '2038-02-12T12:00:00Z',
    timezone: 'UTC',
    online: false,
    cancelled: false,
    group: { id: 10, name: 'Fixers United', image: null, approved: true, archived_at: null },
    ...overrides,
  }
}

let events
let wrapper

function mountIt() {
  wrapper = mount(NetworkEvents, {
    props: { networkId: 5 },
    attachTo: document.body,
    global: {
      plugins: [createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })],
      stubs: {
        NuxtLink: { ...NuxtLinkStub, template: '<a v-bind="$attrs" :href="to"><slot /></a>' },
        BAlert: BAlertStub,
        BButton: BButtonStub,
        BModal: BModalStub,
        BDropdown: BDropdownStub,
      },
    },
  })
  return wrapper
}

describe('components/networks/NetworkEvents', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] })
    vi.setSystemTime(NOW)
    window.localStorage.clear()
    events = vi.fn().mockResolvedValue({ data: [event(1), event(2, { start: '2038-02-20T10:00:00Z', end: '2038-02-20T12:00:00Z', group: { id: 11, name: 'Beta Repairers', approved: true, archived_at: null } })] })
    vi.stubGlobal('useNuxtApp', () => ({ $api: { network: { events } } }))
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'http://api.test' } }))
  })

  afterEach(() => {
    wrapper?.unmount()
    vi.useRealTimers()
    window.localStorage.clear()
  })

  it('shows the calendar by default', async () => {
    const w = mountIt()
    await flushPromises()

    expect(w.find('[data-testid="network-events-calendar"]').exists()).toBe(true)
    expect(w.find('[data-testid="network-events-list"]').exists()).toBe(false)
    expect(w.get('[data-testid="network-events-view-calendar"]').classes()).toContain('active')
    expect(w.get('[data-testid="network-events-month-title"]').text()).toBe('February 2038')
  })

  it('asks for the visible month, including unapproved events (the server decides who may see them)', async () => {
    mountIt()
    await flushPromises()

    expect(events).toHaveBeenCalledTimes(1)
    const [id, params] = events.mock.calls[0]
    expect(id).toBe(5)
    expect(params.include_unapproved).toBe(1)
    // The window covers the whole month, padded by a day each side.
    expect(params.start <= '2038-01-31T23:59:59.999Z').toBe(true)
    expect(params.end >= '2038-03-01T00:00:00.000Z').toBe(true)
    expect(params.end <= '2038-03-03T00:00:00.000Z').toBe(true)
  })

  it('refetches for the new month when paging', async () => {
    const w = mountIt()
    await flushPromises()

    await w.get('[data-testid="network-events-next"]').trigger('click')
    await flushPromises()

    expect(events).toHaveBeenCalledTimes(2)
    expect(w.get('[data-testid="network-events-month-title"]').text()).toBe('March 2038')
  })

  describe('view toggle', () => {
    it('switches to the list and remembers the choice', async () => {
      const w = mountIt()
      await flushPromises()

      await w.get('[data-testid="network-events-view-list"]').trigger('click')
      await flushPromises()

      expect(w.find('[data-testid="network-events-list"]').exists()).toBe(true)
      expect(w.find('[data-testid="network-events-calendar"]').exists()).toBe(false)
      expect(window.localStorage.getItem('networkEventsView')).toBe('list')
    })

    it('a remembered list choice wins over the calendar default', async () => {
      window.localStorage.setItem('networkEventsView', 'list')
      const w = mountIt()
      await flushPromises()

      expect(w.find('[data-testid="network-events-list"]').exists()).toBe(true)
      expect(w.get('[data-testid="network-events-view-list"]').classes()).toContain('active')
    })

    it('a remembered calendar choice, or junk, gives the calendar', async () => {
      window.localStorage.setItem('networkEventsView', 'nonsense')
      const w = mountIt()
      await flushPromises()

      expect(w.find('[data-testid="network-events-calendar"]').exists()).toBe(true)
    })

    it('still works when storage is unavailable', async () => {
      const spy = vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
        throw new Error('denied')
      })
      const set = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
        throw new Error('denied')
      })

      try {
        const w = mountIt()
        await flushPromises()
        expect(w.find('[data-testid="network-events-calendar"]').exists()).toBe(true)

        await w.get('[data-testid="network-events-view-list"]').trigger('click')
        expect(w.find('[data-testid="network-events-list"]').exists()).toBe(true)
      } finally {
        spy.mockRestore()
        set.mockRestore()
      }
    })
  })

  describe('list view filters', () => {
    async function listView() {
      window.localStorage.setItem('networkEventsView', 'list')
      const w = mountIt()
      await flushPromises()
      return w
    }

    it('asks for the next 30 days by default and hides events that have already finished', async () => {
      events.mockResolvedValue({ data: [event(1), event(3, { start: '2038-02-01T10:00:00Z', end: '2038-02-01T12:00:00Z' })] })
      const w = await listView()

      const params = events.mock.calls[0][1]
      expect(params.start).toBe(NOW.toISOString())
      expect(params.end).toBe(new Date('2038-03-12T12:00:00Z').toISOString())
      expect(w.find('[data-testid="network-event-row-1"]').exists()).toBe(true)
      expect(w.find('[data-testid="network-event-row-3"]').exists()).toBe(false)
    })

    it('includes past events when asked, and offers to clear that filter', async () => {
      events.mockResolvedValue({ data: [event(1), event(3, { start: '2038-02-01T10:00:00Z', end: '2038-02-01T12:00:00Z' })] })
      const w = await listView()
      expect(w.find('[data-testid="network-events-clear"]').exists()).toBe(false)

      await w.get('[data-testid="network-events-include-past"]').setValue(true)
      await flushPromises()

      expect(w.find('[data-testid="network-event-row-3"]').exists()).toBe(true)
      expect(events).toHaveBeenCalledTimes(2)
      await w.get('[data-testid="network-events-clear"]').trigger('click')
      await flushPromises()
      expect(w.find('[data-testid="network-event-row-3"]').exists()).toBe(false)
    })

    it('narrows the window to 7 days', async () => {
      const w = await listView()

      await w.get('[data-testid="network-events-range"]').setValue('7')
      await flushPromises()

      expect(events.mock.calls[1][1].end).toBe(new Date('2038-02-17T12:00:00Z').toISOString())
    })

    it('filters by group and clears the filter', async () => {
      const w = await listView()
      expect(w.find('[data-testid="network-event-row-2"]').exists()).toBe(true)

      await w.get('[data-testid="network-events-group-11"]').setValue(true)

      expect(w.find('[data-testid="network-event-row-1"]').exists()).toBe(false)
      expect(w.find('[data-testid="network-event-row-2"]').exists()).toBe(true)
      expect(w.get('[data-testid="network-events-count"]').text()).toBe('1 event')

      await w.get('[data-testid="network-events-clear"]').trigger('click')
      expect(w.find('[data-testid="network-event-row-1"]').exists()).toBe(true)
      expect(w.get('[data-testid="network-events-count"]').text()).toBe('2 events')
    })
  })

  it('reports a failure to load', async () => {
    events.mockRejectedValue({ status: 500 })
    const w = mountIt()
    await flushPromises()

    expect(w.get('[data-testid="network-events-error"]').text()).toBe('Could not load events.')
  })

  it('ignores a slow response that a later one has superseded', async () => {
    let resolveFirst
    events.mockImplementationOnce(() => new Promise((resolve) => { resolveFirst = resolve }))
    events.mockResolvedValueOnce({ data: [event(2, { start: '2038-03-05T10:00:00Z', end: '2038-03-05T12:00:00Z' })] })
    const w = mountIt()

    await w.get('[data-testid="network-events-next"]').trigger('click').catch(() => {})
    await flushPromises()
    resolveFirst({ data: [event(1)] })
    await flushPromises()

    expect(w.find('[data-testid="network-event-chip-1"]').exists()).toBe(false)
    expect(w.find('[data-testid="network-event-chip-2"]').exists()).toBe(true)
  })

  describe('subscribe', () => {
    it('shows the network iCal feed on the API origin to copy', async () => {
      const w = mountIt()
      await flushPromises()
      expect(w.find('[data-testid="network-events-subscribe-modal"]').exists()).toBe(false)

      await w.get('[data-testid="network-events-subscribe"]').trigger('click')

      expect(w.get('[data-testid="network-events-calendar-url"]').element.value).toBe('http://api.test/calendar/network/5')
    })
  })
})
