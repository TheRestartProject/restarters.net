import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkEventsList from '../../../app/components/networks/NetworkEventsList.vue'
import { NuxtLinkStub } from '../../helpers/stubs.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

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

function mountIt(events) {
  return mount(NetworkEventsList, {
    props: { events },
    global: {
      plugins: [createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })],
      stubs: { NuxtLink: { ...NuxtLinkStub, template: '<a v-bind="$attrs" :href="to"><slot /></a>' } },
    },
  })
}

describe('components/networks/NetworkEventsList', () => {
  beforeEach(() => {
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'http://api.test' } }))
  })

  it('says so when there are no events', () => {
    expect(mountIt([]).get('[data-testid="network-events-empty"]').text()).toBe('No events match these filters.')
  })

  it('groups events under month headings in order, each row linking to its event', () => {
    const w = mountIt([event(1), event(2, { start: '2038-03-10T18:00:00Z', end: '2038-03-10T19:30:00Z' })])

    expect(w.findAll('h3').map((h) => h.text())).toEqual(['February 2038', 'March 2038'])
    const row = w.get('[data-testid="network-event-row-2"]')
    expect(row.attributes('href')).toBe('/party/view/2')
    expect(row.text()).toContain('18:00 - 19:30')
    expect(row.text()).toContain('Fixers United')
  })

  it('shows times in the event\'s own timezone', () => {
    const w = mountIt([event(1, { start: '2038-02-03T10:00:00Z', end: '2038-02-03T12:00:00Z', timezone: 'Europe/Paris' })])

    expect(w.get('[data-testid="network-event-row-1"]').text()).toContain('11:00 - 13:00')
  })

  it('marks online, cancelled, unapproved and archived', () => {
    const w = mountIt([
      event(1, { online: true }),
      event(2, { cancelled: true }),
      event(3, { group: { id: 11, name: 'New', approved: false, archived_at: null } }),
      event(4, { group: { id: 12, name: 'Old', approved: true, archived_at: '2020-01-01T00:00:00Z' } }),
    ])

    expect(w.get('[data-testid="network-event-row-1"]').text()).toContain('Online')
    expect(w.get('[data-testid="network-event-row-2"]').classes()).toContain('cancelled')
    expect(w.get('[data-testid="network-event-row-2"]').text()).toContain('Cancelled')
    expect(w.get('[data-testid="network-event-row-3"] [data-testid="unapproved-badge"]').text()).toBe('Group not approved')
    expect(w.get('[data-testid="network-event-row-4"] [data-testid="archived-badge"]').text()).toBe('Archived group')
    expect(w.find('[data-testid="network-event-row-1"] [data-testid="unapproved-badge"]').exists()).toBe(false)
  })

  it('shows the group logo when it has one', () => {
    const w = mountIt([event(1, { group: { id: 10, name: 'Fixers', image: 'logo.png', approved: true, archived_at: null } }), event(2)])

    expect(w.get('[data-testid="network-event-row-1"] img').attributes('src')).toBe('http://api.test/uploads/logo.png')
    expect(w.find('[data-testid="network-event-row-2"] img').exists()).toBe(false)
  })
})
