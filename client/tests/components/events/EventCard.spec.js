import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import EventCard from '../../../app/components/events/EventCard.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { NuxtLinkStub, BBadgeStub, BButtonStub } from '../../helpers/stubs.js'

function baseEvent(overrides = {}) {
  return {
    id: 42,
    title: 'Repair Cafe',
    start: '2026-08-01T10:00:00Z',
    end: '2026-08-01T12:00:00Z',
    timezone: 'Europe/London',
    attending: false,
    online: false,
    location: 'Town Hall',
    group: { id: 3, name: 'Acme Restarters' },
    ...overrides,
  }
}

// Gap 1: EventCard is now one <tr> of EventsList.vue's table - mounted
// standalone here (outside a <table>), same approach GroupVolunteers'
// row-level components use, since Vue mounts via DOM APIs rather than an
// HTML-parser that would otherwise foster-parent a bare <tr>.
function mountComponent(props = {}) {
  // events.no_devices_added/add_a_device are new lang/en/events.php keys
  // (gap 18) not yet in the generated client i18n JSON - overlaid inline
  // per DashboardWhatsHappening.spec.js's established pattern rather than
  // editing client/i18n/locales/*.json directly.
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        events: { ...en.events, no_devices_added: 'No devices added', add_a_device: 'Add a device' },
      },
    },
  })

  return mount(EventCard, {
    props: { event: baseEvent(), ...props },
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, BButton: BButtonStub },
    },
  })
}

