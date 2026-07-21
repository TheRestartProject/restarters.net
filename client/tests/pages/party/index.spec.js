import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import PartyIndexPage from '../../../app/pages/party/index.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useDashboardStore } from '../../../app/stores/dashboard.js'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-title="title"><slot /></div>',
}
const AlertsBannerStub = { template: '<div data-testid="stub-alerts-banner" />' }

// lang/en/calendars.php gained copy_button_label/see_all_calendars alongside
// this Nuxt work (RES gap-closure pass) but client/i18n/locales/en.json is a
// generated, checked-in artifact this change intentionally leaves untouched
// (php artisan translations:export-client) - overlay the new keys here, same
// pattern as tests/pages/networks/show.spec.js.
const messages = {
  en: {
    ...en,
    ...clientEn,
    calendars: {
      ...en.calendars,
      copy_button_label: 'Copy your calendar link',
      see_all_calendars: 'See all your calendars',
    },
  },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(PartyIndexPage, {
    global: {
      plugins: [i18n],
      stubs: {
        NuxtLink: NuxtLinkStub,
        BAlert: BAlertStub,
        BButton: BButtonStub,
        BBadge: BBadgeStub,
        BModal: BModalStub,
        AlertsBanner: AlertsBannerStub,
      },
    },
  })
}

function evt(overrides = {}) {
  return {
    id: 1,
    title: 'Event',
    start: '2026-08-01T10:00:00Z',
    end: '2026-08-01T12:00:00Z',
    timezone: 'Europe/London',
    attending: false,
    approved: true,
    group: { id: 9, name: 'A Group' },
    ...overrides,
  }
}

