import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import DashboardUpcomingEvents from '../../../app/components/dashboard/DashboardUpcomingEvents.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

const BBadgeStub = {
  template: '<span v-bind="$attrs"><slot /></span>',
}

const BButtonStub = {
  props: ['to'],
  template: '<a :href="to" v-bind="$attrs"><slot /></a>',
}

function mountComponent(props = {}) {
  // dashboard.add_event is a new lang/en/dashboard.php key (this cluster's
  // parity fixes) not yet in the generated client i18n JSON - the main agent
  // regenerates that centrally (see DashboardWhatsHappening.spec.js for the
  // same pattern), so it's overlaid here inline.
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: { ...en, ...clientEn, dashboard: { ...en.dashboard, add_event: 'Add' } },
    },
  })

  return mount(DashboardUpcomingEvents, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, BButton: BButtonStub },
    },
  })
}

describe('components/dashboard/DashboardUpcomingEvents', () => {
  it('renders an empty state, but always shows the "see all" link (parity gap #5)', () => {
    const wrapper = mountComponent({ events: [] })

    expect(wrapper.find('[data-testid="upcoming-events-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="upcoming-events-list"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="upcoming-events-see-all"]').attributes('href')).toBe('/party')
  })

  it('renders events sorted soonest-first, linking to the event', () => {
    const wrapper = mountComponent({
      events: [
        {
          id: 10,
          title: 'Later Event',
          start: '2026-08-20T10:00:00Z',
          end: '2026-08-20T12:00:00Z',
          online: false,
          group: { id: 1, name: 'Group A' },
        },
        {
          id: 11,
          title: 'Sooner Event',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T12:00:00Z',
          online: false,
          group: { id: 2, name: 'Group B' },
        },
      ],
    })

    const titles = wrapper.findAll('[data-testid="upcoming-events-list"] a').map((a) => a.text())
    expect(titles[0]).toBe('Sooner Event')

    expect(wrapper.find('[data-testid="upcoming-event-link-11"]').attributes('href')).toBe('/party/view/11')
    expect(wrapper.find('[data-testid="upcoming-events-see-all"]').attributes('href')).toBe('/party')
  })

  it('shows the "Online" badge only for online events (parity gap #8)', () => {
    const wrapper = mountComponent({
      events: [
        {
          id: 20,
          title: 'Online Event',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T12:00:00Z',
          online: true,
          group: { id: 1, name: 'Group A' },
        },
        {
          id: 21,
          title: 'In-person Event',
          start: '2026-08-02T10:00:00Z',
          end: '2026-08-02T12:00:00Z',
          online: false,
          group: { id: 1, name: 'Group A' },
        },
      ],
    })

    expect(wrapper.find('[data-testid="upcoming-event-online-20"]').text()).toBe('Online')
    expect(wrapper.find('[data-testid="upcoming-event-online-21"]').exists()).toBe(false)
  })

  it('shows the event end time on desktop alongside the start time (parity gap #8)', () => {
    const wrapper = mountComponent({
      events: [
        {
          id: 30,
          title: 'An Event',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T12:00:00Z',
          online: false,
          group: { id: 1, name: 'Group A' },
        },
      ],
    })

    const details = wrapper.find('[data-testid="upcoming-event-30"] .small')
    expect(details.text()).toContain('-')
  })

  it('does not show an Attending badge or a group-name link (parity gap #8 - legacy DashboardEvent.vue has neither)', () => {
    const wrapper = mountComponent({
      events: [
        {
          id: 40,
          title: 'An Event',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T12:00:00Z',
          online: false,
          attending: true,
          group: { id: 7, name: 'Group Seven' },
        },
      ],
    })

    expect(wrapper.find('[data-testid="upcoming-event-attending-40"]').exists()).toBe(false)
    const groupLink = wrapper
      .find('[data-testid="upcoming-event-40"]')
      .findAll('a')
      .find((a) => a.text() === 'Group Seven')
    expect(groupLink).toBeUndefined()
  })

  it('shows the host-only "Add" button next to the heading only when amAHost is true (parity gap #3)', () => {
    const withHost = mountComponent({ events: [], amAHost: true })
    const addButton = withHost.find('[data-testid="upcoming-events-add"]')
    expect(addButton.exists()).toBe(true)
    expect(addButton.text()).toBe('Add')
    expect(addButton.attributes('href')).toBe('/party/create')

    const withoutHost = mountComponent({ events: [], amAHost: false })
    expect(withoutHost.find('[data-testid="upcoming-events-add"]').exists()).toBe(false)
  })
})
