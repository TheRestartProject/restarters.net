import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it } from 'vitest'
import EventsList from '../../../app/components/events/EventsList.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

const EVENTS = [
  {
    id: 1,
    title: 'Event One',
    start: '2026-08-01T10:00:00Z',
    end: '2026-08-01T12:00:00Z',
    timezone: 'Europe/London',
    attending: false,
    group: { id: 5, name: 'Group Five' },
  },
  {
    id: 2,
    title: 'Event Two',
    start: '2026-08-02T10:00:00Z',
    end: '2026-08-02T12:00:00Z',
    timezone: 'Europe/London',
    attending: false,
    group: { id: 6, name: 'Group Six' },
  },
]

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventsList, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, BButton: BButtonStub },
    },
  })
}

describe('components/events/EventsList', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('shows a loading skeleton and no items/empty state while loading', () => {
    const wrapper = mountComponent({ loading: true, events: EVENTS })

    expect(wrapper.find('[data-testid="events-list-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="events-list-items"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="events-list-empty"]').exists()).toBe(false)
  })

  it('shows the empty message when there are no events', () => {
    const wrapper = mountComponent({ events: [], emptyMessage: 'Nothing here' })

    expect(wrapper.find('[data-testid="events-list-empty"]').text()).toBe('Nothing here')
    expect(wrapper.find('[data-testid="events-list-items"]').exists()).toBe(false)
  })

  it('renders one EventCard per event', () => {
    const wrapper = mountComponent({ events: EVENTS })

    expect(wrapper.find('[data-testid="event-card-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-2"]').exists()).toBe(true)
  })

  it('marks a card as hosting only when its group id is in hostedGroupIds', () => {
    const wrapper = mountComponent({ events: EVENTS, hostedGroupIds: [5] })

    expect(wrapper.find('[data-testid="event-card-hosting-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-hosting-2"]').exists()).toBe(false)
  })

  // GroupEventScrollTable.vue's head(invited)/head(volunteers) icon titles -
  // distinct keys per column, not both reusing groups.volunteers.
  it('titles the invited/volunteers column icons with the long-form develop keys, not both groups.volunteers', () => {
    const wrapper = mountComponent({ events: EVENTS, past: false })
    const icons = wrapper.findAll('thead img')

    expect(icons[1].attributes('title')).toBe(en.groups.volunteers_invited)
    expect(icons[2].attributes('title')).toBe(en.groups.volunteers_confirmed)
  })

  // GroupEventScrollTable.vue's head(participants_count)/head(volunteers_count)/
  // head(fixed_devices)/head(repairable_devices)/head(dead_devices) icon titles.
  it('titles the past-bucket column icons with the long-form develop keys', () => {
    const wrapper = mountComponent({ events: EVENTS, past: true })
    const icons = wrapper.findAll('thead img')

    expect(icons[1].attributes('title')).toBe(en.groups.participants_attended)
    expect(icons[2].attributes('title')).toBe(en.groups.volunteers_attended)
    expect(icons[5].attributes('title')).toBe(en.groups.fixed_items)
    expect(icons[6].attributes('title')).toBe(en.groups.repairable_items)
    expect(icons[7].attributes('title')).toBe(en.groups.end_of_life_items)
  })
})
