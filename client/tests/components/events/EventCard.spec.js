import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventCard from '../../../app/components/events/EventCard.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

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

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

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

  describe('per-event numbers (gap 17)', () => {
    it('shows invited/volunteers chips for an upcoming event when the backend provides them', () => {
      const wrapper = mountComponent({ event: baseEvent({ stats: { invited: 12, volunteers: 3 } }) })

      expect(wrapper.find('[data-testid="event-card-upcoming-stats-42"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-card-stat-invited-42"]').text()).toContain('12')
      expect(wrapper.find('[data-testid="event-card-stat-volunteers-42"]').text()).toContain('3')
      expect(wrapper.find('[data-testid="event-card-past-stats-42"]').exists()).toBe(false)
    })

    it('renders no upcoming-stats row when the event carries no stats block', () => {
      const wrapper = mountComponent()

      expect(wrapper.find('[data-testid="event-card-upcoming-stats-42"]').exists()).toBe(false)
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

    it('shows the past stats row (participants/volunteers/waste/co2/device breakdown) for a finished event', () => {
      const wrapper = mountComponent({ event: pastEvent() })

      expect(wrapper.find('[data-testid="event-card-past-stats-42"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-card-stat-participants-42"]').text()).toContain('5')
      expect(wrapper.find('[data-testid="event-card-stat-volunteers-42"]').text()).toContain('2')
      expect(wrapper.find('[data-testid="event-card-stat-waste-42"]').text()).toContain('12 kg')
      expect(wrapper.find('[data-testid="event-card-stat-co2-42"]').text()).toContain('31 kg')
      expect(wrapper.find('[data-testid="event-card-stat-fixed-42"]').text()).toContain('3')
      expect(wrapper.find('[data-testid="event-card-stat-repairable-42"]').text()).toContain('1')
      expect(wrapper.find('[data-testid="event-card-stat-dead-42"]').text()).toContain('0')
      expect(wrapper.find('[data-testid="event-card-upcoming-stats-42"]').exists()).toBe(false)
    })

    it('flags zero participants with a danger highlight (legacy dangerIfZero)', () => {
      const wrapper = mountComponent({ event: pastEvent({ participants: 0 }) })

      expect(wrapper.find('[data-testid="event-card-stat-participants-42"]').classes()).toContain('bg-danger-subtle')
    })

    it('flags one-or-fewer volunteers with a danger highlight (legacy dangerIfOne)', () => {
      const wrapper = mountComponent({ event: pastEvent({ volunteers: 1 }) })

      expect(wrapper.find('[data-testid="event-card-stat-volunteers-42"]').classes()).toContain('bg-danger-subtle')
    })

    it('does not flag participants/volunteers above their danger thresholds', () => {
      const wrapper = mountComponent({ event: pastEvent({ participants: 5, volunteers: 2 }) })

      expect(wrapper.find('[data-testid="event-card-stat-participants-42"]').classes()).not.toContain('bg-danger-subtle')
      expect(wrapper.find('[data-testid="event-card-stat-volunteers-42"]').classes()).not.toContain('bg-danger-subtle')
    })

    it('flags all three device counts with a danger highlight when a finished event recorded no devices at all', () => {
      const wrapper = mountComponent({ event: pastEvent({ fixed_devices: 0, repairable_devices: 0, dead_devices: 0 }) })

      expect(wrapper.find('[data-testid="event-card-stat-fixed-42"]').classes()).toContain('bg-danger-subtle')
      expect(wrapper.find('[data-testid="event-card-stat-repairable-42"]').classes()).toContain('bg-danger-subtle')
      expect(wrapper.find('[data-testid="event-card-stat-dead-42"]').classes()).toContain('bg-danger-subtle')
    })

    it('does not flag the device counts when at least one device was recorded', () => {
      const wrapper = mountComponent({ event: pastEvent() })

      expect(wrapper.find('[data-testid="event-card-stat-fixed-42"]').classes()).not.toContain('bg-danger-subtle')
    })

    it('renders cleanly with no stats row when a finished event has no stats block yet', () => {
      const wrapper = mountComponent({ event: baseEvent({ start: '2020-01-01T10:00:00Z', end: '2020-01-01T12:00:00Z' }) })

      expect(wrapper.find('[data-testid="event-card-past-stats-42"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-card-upcoming-stats-42"]').exists()).toBe(false)
    })
  })
})
