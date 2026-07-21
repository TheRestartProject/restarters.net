import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventViewPage from '../../../app/pages/party/view/[id].vue'
import EventAddVolunteerModal from '../../../app/components/events/EventAddVolunteerModal.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useDashboardStore } from '../../../app/stores/dashboard.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useDevicesStore } from '../../../app/stores/devices.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useSessionStore } from '../../../app/stores/session.js'
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
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
// GAP 2/3/15: EventActionsDropdown/EventShareStatsModal/the CO2 info popover
// use BDropdown/BDropdownItem/BPopover - stubbed the same way
// tests/helpers/stubs.js does for the group-view parity work (renders
// unconditionally, no floating-ui positioning to simulate).
const BDropdownStub = {
  props: ['text', 'variant', 'noCaret'],
  template: '<div v-bind="$attrs">{{ text }}<slot /></div>',
}
const BDropdownItemStub = {
  props: ['to', 'href', 'disabled'],
  template: '<a v-bind="$attrs" :href="to || href" :class="{ disabled }"><slot /></a>',
}
const BPopoverStub = { props: ['target'], template: '<div><slot /></div>' }

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BBadge: BBadgeStub,
  BModal: BModalStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
  BDropdown: BDropdownStub,
  BDropdownItem: BDropdownItemStub,
  BPopover: BPopoverStub,
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
          // RES gap-closure pass (parity-v2/events.md) - same overlay
          // pattern for the new lang/en/events.php keys gaps 2/3/9/12/15
          // introduced (see that file's own doc comment - these already
          // exist verbatim on develop, just not yet in the generated JSON).
          event_actions: 'Event actions',
          event_details: 'Details',
          event_description: 'Description',
          share_event_stats: 'Share event stats',
          share_stats_header: 'Share your stats',
          share_stats_message: 'Well done! On the {date} at {event_name} we were able to repair <strong>{number_devices} items</strong>.',
          headline_stats_dropdown: 'Headline stats',
          headline_stats_message: 'This widget shows the headline stats for your event',
          co2_equivalence_visualisation_dropdown: 'CO2 equivalence visualisation',
          infographic_message: 'An infographic of an easy-to-understand equivalent of the CO2 emissions.',
          embed_code_header: 'Embed code',
          read_more: 'READ MORE',
          read_less: 'READ LESS',
          impact_calculation: '<p>How do we calculate environmental impact?</p>',
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

    useSessionStore().config = { discourse_url: 'https://talk.example.com' }
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

  // Audit regression fix: a logged-in viewer used to get a standalone
  // "event-attend" button AND the dropdown's own RSVP item at the same time
  // (EventActionsDropdown.vue never had a standalone RSVP entry point for
  // logged-in users in develop - see its doc comment) - RSVP for a
  // logged-in viewer now lives solely in the dropdown's
  // `event-actions-rsvp` item; the anonymous "log in to RSVP" link is the
  // only variant left outside the dropdown, since the dropdown doesn't
  // render at all when !loggedIn.
  describe('RSVP button states', () => {
    it('shows a login link when not attending and logged out, with no dropdown at all', () => {
      eventsStore.current.data = baseEvent({ attending: false })
      authStore.token = null

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-login-to-rsvp"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-dropdown"]').exists()).toBe(false)
    })

    it('shows the dropdown\'s RSVP item wired to the store when not attending and logged in, with no standalone button', async () => {
      eventsStore.current.data = baseEvent({ attending: false })
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-login-to-rsvp"]').exists()).toBe(false)
      const item = wrapper.find('[data-testid="event-actions-rsvp"]')
      expect(item.exists()).toBe(true)

      await item.trigger('click')
      expect(eventsStore.attend).toHaveBeenCalledWith(5)
    })

    it('shows the attending banner with a cancel button wired to the store when attending, and hides the RSVP item', async () => {
      eventsStore.current.data = baseEvent({ attending: true })
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-rsvp-banner"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-rsvp"]').exists()).toBe(false)

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
      await wrapper.find('[data-testid="event-actions-rsvp"]').trigger('click')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="event-view-follow-prompt"]').exists()).toBe(true)

      await wrapper.find('[data-testid="event-view-follow-group-prompt"]').trigger('click')
      expect(groupsStore.join).toHaveBeenCalledWith(9)
      await wrapper.vm.$nextTick()
      expect(wrapper.find('[data-testid="event-view-now-following"]').exists()).toBe(true)
    })
  })

  // Gap 2: Edit/Duplicate/Delete/Invite are now items inside the single
  // EventActionsDropdown, not standalone buttons - `event-view-*` testids
  // become `event-actions-*`, and the dropdown only renders at all for a
  // logged-in viewer (authStore.token), same as the rest of the dropdown's
  // authenticated-only actions.
  describe('permission-gated buttons', () => {
    it('shows Edit/Duplicate/Delete only when Administrator', () => {
      eventsStore.current.data = baseEvent()
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-actions-edit"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-duplicate"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-delete"]').exists()).toBe(true)
    })

    it('shows Edit/Delete when memberships report the user hosts the event\'s group', () => {
      eventsStore.current.data = baseEvent({ group: { id: 9, name: 'Acme Restarters' } })
      groupsStore.memberships = [{ id: 9, name: 'Acme Restarters', role: 3 }]
      authStore.user = { role_name: 'Restarter' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-actions-edit"]').exists()).toBe(true)
    })

    it('hides Edit/Duplicate/Delete for a plain Restarter not hosting the group', () => {
      eventsStore.current.data = baseEvent()
      authStore.user = { role_name: 'Restarter' }
      authStore.token = 'a-token'
      groupsStore.memberships = []

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-actions-edit"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-actions-duplicate"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-actions-delete"]').exists()).toBe(false)
    })

    it('disables Delete when the event still has devices', () => {
      eventsStore.current.data = baseEvent()
      devicesStore.byEvent[5] = { data: [{ id: 1 }], loading: false, error: null, loaded: true }
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      const item = wrapper.find('[data-testid="event-actions-delete"]')
      expect(item.exists()).toBe(true)
      expect(item.classes()).toContain('disabled')
    })

    it('enables Delete and wires up the confirm flow when the event has zero devices', async () => {
      eventsStore.current.data = baseEvent()
      devicesStore.byEvent[5] = { data: [], loading: false, error: null, loaded: true }
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      const item = wrapper.find('[data-testid="event-actions-delete"]')
      expect(item.classes()).not.toContain('disabled')

      await item.trigger('click')
      expect(wrapper.find('[data-testid="event-view-delete-confirm"]').exists()).toBe(true)

      await wrapper.find('[data-testid="event-view-delete-confirm"]').trigger('click')
      expect(eventsStore.deleteEvent).toHaveBeenCalledWith(5)
    })

    // EventActions.vue's canedit Invite item requires isAttending too, not
    // just canedit/upcoming/approved (audit fix - the item used to show for
    // any canedit host regardless of whether they'd RSVPed themselves).
    it('shows Invite volunteers only when canedit, attending, upcoming and approved', () => {
      eventsStore.current.data = baseEvent({ approved: true, attending: true })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-actions-invite"]').exists()).toBe(true)
    })

    // EventActions.vue's non-canedit Invite item is just `attending &&
    // upcoming` (no approved check) - a non-editing attendee gets it too,
    // matching legacy exactly (this used to be deliberately stricter than
    // legacy - see git history - which was itself the audit-flagged gap).
    it('shows Invite volunteers for a non-editing attendee too, matching develop', () => {
      eventsStore.current.data = baseEvent({ approved: true, attending: true })
      authStore.user = { role_name: 'Restarter' }
      authStore.token = 'a-token'
      groupsStore.memberships = []

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-actions-invite"]').exists()).toBe(true)
    })

    it('opens the invite modal from the invite dropdown item', async () => {
      eventsStore.current.data = baseEvent({ approved: true, attending: true })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-invite-form"]').exists()).toBe(false)

      await wrapper.find('[data-testid="event-actions-invite"]').trigger('click')
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

  // Gap 9/10: EventDetailsPanel's icon+bordered rows, including named hosts
  // sourced from the confirmed attendee list (role === EVENT_ROLE_HOST) -
  // no new endpoint, just useEventAttendance(id).hosts over data the page
  // already fetches.
  describe('event details panel (gap 9/10)', () => {
    it('renders the details panel with date/time rows', () => {
      eventsStore.current.data = baseEvent()
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-details-panel"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-details-time"]').exists()).toBe(true)
    })

    it('lists confirmed attendees with role HOST as named hosts', () => {
      eventsStore.current.data = baseEvent()
      eventsStore.attendees.data = {
        confirmed: [
          { id: 1, user: 20, role: 3, volunteer: { name: 'Jo Host', user_skills: [] } },
          { id: 2, user: 21, role: 4, volunteer: { name: 'Sam Guest', user_skills: [] } },
        ],
        invited: [],
      }

      const wrapper = mountPage()
      const hosts = wrapper.find('[data-testid="event-details-hosts"]')
      expect(hosts.text()).toContain('Jo Host')
      expect(hosts.text()).not.toContain('Sam Guest')
    })

    it('does not render a hosts row when nobody confirmed has role HOST', () => {
      eventsStore.current.data = baseEvent()
      eventsStore.attendees.data = { confirmed: [], invited: [] }

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-details-hosts"]').exists()).toBe(false)
    })
  })

  // Gap 11: EventDetails.vue's "Talk thread" row. app/Http/Resources/
  // Party.php only populates discourse_thread for a confirmed attendee
  // (mirrors view.blade.php's own gate), so the client renders purely off
  // whether the field is present - no extra isAttending check layered on
  // top.
  describe('discourse talk-thread link (gap 11)', () => {
    it('links to the Discourse thread when discourse_thread + config are present', () => {
      eventsStore.current.data = baseEvent({ discourse_thread: '4242' })
      const wrapper = mountPage()

      const talk = wrapper.find('[data-testid="event-details-talk"]')
      expect(talk.exists()).toBe(true)
      expect(talk.find('a').attributes('href')).toBe('https://talk.example.com/t/4242')
    })

    it('does not render a talk-thread link when discourse_thread is null (non-attendee, or no linked thread)', () => {
      eventsStore.current.data = baseEvent({ discourse_thread: null })
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-details-talk"]').exists()).toBe(false)
    })
  })

  // Gap 5: event-photos gallery, unblocked now the resource returns
  // `images`. Full component behaviour (thumbnails, lightbox) is covered
  // by EventImagesGallery.spec.js - this just confirms the page wires the
  // real `event.images` through.
  describe('event photos gallery (gap 5)', () => {
    it('shows the gallery once the event has images', () => {
      eventsStore.current.data = baseEvent({ images: [{ id: 1, idxref: 101, path: 'abc.jpg' }] })
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-images-gallery"]').exists()).toBe(true)
    })

    it('does not show the gallery when the event has no images', () => {
      eventsStore.current.data = baseEvent({ images: [] })
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-images-gallery"]').exists()).toBe(false)
    })
  })

  // Gap 13: EventAttendanceCount.vue's +/- stepper. PATCH /api/v2/events/
  // {id} validates the full event payload on every call, not just
  // participants/volunteers in isolation, so clicking +/- must resend the
  // event's current field values alongside the changed count.
  describe('inline-editable headcounts (gap 13)', () => {
    it('resends the full event payload plus the changed count when the participants stepper is used', async () => {
      eventsStore.current.data = baseEvent({
        start: '2020-01-01T10:00:00+00:00',
        end: '2020-01-01T12:00:00+00:00',
        link: 'https://example.com',
        stats: { ...baseEvent().stats, participants: 4 },
      })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'
      eventsStore.updateEventCount = vi.fn().mockResolvedValue()

      const wrapper = mountPage()
      await wrapper.find('[data-testid="event-attendees-participants-count-inc"]').trigger('click')

      expect(eventsStore.updateEventCount).toHaveBeenCalledWith(
        5,
        {
          start: '2020-01-01T10:00:00+00:00',
          end: '2020-01-01T12:00:00+00:00',
          title: 'Repair Café',
          description: '<p>Bring your broken things.</p>',
          location: 'Town Hall',
          online: false,
          link: 'https://example.com',
          timezone: 'Europe/London',
          network_data: '{}',
          participants: 5,
        },
        'participants',
        5
      )
    })

    it('toasts an error and leaves the count alone when the save fails', async () => {
      eventsStore.current.data = baseEvent({
        start: '2020-01-01T10:00:00+00:00',
        end: '2020-01-01T12:00:00+00:00',
        stats: { ...baseEvent().stats, volunteers: 2 },
      })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'
      eventsStore.updateEventCount = vi.fn().mockRejectedValue(new Error('nope'))

      const wrapper = mountPage()
      await wrapper.find('[data-testid="event-attendees-volunteers-count-inc"]').trigger('click')
      await wrapper.vm.$nextTick()

      expect(eventsStore.updateEventCount).toHaveBeenCalled()
    })

    it('does not show the stepper for a non-editing viewer', () => {
      eventsStore.current.data = baseEvent({
        start: '2020-01-01T10:00:00+00:00',
        end: '2020-01-01T12:00:00+00:00',
        stats: { ...baseEvent().stats, participants: 4 },
      })

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-attendees-participants-count-inc"]').exists()).toBe(false)
    })
  })

  // Gap 14: EventAttendance.vue's "Add volunteer" modal - full form
  // behaviour is covered by EventAddVolunteerModal.spec.js, this just
  // confirms the page wires the trigger and the close-refetch through.
  describe('add-volunteer modal (gap 14)', () => {
    it('opens the add-volunteer modal from the confirmed tab\'s link', async () => {
      eventsStore.current.data = baseEvent({ start: '2020-01-01T10:00:00+00:00', end: '2020-01-01T12:00:00+00:00' })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-add-volunteer-modal"]').exists()).toBe(false)

      await wrapper.find('[data-testid="event-attendees-add-volunteer-link"]').trigger('click')
      expect(wrapper.find('[data-testid="event-add-volunteer-modal"]').exists()).toBe(true)
    })

    it('refetches attendees when the modal is closed (matches legacy\'s always-refetch-on-hide)', async () => {
      eventsStore.current.data = baseEvent({ start: '2020-01-01T10:00:00+00:00', end: '2020-01-01T12:00:00+00:00' })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      await wrapper.find('[data-testid="event-attendees-add-volunteer-link"]').trigger('click')
      eventsStore.fetchAttendees.mockClear()

      await wrapper.findComponent(EventAddVolunteerModal).findComponent(BModalStub).vm.$emit('hide')

      expect(eventsStore.fetchAttendees).toHaveBeenCalledWith(5)
      expect(wrapper.find('[data-testid="event-add-volunteer-modal"]').exists()).toBe(false)
    })
  })

  // Gap 12: description truncated behind a Read more/Read less toggle,
  // using text-clipper for the same tag-safe 440-character cut
  // EventDescription.vue's ReadMore uses (not a CSS max-height clamp -
  // that approximation cut off at a different point than develop).
  describe('description truncation (gap 12)', () => {
    it('shows a Read more toggle for a long description, clips the rendered HTML, and expands to the full text on click', async () => {
      eventsStore.current.data = baseEvent({ description: `<p>${'a'.repeat(500)}</p>` })
      const wrapper = mountPage()

      const toggle = wrapper.find('[data-testid="event-view-description-toggle"]')
      expect(toggle.exists()).toBe(true)
      const content = wrapper.find('[data-testid="event-view-description-content"]')
      expect(content.text().length).toBeLessThan(500)

      await toggle.trigger('click')
      expect(wrapper.find('[data-testid="event-view-description-content"]').text().length).toBe(500)
    })

    it('does not show a Read more toggle for a short description', () => {
      eventsStore.current.data = baseEvent({ description: '<p>Short.</p>' })
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-view-description-toggle"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-view-description-content"]').text()).toBe('Short.')
    })

    // EventDescription.vue's CollapsibleSection hide-title: the "Event
    // description" heading only shows below md.
    // The description now goes through EventCollapsibleSection with
    // `hide-title`, as develop's EventDescription.vue:2 does, so the
    // mobile-only behaviour lives on the collapsible's title row rather than
    // on the heading itself - and the mobile collapse TOGGLE comes with it,
    // which the bare heading never had.
    it('renders the description in a collapsible whose title row is mobile-only', () => {
      eventsStore.current.data = baseEvent({ description: '<p>Short.</p>' })
      const wrapper = mountPage()

      const title = wrapper.find('[data-testid="event-view-description"] .collapsible-title')
      expect(title.exists()).toBe(true)
      expect(title.classes()).toContain('d-md-none')
      expect(title.text()).toContain('Description')
      expect(wrapper.find('[data-testid="event-view-description"] .collapsible-toggle').exists()).toBe(true)
    })
  })

  // Gap 2: every event action lives in one EventActionsDropdown.
  describe('event actions dropdown (gap 2)', () => {
    it('offers Request review/Share stats/Export for a finished, editable event', () => {
      eventsStore.current.data = baseEvent({ start: '2020-01-01T10:00:00+00:00', end: '2020-01-01T12:00:00+00:00' })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-actions-request-review"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-share-stats"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-export"]').attributes('href')).toBe('/export/devices/event/5')
    })

    it('offers an RSVP item for an upcoming event when not attending', () => {
      eventsStore.current.data = baseEvent({ attending: false })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-actions-rsvp"]').exists()).toBe(true)
    })

    it('offers a follow-group item when the viewer is not in the hosting group', () => {
      eventsStore.current.data = baseEvent({ group: { id: 9, name: 'Acme Restarters' } })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'
      groupsStore.memberships = []

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-actions-follow-group"]').exists()).toBe(true)
    })
  })

  // Gap 3: "Share event stats" opens a modal (dropdown item + CO2-card
  // "Share this" button both wire to it).
  describe('share event stats (gap 3)', () => {
    it('opens the share-stats modal from the dropdown item', async () => {
      eventsStore.current.data = baseEvent({ start: '2020-01-01T10:00:00+00:00', end: '2020-01-01T12:00:00+00:00' })
      authStore.user = { role_name: 'Administrator' }
      authStore.token = 'a-token'

      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="event-share-stats-modal"]').exists()).toBe(false)

      await wrapper.find('[data-testid="event-actions-share-stats"]').trigger('click')
      expect(wrapper.find('[data-testid="event-share-stats-modal"]').exists()).toBe(true)
    })

    // StatsImpact.vue: the header dropdown's "Share event stats" (above)
    // opens the embed-code modal (EventShareStatsModal), but the CO2 card's
    // own "Share this" link (StatsValue.vue's `share` handler) opens the
    // canvas-painted social-image generator (StatsShareImageModal) instead -
    // a separate feature, previously wired to the same embed modal as the
    // dropdown. Matches group/view/[id].vue's identical fix for its own CO2
    // card.
    it('opens the canvas share-image modal (not the embed modal) from the CO2 card\'s Share this link', async () => {
      eventsStore.current.data = baseEvent({
        start: '2020-01-01T10:00:00+00:00',
        end: '2020-01-01T12:00:00+00:00',
        stats: { ...baseEvent().stats, co2_total: 42 },
      })
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="stats-share-image-modal"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-share-stats-modal"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-view-impact-share"]').text()).toBe('Share this')

      await wrapper.find('[data-testid="event-view-impact-share"]').trigger('click')

      expect(wrapper.find('[data-testid="stats-share-image-modal"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-share-stats-modal"]').exists()).toBe(false)
    })
  })

  // Gap 15: fixed-items stats get the same icon-card treatment as the
  // impact cards, plus a CO2-equivalence description and info popover.
  describe('stats/impact cards (gap 15)', () => {
    it('shows an icon card for fixed devices and a CO2-equivalence description', () => {
      eventsStore.current.data = baseEvent({ start: '2020-01-01T10:00:00+00:00', end: '2020-01-01T12:00:00+00:00' })
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-view-stats-fixed"] img').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-view-impact-equivalent"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-view-impact-info"]').exists()).toBe(true)
    })
  })
})
