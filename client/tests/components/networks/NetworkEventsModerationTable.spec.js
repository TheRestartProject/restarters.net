import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkEventsModerationTable from '../../../app/components/networks/NetworkEventsModerationTable.vue'
import { useModerationStore } from '../../../app/stores/moderation.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(NetworkEventsModerationTable, {
    props: { networkId: 1, ...props },
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } },
  })
}

describe('components/networks/NetworkEventsModerationTable', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useModerationStore()
  })

  it('fetches the moderation events queue on mount', () => {
    store.fetchEvents = vi.fn(async () => {})
    mountComponent()
    expect(store.fetchEvents).toHaveBeenCalled()
  })

  it('shows the "None" message when there are no events awaiting moderation for this network', async () => {
    store.fetchEvents = vi.fn(async () => {
      store.events.data = []
    })

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.get('[data-testid="network-events-moderation-empty"]').text()).toBe('None')
  })

  it('scopes to events via their nested group.networks array', async () => {
    store.fetchEvents = vi.fn(async () => {
      store.events.data = [
        { id: 7, title: 'Repair Café', start: '2026-08-01T10:00:00Z', group: { id: 3, name: 'Camden Restarters', networks: [{ id: 1 }] } },
        { id: 8, title: 'Fixfest', start: '2026-08-02T10:00:00Z', group: { id: 4, name: 'Other City', networks: [{ id: 99 }] } },
      ]
    })

    const wrapper = mountComponent({ networkId: 1 })
    await flushPromises()

    expect(wrapper.find('[data-testid="network-events-moderation-row-7"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="network-events-moderation-row-8"]').exists()).toBe(false)
  })

  it('renders date, title+group, confirmed volunteers, and a moderation flag link to the event', async () => {
    store.fetchEvents = vi.fn(async () => {
      store.events.data = [
        {
          id: 7,
          title: 'Repair Café',
          start: '2026-08-01T10:00:00Z',
          group: { id: 3, name: 'Camden Restarters', networks: [{ id: 1 }] },
          stats: { volunteers: 6 },
        },
      ]
    })

    const wrapper = mountComponent()
    await flushPromises()

    const row = wrapper.get('[data-testid="network-events-moderation-row-7"]')
    expect(row.text()).toContain('Repair Café')
    expect(row.text()).toContain('Camden Restarters')
    expect(row.text()).toContain('6')

    const link = wrapper.get('[data-testid="network-events-moderation-link-7"]')
    expect(link.attributes('href')).toBe('/party/view/7')

    const flag = wrapper.get('[data-testid="network-events-moderation-flag-7"]')
    expect(flag.attributes('href')).toBe('/party/edit/7')
    expect(flag.text()).toBe('Event requires moderation')
  })

  // GroupEventsScrollTableDateLong.vue's date cell: full date, start-end
  // time and a clock-icon timezone line - not a bare formatted date
  // (rendered-diff finding #89).
  it('renders the event start/end time and timezone alongside the date', async () => {
    store.fetchEvents = vi.fn(async () => {
      store.events.data = [
        {
          id: 7,
          title: 'Repair Café',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T14:00:00Z',
          timezone: 'Europe/London',
          group: { id: 3, name: 'Camden Restarters', networks: [{ id: 1 }] },
          stats: { volunteers: 6 },
        },
      ]
    })

    const wrapper = mountComponent()
    await flushPromises()

    const row = wrapper.get('[data-testid="network-events-moderation-row-7"]')
    expect(row.text()).toContain('Europe/London')
    expect(row.find('img.datelong-icon').exists()).toBe(true)
  })

  // develop's head(date_long) template carries no title/tooltip on the
  // clock icon, unlike the invited/volunteers headers (rendered-diff
  // finding #90).
  it('does not put a tooltip on the date column\'s clock-icon header', async () => {
    store.fetchEvents = vi.fn(async () => {
      store.events.data = [{ id: 7, title: 'Repair Café', start: '2026-08-01T10:00:00Z', group: { id: 3, name: 'Camden Restarters', networks: [{ id: 1 }] } }]
    })

    const wrapper = mountComponent()
    await flushPromises()

    const headerIcons = wrapper.findAll('th img')
    const clockHeaderIcon = headerIcons.find((img) => img.attributes('src') === '/images/clock.svg')
    expect(clockHeaderIcon.attributes('title')).toBeUndefined()
  })
})