describe('components/events/EventCard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders the date block, title link and group link', () => {
    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="event-card-date-42"]').text()).toContain('01')
    expect(wrapper.find('[data-testid="event-card-date-42"]').text()).toContain('AUG')
    expect(wrapper.find('[data-testid="event-card-link-42"]').attributes('href')).toBe('/party/view/42')
    expect(wrapper.find('[data-testid="event-card-group-42"]').attributes('href')).toBe('/group/view/3')
  })

  // Gap 1: GroupEventsScrollTableDateLong.vue's long-date column (full date
  // + start/end time + timezone) - useEventComputed.js already derives all
  // four fields, this column just needed wiring up.
  it('renders the long-date column with full date, start/end time and timezone', () => {
    const wrapper = mountComponent()

    const cell = wrapper.find('[data-testid="event-card-datelong-42"]')
    expect(cell.text()).toContain('Sat 1st Aug 2026')
    expect(cell.text()).toContain('11:00')
    expect(cell.text()).toContain('13:00')
    expect(cell.text()).toContain('Europe/London')
  })

  it('shows the venue when the event is not online', () => {
    const wrapper = mountComponent()

    expect(wrapper.find('[data-testid="event-card-venue-42"]').text()).toContain('Town Hall')
    expect(wrapper.find('[data-testid="event-card-online-42"]').exists()).toBe(false)
  })

  it('shows an online marker instead of venue when the event is online', () => {
    const wrapper = mountComponent({ event: baseEvent({ online: true }) })

    expect(wrapper.find('[data-testid="event-card-online-42"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-venue-42"]').exists()).toBe(false)
  })

  it('shows an attending badge only when attending', () => {
    expect(mountComponent({ event: baseEvent({ attending: true }) }).find('[data-testid="event-card-attending-42"]').exists()).toBe(true)
    expect(mountComponent({ event: baseEvent({ attending: false }) }).find('[data-testid="event-card-attending-42"]').exists()).toBe(false)
  })

  it('shows a hosting badge only when the hosting prop is set', () => {
    expect(mountComponent({ hosting: true }).find('[data-testid="event-card-hosting-42"]').exists()).toBe(true)
    expect(mountComponent({ hosting: false }).find('[data-testid="event-card-hosting-42"]').exists()).toBe(false)
  })

  // GroupEventsScrollTableActions.vue disables RSVP once an event is
  // "starting soon" - starting today but not yet begun. The base fixture
  // starts at 10:00 UTC on 2026-08-01, so the clock decides.
  describe('starting soon', () => {
    afterEach(() => {
      vi.useRealTimers()
    })

    it('disables RSVP for an event starting later today', () => {
      vi.useFakeTimers()
      vi.setSystemTime(new Date('2026-08-01T08:00:00Z'))

      const wrapper = mountComponent()

      expect(wrapper.find('[data-testid="event-attend-42"]').attributes('disabled')).toBeDefined()
    })

    it('leaves RSVP enabled the day before', () => {
      vi.useFakeTimers()
      vi.setSystemTime(new Date('2026-07-31T08:00:00Z'))

      const wrapper = mountComponent()

      expect(wrapper.find('[data-testid="event-attend-42"]').attributes('disabled')).toBeUndefined()
    })

    it('still lets an attendee withdraw from an event starting today', () => {
      vi.useFakeTimers()
      vi.setSystemTime(new Date('2026-08-01T08:00:00Z'))

      const wrapper = mountComponent({ event: baseEvent({ attending: true }) })

      expect(wrapper.find('[data-testid="event-unattend-42"]').attributes('disabled')).toBeUndefined()
    })
  })

  it('shows the RSVP button and calls store.attend() when not attending', async () => {
    const store = useEventsStore()
    store.attend = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ event: baseEvent({ attending: false }) })

    const button = wrapper.find('[data-testid="event-attend-42"]')
    expect(button.exists()).toBe(true)

    await button.trigger('click')

    expect(store.attend).toHaveBeenCalledWith(42)
  })

  it('shows the cancel button and calls store.unattend() when attending', async () => {
    const store = useEventsStore()
    store.unattend = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ event: baseEvent({ attending: true }) })

    const button = wrapper.find('[data-testid="event-unattend-42"]')
    expect(button.exists()).toBe(true)

    await button.trigger('click')

    expect(store.unattend).toHaveBeenCalledWith(42)
  })

  it('does not throw when the store action rejects (revert + toast already handled by the store)', async () => {
    const store = useEventsStore()
    store.attend = vi.fn().mockRejectedValue(new Error('nope'))

    const wrapper = mountComponent({ event: baseEvent({ attending: false }) })
    await wrapper.find('[data-testid="event-attend-42"]').trigger('click')
    await flushPromises()

    expect(store.attend).toHaveBeenCalledTimes(1)
  })

  // Gap 17: a full-row highlight for events you're attending, in addition
  // to the badge (GroupEventScrollTable.vue's rowClass()/.attending).
  it('adds an "attending" row class when attending, not otherwise', () => {
    expect(mountComponent({ event: baseEvent({ attending: true }) }).classes()).toContain('attending')
    expect(mountComponent({ event: baseEvent({ attending: false }) }).classes()).not.toContain('attending')
  })

  describe('per-event numbers (gap 1/17) - table columns', () => {
    it('shows invited/volunteers cells for an upcoming (non-past) row when the backend provides them', () => {
      const wrapper = mountComponent({ event: baseEvent({ stats: { invited: 12, volunteers: 3 } }), past: false })

      expect(wrapper.find('[data-testid="event-card-stat-invited-42"]').text()).toContain('12')
      expect(wrapper.find('[data-testid="event-card-stat-volunteers-42"]').text()).toContain('3')
      expect(wrapper.find('[data-testid="event-card-stat-participants-42"]').exists()).toBe(false)
    })

    function pastEvent(statsOverrides = {}) {
      return baseEvent({
        start: '2020-01-01T10:00:00Z',
        end: '2020-01-01T12:00:00Z',
        stats: {
          participants: 5,
          volunteers: 2,
          waste_total: 12.4,
          co2_total: 30.6,
          fixed_devices: 3,
          repairable_devices: 1,
          dead_devices: 0,
          ...statsOverrides,
        },
      })
    }

    it('shows the past-columns row (participants/volunteers/waste/co2/device breakdown) for a finished event', () => {
      const wrapper = mountComponent({ event: pastEvent(), past: true })

      expect(wrapper.find('[data-testid="event-card-stat-participants-42"]').text()).toContain('5')
      expect(wrapper.find('[data-testid="event-card-stat-volunteers-42"]').text()).toContain('2')
      expect(wrapper.find('[data-testid="event-card-stat-waste-42"]').text()).toContain('12 kg')
      expect(wrapper.find('[data-testid="event-card-stat-co2-42"]').text()).toContain('31 kg')
      expect(wrapper.find('[data-testid="event-card-stat-fixed-42"]').text()).toContain('3')
      expect(wrapper.find('[data-testid="event-card-stat-repairable-42"]').text()).toContain('1')
      expect(wrapper.find('[data-testid="event-card-stat-dead-42"]').text()).toContain('0')
      expect(wrapper.find('[data-testid="event-card-stat-invited-42"]').exists()).toBe(false)
    })

    it('flags zero participants with a danger highlight (legacy dangerIfZero)', () => {
      const wrapper = mountComponent({ event: pastEvent({ participants: 0 }), past: true })

      expect(wrapper.find('[data-testid="event-card-stat-participants-42"]').classes()).toContain('bg-danger-subtle')
    })

    it('flags one-or-fewer volunteers with a danger highlight (legacy dangerIfOne)', () => {
      const wrapper = mountComponent({ event: pastEvent({ volunteers: 1 }), past: true })

      expect(wrapper.find('[data-testid="event-card-stat-volunteers-42"]').classes()).toContain('bg-danger-subtle')
    })

    it('does not flag participants/volunteers above their danger thresholds', () => {
      const wrapper = mountComponent({ event: pastEvent({ participants: 5, volunteers: 2 }), past: true })

      expect(wrapper.find('[data-testid="event-card-stat-participants-42"]').classes()).not.toContain('bg-danger-subtle')
      expect(wrapper.find('[data-testid="event-card-stat-volunteers-42"]').classes()).not.toContain('bg-danger-subtle')
    })

    // Gap 18: "No devices added / Add a device" replaces the device/waste/
    // co2 stat cells (colspan) instead of red-tinting them, when a finished
    // event recorded zero devices. GroupEventScrollTable.vue's noDevices()
    // also requires canedit - a read-only visitor shouldn't get an
    // "add a device" link they can't use.
    it('shows "No devices added / Add a device" (colspan 7) instead of the stat cells for a canedit viewer when a finished event recorded no devices at all', () => {
      const wrapper = mountComponent({ event: pastEvent({ fixed_devices: 0, repairable_devices: 0, dead_devices: 0 }), past: true, canedit: true })

      const cell = wrapper.find('[data-testid="event-card-no-devices"]')
      expect(cell.exists()).toBe(true)
      expect(cell.attributes('colspan')).toBe('7')
      expect(cell.text()).toContain('No devices added')
      expect(cell.text()).toContain('Add a device')
      expect(wrapper.find('[data-testid="event-card-stat-fixed-42"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-card-stat-waste-42"]').exists()).toBe(false)
    })

    it('shows the (zero) stat cells instead of the no-devices message for a non-canedit viewer', () => {
      const wrapper = mountComponent({ event: pastEvent({ fixed_devices: 0, repairable_devices: 0, dead_devices: 0 }), past: true, canedit: false })

      expect(wrapper.find('[data-testid="event-card-no-devices"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-card-stat-fixed-42"]').text()).toContain('0')
    })

    it('does not show the no-devices message when at least one device was recorded', () => {
      const wrapper = mountComponent({ event: pastEvent(), past: true })

      expect(wrapper.find('[data-testid="event-card-no-devices"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-card-stat-fixed-42"]').classes()).not.toContain('bg-danger-subtle')
    })

    it('renders cleanly with empty stat cells when a finished event has no stats block yet', () => {
      const wrapper = mountComponent({ event: baseEvent({ start: '2020-01-01T10:00:00Z', end: '2020-01-01T12:00:00Z' }), past: true })

      expect(wrapper.find('[data-testid="event-card-stat-participants-42"]').text()).toBe('')
      expect(wrapper.find('[data-testid="event-card-no-devices"]').exists()).toBe(false)
    })
  })
})
