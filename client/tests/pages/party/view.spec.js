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
    stats: { fixed_devices: 3, fixed_powered: 2, fixed_unpowered: 1, waste_powered: 4, co2_powered: 5, waste_unpowered: 1, co2_unpowered: 2 },
    ...overrides,
  }
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

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
})
