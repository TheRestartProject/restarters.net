import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventViewPage from '../../../app/pages/party/view/[id].vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useDashboardStore } from '../../../app/stores/dashboard.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useDevicesStore } from '../../../app/stores/devices.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// The page pulls in EventVenueMap.vue (gap D4), which uses the real
// @vue-leaflet/vue-leaflet package - that package does its own dynamic
// import()s of Leaflet's marker PNGs to patch L.Icon.Default, which needs a
// bundler/browser rather than Node's module loader (Vitest runs component
// tests under Node). Mock the whole package rather than mount it for real,
// same approach GroupMap.spec.js/EventVenueMap.spec.js take.
const { LMapStub, LTileLayerStub, LMarkerStub } = vi.hoisted(() => ({
  LMapStub: { name: 'LMap', props: ['zoom', 'center', 'options', 'useGlobalLeaflet'], template: '<div class="stub-lmap"><slot /></div>' },
  LTileLayerStub: { name: 'LTileLayer', props: ['url', 'attribution'], template: '<div class="stub-ltilelayer" />' },
  LMarkerStub: { name: 'LMarker', props: ['latLng', 'icon', 'interactive'], template: '<div class="stub-lmarker" />' },
}))

vi.mock('@vue-leaflet/vue-leaflet', () => ({ LMap: LMapStub, LTileLayer: LTileLayerStub, LMarker: LMarkerStub }))

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue"><slot /></div>',
}
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot /></div>' }

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BBadge: BBadgeStub,
  BModal: BModalStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
}

function baseEvent(overrides = {}) {
  return {
    id: 5,
    title: 'Repair Café',
    description: '<p>Bring your broken things.</p>',
    start: '2026-08-20T10:00:00+00:00',
    end: '2026-08-20T12:00:00+00:00',
    timezone: 'Europe/London',
    location: 'Town Hall',
    online: false,
    lat: 51.5,
    lng: -0.1,
    online_event: false,
    approved: true,
    attending: false,
    group: { id: 9, name: 'Acme Restarters', image: null },
    stats: {
      fixed_devices: 3,
      fixed_powered: 2,
      fixed_unpowered: 1,
      waste_powered: 4,
      co2_powered: 5,
      waste_unpowered: 1,
      co2_unpowered: 2,
      waste_total: 5,
      co2_total: 7,
      dead_devices: 0,
      repairable_devices: 0,
      no_weight_powered: 0,
      no_weight_unpowered: 0,
      participants: 12,
      volunteers: 3,
    },
    ...overrides,
  }
}