describe('pages/party/index (mine)', () => {
  let eventsStore
  let dashboardStore
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())

    eventsStore = useEventsStore()
    // These specs seed myEvents.data directly, i.e. they simulate a COMPLETED
    // fetch - so mark it loaded. Without this the page renders its loading
    // placeholder, because `loaded: false` now means "not fetched yet" rather
    // than "fetched and empty".
    eventsStore.myEvents.loaded = true
    eventsStore.fetchMyEvents = vi.fn().mockResolvedValue([])

    dashboardStore = useDashboardStore()
    dashboardStore.fetch = vi.fn().mockResolvedValue({})

    profileStore = useProfileStore()
    profileStore.fetchCalendars = vi.fn().mockResolvedValue({})
  })

  it('calls eventsStore.fetchMyEvents() and dashboardStore.fetch() on mount', () => {
    mountPage()
    expect(eventsStore.fetchMyEvents).toHaveBeenCalledTimes(1)
    expect(dashboardStore.fetch).toHaveBeenCalledTimes(1)
  })

  it('mounts the AlertsBanner above the events list', () => {
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="stub-alerts-banner"]').exists()).toBe(true)
  })

  it('shows a loading skeleton while loading', () => {
    eventsStore.myEvents.loading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="party-mine-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="party-mine-content"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls fetchMyEvents again', async () => {
    eventsStore.myEvents.error = { status: 500 }

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="party-mine-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="party-mine-retry"]').trigger('click')
    expect(eventsStore.fetchMyEvents).toHaveBeenCalledTimes(2)
  })

  it('shows the empty state on the upcoming tab when there are no mine events', () => {
    eventsStore.myEvents.data = []

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="party-mine-panel-upcoming"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="events-list-empty"]').exists()).toBe(true)
  })

  it('splits mine events into upcoming and past tabs', async () => {
    eventsStore.myEvents.data = [
      evt({ id: 1, title: 'Future', start: '2026-08-20T10:00:00Z', end: '2026-08-20T12:00:00Z' }),
      evt({ id: 2, title: 'Past', start: '2020-01-01T10:00:00Z', end: '2020-01-01T12:00:00Z' }),
    ]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-card-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-2"]').exists()).toBe(false)

    await wrapper.find('[data-testid="party-mine-tab-past"]').trigger('click')

    expect(wrapper.find('[data-testid="event-card-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-1"]').exists()).toBe(false)
  })

  it('excludes a nearby-tagged event from the mine (upcoming) bucket', () => {
    eventsStore.myEvents.data = [
      evt({ id: 3, title: 'Nearby', start: '2026-08-20T10:00:00Z', end: '2026-08-20T12:00:00Z', nearby: true }),
    ]

    const wrapper = mountPage()

    const mineUpcoming = wrapper.find('[data-testid="party-mine-panel-upcoming"]')
    expect(mineUpcoming.find('[data-testid="event-card-3"]').exists()).toBe(false)
    expect(mineUpcoming.find('[data-testid="events-list-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="party-other-events"]').exists()).toBe(true)
  })

  it('shows the other-events section with nearby/all tabs when tagged events are present', async () => {
    eventsStore.myEvents.data = [
      evt({ id: 3, title: 'Nearby Event', start: '2026-08-20T10:00:00Z', end: '2026-08-20T12:00:00Z', nearby: true }),
      evt({ id: 4, title: 'All Event', start: '2026-08-21T10:00:00Z', end: '2026-08-21T12:00:00Z', all: true }),
    ]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="party-other-events"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-3"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-4"]').exists()).toBe(false)

    await wrapper.find('[data-testid="party-other-tab-all"]').trigger('click')

    expect(wrapper.find('[data-testid="event-card-4"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-3"]').exists()).toBe(false)
  })

  describe('"all other events" filter bar (gap 18)', () => {
    function allEvent(overrides = {}) {
      return evt({ all: true, ...overrides })
    }

    beforeEach(() => {
      eventsStore.myEvents.data = [
        allEvent({
          id: 10,
          title: 'Repair Cafe',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T12:00:00Z',
          group: { id: 1, name: 'Group A', country: 'UK' },
        }),
        allEvent({
          id: 11,
          title: 'Fixit Session',
          start: '2026-09-01T10:00:00Z',
          end: '2026-09-01T12:00:00Z',
          group: { id: 2, name: 'Group B', country: 'France' },
        }),
      ]
    })

    it('populates the country dropdown from the distinct group.country values on the "all" events', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-other-tab-all"]').trigger('click')

      // The country filter is a TagMultiselect now, matching develop's
      // vue-multiselect control in GroupEventsScrollTableFilters.vue, so the
      // options are a prop rather than <option> elements.
      const picker = wrapper.findComponent('[data-testid="event-filters-country"]')
      expect(picker.props('options')).toEqual(['France', 'UK'])
    })

    it('narrows the list by title text', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-other-tab-all"]').trigger('click')

      const panel = wrapper.find('[data-testid="party-other-panel-all"]')
      await panel.find('[data-testid="event-filters-search"]').setValue('Repair')

      expect(panel.find('[data-testid="event-card-10"]').exists()).toBe(true)
      expect(panel.find('[data-testid="event-card-11"]').exists()).toBe(false)
    })

    it('narrows the list by host-group country', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-other-tab-all"]').trigger('click')

      const panel = wrapper.find('[data-testid="party-other-panel-all"]')
      await panel.findComponent('[data-testid="event-filters-country"]').vm.$emit('update:modelValue', 'France')

      expect(panel.find('[data-testid="event-card-10"]').exists()).toBe(false)
      expect(panel.find('[data-testid="event-card-11"]').exists()).toBe(true)
    })

    it('narrows the list by a start/end date range', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-other-tab-all"]').trigger('click')

      const panel = wrapper.find('[data-testid="party-other-panel-all"]')
      await panel.find('[data-testid="event-filters-start"]').setValue('2026-08-15')

      expect(panel.find('[data-testid="event-card-10"]').exists()).toBe(false)
      expect(panel.find('[data-testid="event-card-11"]').exists()).toBe(true)
    })

    it('shows the no-search-results empty message when the filters exclude everything', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-other-tab-all"]').trigger('click')

      const panel = wrapper.find('[data-testid="party-other-panel-all"]')
      await panel.find('[data-testid="event-filters-search"]').setValue('nonexistent')

      expect(panel.find('[data-testid="events-list-empty"]').exists()).toBe(true)
      expect(panel.find('[data-testid="events-list-empty"]').text()).toBe('No events match your search.')
    })
  })

  it('marks a mine event as hosting when its group id has role HOST in dashboard your_groups', () => {
    dashboardStore.data = {
      your_groups: [{ id: 9, name: 'A Group', role: 3, archived: false, image_url: null }],
    }
    eventsStore.myEvents.data = [
      evt({ id: 1, start: '2026-08-20T10:00:00Z', end: '2026-08-20T12:00:00Z', group: { id: 9, name: 'A Group' } }),
    ]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-card-hosting-1"]').exists()).toBe(true)
  })

  describe('calendar link modal', () => {
    it('is hidden until the calendar button is clicked', () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="party-calendar-modal"]').exists()).toBe(false)
    })

    it('opens the modal and fetches the calendar link when the button is clicked', async () => {
      const wrapper = mountPage()

      await wrapper.find('[data-testid="party-calendar-button"]').trigger('click')

      expect(wrapper.find('[data-testid="party-calendar-modal"]').exists()).toBe(true)
      expect(profileStore.fetchCalendars).toHaveBeenCalledTimes(1)
    })

    // Gap 24: the title used the wrong lang key (profile.calendars.my_events,
    // "My events" - unrelated boilerplate also used by CalendarsTab.vue)
    // instead of GroupEvents.vue's translatedCalendarTitle
    // (groups.calendar_copy_title), the same key its own description text
    // beside it already correctly used.
    it('uses groups.calendar_copy_title, matching develop\'s CalendarAddModal, not the unrelated profile.calendars.my_events copy', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-calendar-button"]').trigger('click')

      expect(wrapper.find('[data-testid="party-calendar-modal"]').attributes('data-title')).toBe(
        'Access all group events in your personal calendar'
      )
    })

    it('exposes the iCal URL with a working copy button once loaded', async () => {
      profileStore.calendars.data = { user_url: 'https://example.test/calendar/user/abc' }

      const originalClipboard = navigator.clipboard
      const writeText = vi.fn()
      Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true })

      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-calendar-button"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="party-calendar-url"]').element.value).toBe('https://example.test/calendar/user/abc')

      await wrapper.find('[data-testid="party-calendar-copy"]').trigger('click')
      expect(writeText).toHaveBeenCalledWith('https://example.test/calendar/user/abc')

      Object.defineProperty(navigator, 'clipboard', { value: originalClipboard, configurable: true })
    })

    it('links to the profile edit page for the full calendars list', async () => {
      profileStore.calendars.data = { user_url: 'https://example.test/calendar/user/abc' }

      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-calendar-button"]').trigger('click')

      const link = wrapper.find('[data-testid="party-calendar-see-all"]')
      expect(link.exists()).toBe(true)
    })

    it('shows a load-error state when fetchCalendars fails', async () => {
      profileStore.fetchCalendars = vi.fn().mockRejectedValue({ status: 500 })
      profileStore.calendars.error = { status: 500 }

      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-calendar-button"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="party-calendar-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="party-calendar-url"]').exists()).toBe(false)
    })

    // Gap 24: description text, "Find out more" help link and an inline
    // "copied to clipboard" confirmation, matching develop's CalendarAddModal.
    it('shows a description and a "Find out more" help link', async () => {
      profileStore.calendars.data = { user_url: 'https://example.test/calendar/user/abc' }

      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-calendar-button"]').trigger('click')

      expect(wrapper.find('[data-testid="party-calendar-description"]').exists()).toBe(true)
      const link = wrapper.find('[data-testid="party-calendar-find-out-more"]')
      expect(link.attributes('href')).toContain('talk.restarters.net')
      expect(link.attributes('target')).toBe('_blank')
    })

    it('shows a copied-to-clipboard confirmation only after the copy button is used', async () => {
      profileStore.calendars.data = { user_url: 'https://example.test/calendar/user/abc' }
      Object.defineProperty(navigator, 'clipboard', { value: { writeText: vi.fn() }, configurable: true })

      const wrapper = mountPage()
      await wrapper.find('[data-testid="party-calendar-button"]').trigger('click')

      expect(wrapper.find('[data-testid="party-calendar-copied"]').exists()).toBe(false)

      await wrapper.find('[data-testid="party-calendar-copy"]').trigger('click')
      expect(wrapper.find('[data-testid="party-calendar-copied"]').exists()).toBe(true)
    })
  })

  // Gap 6: page-level 'Events' h1, distinct from the 'Your events' section
  // heading (which now carries the calendar button - gap 16).
  it('shows a page-level "Events" h1 and a distinct "Your events" section heading', () => {
    const wrapper = mountPage()

    expect(wrapper.find('h1').text()).toBe('Events')
    expect(wrapper.find('[data-testid="party-mine-content"]').text()).toContain('Your events')
  })

  // Gap 7: mobile-collapsible sections with a count badge next to the heading.
  it('shows a count badge on the "Your events" and "Other events" section headings', () => {
    eventsStore.myEvents.data = [
      evt({ id: 1, title: 'Upcoming', start: '2026-08-20T10:00:00Z', end: '2026-08-20T12:00:00Z' }),
      evt({ id: 2, title: 'Nearby', start: '2026-08-20T10:00:00Z', end: '2026-08-20T12:00:00Z', nearby: true }),
    ]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="party-mine-content"] [data-testid="event-collapsible-count-badge"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="party-other-events"] [data-testid="event-collapsible-count-badge"]').exists()).toBe(true)
  })
})
