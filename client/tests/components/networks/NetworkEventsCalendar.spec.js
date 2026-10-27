import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkEventsCalendar from '../../../app/components/networks/NetworkEventsCalendar.vue'
import { GROUP_COLOURS } from '../../../app/utils/networkEvents.js'
import { NuxtLinkStub } from '../../helpers/stubs.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkWithAttrs = { ...NuxtLinkStub, template: '<a v-bind="$attrs" :href="to"><slot /></a>' }

function event(id, overrides = {}) {
  return {
    id,
    title: `Event ${id}`,
    start: '2038-02-03T10:00:00Z',
    end: '2038-02-03T12:00:00Z',
    timezone: 'UTC',
    online: false,
    cancelled: false,
    group: { id: 10, name: 'Fixers United', image: null, approved: true, archived_at: null },
    ...overrides,
  }
}

function manyEvents(n) {
  return Array.from({ length: n }, (_, i) =>
    event(i + 1, { start: `2038-02-03T${String(8 + i).padStart(2, '0')}:00:00Z`, end: `2038-02-03T${String(9 + i).padStart(2, '0')}:00:00Z` })
  )
}

let wrapper

function mountIt(events, month = '2038-02') {
  wrapper = mount(NetworkEventsCalendar, {
    props: { events, month },
    attachTo: document.body,
    global: {
      plugins: [createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })],
      stubs: { NuxtLink: NuxtLinkWithAttrs },
    },
  })
  return wrapper
}

const DAY = '[data-testid="network-events-day-2038-02-03"]'