function mountPage() {
  // events.environmental_impact/not_counting/to_be_recycled/to_be_repaired/
  // no_weight/stat-0/stat-2 are new lang/en/events.php keys (this task's
  // D1/D3 gaps) not yet in the generated client i18n JSON - the main agent
  // regenerates that centrally, so they're overlaid here inline rather than
  // editing client/i18n/locales/*.json directly (see DashboardWhatsHappening
  // .spec.js for the same pattern).
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        events: {
          ...en.events,
          environmental_impact: 'Environmental impact',
          not_counting: "Not counting toward this event's environmental impact is|Not counting toward this event's environmental impact are",
          to_be_recycled: '{value} item to be recycled|{value} items to be recycled',
          to_be_repaired: '{value} item to be repaired|{value} items to be repaired',
          no_weight: '{value} misc or unpowered item with no weight estimate|{value} misc or unpowered items with no weight estimate',
          'stat-0': 'Participants',
          'stat-2': 'Volunteers',
        },
      },
    },
  })

  return mount(EventViewPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/party/view/[id]', () => {
  let eventsStore
  let dashboardStore
  let groupsStore
  let devicesStore
  let authStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '5' }, query: {}, fullPath: '/party/view/5' }))
    vi.stubGlobal('navigateTo', vi.fn())

    eventsStore = useEventsStore()
    eventsStore.fetchEvent = vi.fn().mockResolvedValue(baseEvent())
    eventsStore.fetchAttendees = vi.fn().mockResolvedValue({ confirmed: [], invited: [] })
    eventsStore.attend = vi.fn().mockResolvedValue({ attending: true })
    eventsStore.unattend = vi.fn().mockResolvedValue({ left: true })
    eventsStore.deleteEvent = vi.fn().mockResolvedValue({ deleted: true })

    dashboardStore = useDashboardStore()
    dashboardStore.fetch = vi.fn().mockResolvedValue({})

    groupsStore = useGroupsStore()
    groupsStore.join = vi.fn().mockResolvedValue()

    devicesStore = useDevicesStore()
    devicesStore.fetchForEvent = vi.fn().mockResolvedValue([])

    authStore = useAuthStore()
  })

  it('fetches the event, attendees and devices for the routed id on mount', () => {
    mountPage()

    expect(eventsStore.fetchEvent).toHaveBeenCalledWith(5)
    expect(eventsStore.fetchAttendees).toHaveBeenCalledWith(5)
    expect(devicesStore.fetchForEvent).toHaveBeenCalledWith(5)
  })

  it('shows a loading skeleton and no header while loading', () => {
    eventsStore.current.loading = true
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-view-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-view-header"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls the fetches again', async () => {
    eventsStore.current.error = { status: 404 }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-view-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="event-view-retry"]').trigger('click')
    expect(eventsStore.fetchEvent).toHaveBeenCalledTimes(2)
  })

  it('renders the header: title, date and venue', () => {
    eventsStore.current.data = baseEvent()
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-view-title"]').text()).toBe('Repair Café')
    expect(wrapper.find('[data-testid="event-view-date"]').text()).toContain('20')
    expect(wrapper.find('[data-testid="event-view-venue"]').text()).toContain('Town Hall')
    expect(wrapper.find('[data-testid="event-view-online"]').exists()).toBe(false)
  })

  it('shows an online marker instead of venue when the event is online', () => {
    eventsStore.current.data = baseEvent({ online: true })
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-view-online"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-view-venue"]').exists()).toBe(false)
  })

  it('shows the moderation note only when the event is not approved', () => {
    eventsStore.current.data = baseEvent({ approved: false })
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="event-view-moderation-note"]').exists()).toBe(true)

    eventsStore.current.data = baseEvent({ approved: true })
    const wrapper2 = mountPage()
    expect(wrapper2.find('[data-testid="event-view-moderation-note"]').exists()).toBe(false)
  })

  describe('RSVP button states', () => {
    it('shows a login link when not attending and logged out', () => {
      eventsStore.current.data = baseEvent({ attending: false })
      authStore.token = null

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-login-to-rsvp"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-attend"]').exists()).toBe(false)
    })

    it('shows an RSVP button wired to the store when not attending and logged in', async () => {
      eventsStore.current.data = baseEvent({ attending: false })
      authStore.token = 'a-token'

      const wrapper = mountPage()
      const button = wrapper.find('[data-testid="event-attend"]')
      expect(button.exists()).toBe(true)

      await button.trigger('click')
      expect(eventsStore.attend).toHaveBeenCalledWith(5)
    })

    it('shows the attending banner with a cancel button wired to the store when attending', async () => {
      eventsStore.current.data = baseEvent({ attending: true })
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-rsvp-banner"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-attend"]').exists()).toBe(false)

      await wrapper.find('[data-testid="event-view-unattend"]').trigger('click')
      expect(eventsStore.unattend).toHaveBeenCalledWith(5)
    })

    it('does not show the RSVP banner once the event has finished, even if attending', () => {
      eventsStore.current.data = baseEvent({
        attending: true,
        start: '2020-01-01T10:00:00+00:00',
        end: '2020-01-01T12:00:00+00:00',
      })
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-rsvp-banner"]').exists()).toBe(false)
    })

    it('shows the follow-group prompt after RSVP reports prompt_follow_group, and wires the follow button', async () => {
      eventsStore.current.data = baseEvent({ attending: false })
      authStore.token = 'a-token'
      eventsStore.attend = vi.fn().mockResolvedValue({ attending: true, prompt_follow_group: true })

      const wrapper = mountPage()
      await wrapper.find('[data-testid="event-attend"]').trigger('click')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="event-view-follow-prompt"]').exists()).toBe(true)

      await wrapper.find('[data-testid="event-view-follow-group-prompt"]').trigger('click')
      expect(groupsStore.join).toHaveBeenCalledWith(9)
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[data-testid="event-view-now-following"]').exists()).toBe(true)
    })
  })

  describe('permission-gated buttons', () => {
    it('shows Edit/Duplicate/Delete only when Administrator', () => {
      eventsStore.current.data = baseEvent()
      authStore.user = { role_name: 'Administrator' }

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-edit"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-view-duplicate"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-view-delete"]').exists()).toBe(true)
    })

    it('shows Edit/Delete when memberships report the user hosts the event\'s group', () => {
      eventsStore.current.data = baseEvent({ group: { id: 9, name: 'Acme Restarters' } })
      groupsStore.memberships = [{ id: 9, name: 'Acme Restarters', role: 3 }]
      authStore.user = { role_name: 'Restarter' }

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-edit"]').exists()).toBe(true)
    })

    it('hides Edit/Duplicate/Delete for a plain Restarter not hosting the group', () => {
      eventsStore.current.data = baseEvent()
      authStore.user = { role_name: 'Restarter' }
      groupsStore.memberships = []

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-edit"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-view-duplicate"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-view-delete"]').exists()).toBe(false)
    })

    it('disables Delete when the event still has devices', () => {
      eventsStore.current.data = baseEvent()
      devicesStore.byEvent[5] = { data: [{ id: 1 }], loading: false, error: null, loaded: true }
      authStore.user = { role_name: 'Administrator' }

      const wrapper = mountPage()
      const button = wrapper.find('[data-testid="event-view-delete"]')
      expect(button.exists()).toBe(true)
      expect(button.attributes('disabled')).toBeDefined()
    })

    it('enables Delete and wires up the confirm flow when the event has zero devices', async () => {
      eventsStore.current.data = baseEvent()
      devicesStore.byEvent[5] = { data: [], loading: false, error: null, loaded: true }
      authStore.user = { role_name: 'Administrator' }

      const wrapper = mountPage()
      const button = wrapper.find('[data-testid="event-view-delete"]')
      expect(button.attributes('disabled')).toBeUndefined()

      await button.trigger('click')
      expect(wrapper.find('[data-testid="event-view-delete-confirm"]').exists()).toBe(true)

      await wrapper.find('[data-testid="event-view-delete-confirm"]').trigger('click')
      expect(eventsStore.deleteEvent).toHaveBeenCalledWith(5)
    })

    it('shows Invite volunteers only when canedit, upcoming and approved', () => {
      eventsStore.current.data = baseEvent({ approved: true })
      authStore.user = { role_name: 'Administrator' }

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-invite"]').exists()).toBe(true)
    })

    it('hides Invite volunteers for a non-editing attendee (stricter than legacy - api-gaps.md)', () => {
      eventsStore.current.data = baseEvent({ approved: true, attending: true })
      authStore.user = { role_name: 'Restarter' }
      groupsStore.memberships = []

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-invite"]').exists()).toBe(false)
    })

    it('opens the invite modal from the invite button', async () => {
      eventsStore.current.data = baseEvent({ approved: true })
      authStore.user = { role_name: 'Administrator' }

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-invite-form"]').exists()).toBe(false)

      await wrapper.find('[data-testid="event-view-invite"]').trigger('click')
      expect(wrapper.find('[data-testid="event-invite-form"]').exists()).toBe(true)
    })
  })

  describe('calendar links', () => {
    it('renders the four add-to-calendar hrefs for an upcoming event', () => {
      eventsStore.current.data = baseEvent()
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-view-calendar-google"]').attributes('href')).toContain('calendar.google.com')
      expect(wrapper.find('[data-testid="event-view-calendar-outlook"]').attributes('href')).toContain('outlook.live.com')
      expect(wrapper.find('[data-testid="event-view-calendar-ics"]').attributes('href')).toContain('data:text/calendar')
      expect(wrapper.find('[data-testid="event-view-calendar-yahoo"]').attributes('href')).toContain('calendar.yahoo.com')
    })

    it('does not render the calendar dropdown for a finished event', () => {
      eventsStore.current.data = baseEvent({ start: '2020-01-01T10:00:00+00:00', end: '2020-01-01T12:00:00+00:00' })
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-calendar-dropdown"]').exists()).toBe(false)
    })
  })

  // Gap D1: waste/CO2 stat cards + "not counting" note, modelled on
  // components/groups/GroupStats.vue's group-stats-impact section.
  describe('environmental impact (D1)', () => {
    const finishedRange = { start: '2020-01-01T10:00:00+00:00', end: '2020-01-01T12:00:00+00:00' }

    it('shows rounded waste/co2 totals for a finished event', () => {
      eventsStore.current.data = baseEvent({ ...finishedRange, stats: { ...baseEvent().stats, waste_total: 5.6, co2_total: 7.4 } })
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-view-impact-waste"]').text()).toContain('6 kg')
      expect(wrapper.find('[data-testid="event-view-impact-co2"]').text()).toContain('7 kg')
    })

    it('does not show the impact section for an upcoming event', () => {
      eventsStore.current.data = baseEvent()
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-impact"]').exists()).toBe(false)
    })

    it('lists dead/repairable/no-weight devices excluded from the totals', () => {
      eventsStore.current.data = baseEvent({
        ...finishedRange,
        stats: { ...baseEvent().stats, dead_devices: 2, repairable_devices: 1, no_weight_powered: 1, no_weight_unpowered: 2 },
      })
      const wrapper = mountPage()

      const note = wrapper.find('[data-testid="event-view-impact-notincluded"]').text()
      expect(note).toContain('2 items to be recycled')
      expect(note).toContain('1 item to be repaired')
      expect(note).toContain('3 misc or unpowered items with no weight estimate')
    })

    it('omits the notincluded note when nothing is excluded', () => {
      eventsStore.current.data = baseEvent({ ...finishedRange })
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-impact-notincluded"]').exists()).toBe(false)
    })
  })

  // Gap D2: optional host-set external link (Party::$link), only rendered
  // when the resource actually returns it.
  describe('event link (D2)', () => {
    it('renders a clickable external link when event.link is set', () => {
      eventsStore.current.data = baseEvent({ link: 'https://example.com/signup' })
      const wrapper = mountPage()

      const anchor = wrapper.find('[data-testid="event-view-link-anchor"]')
      expect(anchor.exists()).toBe(true)
      expect(anchor.attributes('href')).toBe('https://example.com/signup')
      expect(anchor.attributes('target')).toBe('_blank')
      expect(anchor.attributes('rel')).toBe('noopener')
    })

    it('does not render a link row when event.link is absent', () => {
      eventsStore.current.data = baseEvent()
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-link"]').exists()).toBe(false)
    })
  })

  // Gap D4: small Leaflet venue map for in-person events with coordinates.
  describe('venue map (D4)', () => {
    it('renders the venue map for an in-person event with coordinates', () => {
      eventsStore.current.data = baseEvent({ online: false, lat: 51.5, lng: -0.1 })
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-venue-map"]').exists()).toBe(true)
    })

    it('does not render the venue map for an online event', () => {
      eventsStore.current.data = baseEvent({ online: true, lat: 51.5, lng: -0.1 })
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-venue-map"]').exists()).toBe(false)
    })

    it('does not render the venue map when coordinates are missing', () => {
      eventsStore.current.data = baseEvent({ online: false, lat: null, lng: null })
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-venue-map"]').exists()).toBe(false)
    })
  })

  // Gap D3: total-participants/-volunteers headcounts, passed through to
  // EventAttendees from event.stats (real data, not fabricated - confirmed
  // against a live GET /api/v2/events/{id}).
  describe('attendee headcounts (D3)', () => {
    it('shows participant/volunteer headcounts for a finished event', () => {
      eventsStore.current.data = baseEvent({
        start: '2020-01-01T10:00:00+00:00',
        end: '2020-01-01T12:00:00+00:00',
        stats: { ...baseEvent().stats, participants: 12, volunteers: 3 },
      })
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-attendees-participants"]').text()).toContain('12')
      expect(wrapper.find('[data-testid="event-attendees-volunteers"]').text()).toContain('3')
    })

    it('does not show headcounts for an upcoming event', () => {
      eventsStore.current.data = baseEvent()
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-attendees-headcounts"]').exists()).toBe(false)
    })
  })
})
