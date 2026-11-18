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

    // Parity gap #6: the WHOLE row is one link (develop's DashboardEvent.vue
    // wraps date box + title + timestamp + avatar in a single clickable
    // target), so the anchor's text is the row's full content, not just the
    // title.
    const rows = wrapper.findAll('[data-testid="upcoming-events-list"] > li')
    expect(rows).toHaveLength(2)
    expect(rows[0].text()).toContain('Sooner Event')
    expect(rows[1].text()).toContain('Later Event')

    const link = wrapper.find('[data-testid="upcoming-event-link-11"]')
    expect(link.attributes('href')).toBe('/party/view/11')
    expect(link.text()).toContain('Sooner Event')

    expect(wrapper.find('[data-testid="upcoming-events-see-all"]').attributes('href')).toBe('/party')
  })

  it('wraps the whole row - date box and avatar included - in the single navigation link (parity gap #6)', () => {
    const wrapper = mountComponent({
      events: [
        {
          id: 50,
          title: 'Whole Row Event',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T12:00:00Z',
          online: false,
          group: { id: 1, name: 'Group A', image_url: '/uploads/group.jpg' },
        },
      ],
    })

    const link = wrapper.find('[data-testid="upcoming-event-link-50"]')
    expect(link.exists()).toBe(true)
    expect(link.attributes('href')).toBe('/party/view/50')
    // Only one <a> per row - date box/avatar aren't separately-linked.
    expect(wrapper.find('[data-testid="upcoming-event-50"]').findAll('a')).toHaveLength(1)
    expect(link.find('img').exists()).toBe(true)
    expect(link.find('.day').exists()).toBe(true)
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