describe('components/networks/NetworkEventsCalendar', () => {
  beforeEach(() => {
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'http://api.test' } }))
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  it('renders a Monday-first month with its title and one cell per day', () => {
    const w = mountIt([])

    expect(w.get('[data-testid="network-events-month-title"]').text()).toBe('February 2038')
    expect(w.findAll('thead th').map((th) => th.text())).toEqual(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])
    expect(w.findAll('tbody td')).toHaveLength(28)
  })

  it('emits prev and next', async () => {
    const w = mountIt([])

    await w.get('[data-testid="network-events-prev"]').trigger('click')
    await w.get('[data-testid="network-events-next"]').trigger('click')

    expect(w.emitted('prev')).toHaveLength(1)
    expect(w.emitted('next')).toHaveLength(1)
  })

  it('labels each cell with its date and how many events it has', () => {
    const w = mountIt([event(1), event(2)])

    expect(w.get(DAY).attributes('aria-label')).toBe('3 February, 2 events')
    expect(w.get('[data-testid="network-events-day-2038-02-04"]').attributes('aria-label')).toBe('4 February, No events')
  })

  it('shows every event as a chip linking to it when there are 5 or fewer, with no "more"', () => {
    const w = mountIt(manyEvents(5))
    const chips = w.get(DAY).findAll('[data-testid^="network-event-chip-"]')

    expect(chips).toHaveLength(5)
    expect(chips[0].attributes('href')).toBe('/party/view/1')
    expect(w.find('[data-testid="network-events-more-2038-02-03"]').exists()).toBe(false)
  })

  it('shows 5 chips and "+N more" for the rest', () => {
    const w = mountIt(manyEvents(8))

    expect(w.get(DAY).findAll('[data-testid^="network-event-chip-"]')).toHaveLength(5)
    expect(w.get('[data-testid="network-events-more-2038-02-03"]').text()).toBe('+3 more')
  })

  describe('"+N more" popover', () => {
    it('lists all of that day\'s events, each linking through to the event', async () => {
      const w = mountIt(manyEvents(8))
      expect(w.find('[data-testid="network-events-popover-2038-02-03"]').exists()).toBe(false)

      await w.get('[data-testid="network-events-more-2038-02-03"]').trigger('click')

      const popover = w.get('[data-testid="network-events-popover-2038-02-03"]')
      expect(popover.attributes('aria-label')).toBe('Events on Wednesday 3 February')
      const links = popover.findAll('[data-testid^="network-event-chip-"]')
      expect(links.map((l) => l.attributes('href'))).toEqual(Array.from({ length: 8 }, (_, i) => `/party/view/${i + 1}`))
    })

    it('is anchored to the day cell, and only one is open at a time', async () => {
      const events = [...manyEvents(6), event(20, { start: '2038-02-04T10:00:00Z', end: '2038-02-04T11:00:00Z' }), ...Array.from({ length: 5 }, (_, i) => event(30 + i, { start: '2038-02-04T12:00:00Z', end: '2038-02-04T13:00:00Z' }))]
      const w = mountIt(events)

      await w.get('[data-testid="network-events-more-2038-02-03"]').trigger('click')
      expect(w.get(DAY).find('[data-testid="network-events-popover-2038-02-03"]').exists()).toBe(true)

      await w.get('[data-testid="network-events-more-2038-02-04"]').trigger('click')
      expect(w.findAll('[role="dialog"]')).toHaveLength(1)
      expect(w.get('[data-testid="network-events-day-2038-02-04"]').find('[role="dialog"]').exists()).toBe(true)
    })

    it('toggles closed on a second click, via the close button, Escape and a click elsewhere', async () => {
      const w = mountIt(manyEvents(7))
      const more = () => w.get('[data-testid="network-events-more-2038-02-03"]')
      const isOpen = () => w.find('[role="dialog"]').exists()

      await more().trigger('click')
      expect(isOpen()).toBe(true)
      await more().trigger('click')
      expect(isOpen()).toBe(false)

      await more().trigger('click')
      await w.get('[data-testid="network-events-popover-close"]').trigger('click')
      expect(isOpen()).toBe(false)

      await more().trigger('click')
      document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
      await w.vm.$nextTick()
      expect(isOpen()).toBe(false)

      await more().trigger('click')
      document.body.click()
      await w.vm.$nextTick()
      expect(isOpen()).toBe(false)
    })

    it('stays open when clicking inside it', async () => {
      const w = mountIt(manyEvents(7))
      await w.get('[data-testid="network-events-more-2038-02-03"]').trigger('click')

      await w.get('[data-testid="network-events-popover-2038-02-03"]').trigger('click')

      expect(w.find('[role="dialog"]').exists()).toBe(true)
    })

    it('closes when moving to another month', async () => {
      const w = mountIt(manyEvents(7))
      await w.get('[data-testid="network-events-more-2038-02-03"]').trigger('click')

      await w.get('[data-testid="network-events-next"]').trigger('click')

      expect(w.find('[role="dialog"]').exists()).toBe(false)
    })

    it('opens from the count dot that stands in for the chips on small screens', async () => {
      const w = mountIt(manyEvents(2))
      const dot = w.get('[data-testid="network-events-count-2038-02-03"]')
      expect(dot.text()).toBe('2')
      expect(dot.classes()).toContain('d-md-none')

      await dot.trigger('click')

      expect(w.get('[data-testid="network-events-popover-2038-02-03"]').findAll('[data-testid^="network-event-chip-"]')).toHaveLength(2)
    })
  })

  describe('chips', () => {
    it('are coloured per group, the same for every event of a group and different between groups', () => {
      const other = { id: 11, name: 'Other', image: null, approved: true, archived_at: null }
      const w = mountIt([event(1), event(2), event(3, { group: other })])
      const colour = (id) => w.get(`[data-testid="network-event-chip-${id}"]`).attributes('style')

      expect(colour(1)).toBe(colour(2))
      expect(colour(1)).not.toBe(colour(3))
      expect(GROUP_COLOURS.some((c) => colour(1).includes(c))).toBe(true)
    })

    it('show the group logo when it has one and the time in the event\'s timezone', () => {
      const w = mountIt([
        event(1, { group: { id: 10, name: 'Fixers United', image: 'logo.png', approved: true, archived_at: null } }),
        event(2, { start: '2038-02-03T23:30:00Z', end: '2038-02-04T01:00:00Z', timezone: 'Europe/Paris' }),
      ])

      expect(w.get('[data-testid="network-event-chip-1"] img').attributes('src')).toBe('http://api.test/uploads/logo.png')
      expect(w.find('[data-testid="network-event-chip-2"] img').exists()).toBe(false)
      expect(w.get('[data-testid="network-event-chip-1"]').text()).toBe('10:00Fixers United')
      // 23:30 UTC is 00:30 the next day in Paris, so it sits on the 4th.
      expect(w.get('[data-testid="network-events-day-2038-02-04"] [data-testid="network-event-chip-2"]').text()).toContain('00:30')
    })

    it('are struck through when cancelled, dashed for an unapproved or archived group, and say why on hover', () => {
      const w = mountIt([
        event(1, { cancelled: true }),
        event(2, { group: { id: 11, name: 'New', approved: false, archived_at: null } }),
        event(3, { group: { id: 12, name: 'Old', approved: true, archived_at: '2020-01-01T00:00:00Z' } }),
        event(4),
      ])
      const chip = (id) => w.get(`[data-testid="network-event-chip-${id}"]`)

      expect(chip(1).classes()).toContain('cancelled')
      expect(chip(1).classes()).not.toContain('dashed')
      expect(chip(1).attributes('title')).toBe('Event 1 (Cancelled)')

      expect(chip(2).classes()).toContain('dashed')
      expect(chip(2).attributes('title')).toBe('Event 2 (Group not approved)')
      expect(chip(3).classes()).toContain('dashed')
      expect(chip(3).attributes('title')).toBe('Event 3 (Archived group)')

      expect(chip(4).classes()).not.toContain('dashed')
      expect(chip(4).classes()).not.toContain('cancelled')
      expect(chip(4).attributes('title')).toBe('Event 4')
    })
  })
})
