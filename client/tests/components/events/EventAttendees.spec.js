import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventAttendees from '../../../app/components/events/EventAttendees.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue"><slot /></div>',
}
const BPopoverStub = { props: ['target'], template: '<div><slot /></div>' }

function confirmedAttendee(overrides = {}) {
  return {
    id: 1,
    user: 10,
    fullName: null,
    role: 4,
    confirmed: true,
    profilePath: '/uploads/thumbnail_x.png',
    volunteer: { id: 10, name: 'Sam Jones', user_skills: [] },
    ...overrides,
  }
}

function mountComponent(props = {}) {
  // events.stat-0/stat-2 are new lang/en/events.php keys (gap D3) not yet in
  // the generated client i18n JSON - overlaid inline per
  // DashboardWhatsHappening.spec.js's pattern rather than editing
  // client/i18n/locales/*.json directly.
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: { ...en, ...clientEn, events: { ...en.events, 'stat-0': 'Participants', 'stat-2': 'Volunteers' } },
    },
  })

  return mount(EventAttendees, {
    props: { eventId: 5, confirmed: [], invited: [], ...props },
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, BButton: BButtonStub, BModal: BModalStub, BPopover: BPopoverStub },
    },
  })
}

describe('components/events/EventAttendees', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('shows a loading skeleton and no tabs while loading', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="event-attendees-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-attendees-tab-confirmed"]').exists()).toBe(false)
  })

  it('shows empty states on both tabs when there are no attendees', async () => {
    const wrapper = mountComponent()
    expect(wrapper.find('[data-testid="event-attendees-empty-confirmed"]').exists()).toBe(true)

    await wrapper.find('[data-testid="event-attendees-tab-invited"]').trigger('click')
    expect(wrapper.find('[data-testid="event-attendees-empty-invited"]').exists()).toBe(true)
  })

  it('renders a confirmed attendee with a host badge only when role is HOST', () => {
    const host = confirmedAttendee({ id: 1, role: 3 })
    const guest = confirmedAttendee({ id: 2, role: 4, user: 11 })
    const wrapper = mountComponent({ confirmed: [host, guest] })

    expect(wrapper.find('[data-testid="event-attendee-host-badge-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-attendee-host-badge-2"]').exists()).toBe(false)
  })

  // Audit fix: develop's EventAttendee.vue never shows email on the
  // confirmed-attendee row at all (only inside the add-volunteer modal,
  // which is backend-blocked and unbuilt here) - this used to render it
  // inline whenever the (server-gated) field was present, which was more
  // exposure than develop's own UI.
  it('never renders the attendee email, even when volunteer.email is present', () => {
    const withEmail = confirmedAttendee({ id: 1, volunteer: { id: 10, name: 'Sam', email: 'sam@example.com', user_skills: [] } })
    const wrapper = mountComponent({ confirmed: [withEmail] })

    expect(wrapper.find('[data-testid="event-attendee-email-1"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('sam@example.com')
  })

  it('renders a manually-added (unregistered) volunteer using fullName, with no profile link', () => {
    const manual = confirmedAttendee({ id: 3, user: null, fullName: 'Manual Volunteer', volunteer: null })
    const wrapper = mountComponent({ confirmed: [manual] })

    const row = wrapper.find('[data-testid="event-attendee-3"]')
    expect(row.text()).toContain('Manual Volunteer')
    expect(row.find('a').exists()).toBe(false)
  })

  it('does not show a remove button when canedit is false', () => {
    const wrapper = mountComponent({ confirmed: [confirmedAttendee()], canedit: false })
    expect(wrapper.find('[data-testid="event-attendee-remove-1"]').exists()).toBe(false)
  })

  it('removes an attendee via the store after confirm, with an intermediate confirm step', async () => {
    const store = useEventsStore()
    store.removeAttendee = vi.fn().mockResolvedValue()

    const wrapper = mountComponent({ confirmed: [confirmedAttendee()], canedit: true })

    await wrapper.find('[data-testid="event-attendee-remove-1"]').trigger('click')
    expect(wrapper.find('[data-testid="event-attendee-remove-confirm-1"]').exists()).toBe(true)

    await wrapper.find('[data-testid="event-attendee-remove-confirm-1"]').trigger('click')
    expect(store.removeAttendee).toHaveBeenCalledWith(5, 1)
  })

  it('shows the invite-to-join link only when canedit, upcoming and approved are all true', async () => {
    const invited = [confirmedAttendee({ id: 9 })]

    const notEditable = mountComponent({ invited, canedit: false, upcoming: true, approved: true })
    await notEditable.find('[data-testid="event-attendees-tab-invited"]').trigger('click')
    expect(notEditable.find('[data-testid="event-attendees-invite-link"]').exists()).toBe(false)

    const editable = mountComponent({ invited, canedit: true, upcoming: true, approved: true })
    await editable.find('[data-testid="event-attendees-tab-invited"]').trigger('click')
    expect(editable.find('[data-testid="event-attendees-invite-link"]').exists()).toBe(true)
  })

  it('emits invite when the invite-to-join link is clicked', async () => {
    const wrapper = mountComponent({ invited: [confirmedAttendee({ id: 9 })], canedit: true, upcoming: true, approved: true })
    await wrapper.find('[data-testid="event-attendees-tab-invited"]').trigger('click')

    await wrapper.find('[data-testid="event-attendees-invite-link"]').trigger('click')
    expect(wrapper.emitted('invite')).toBeTruthy()
  })

  // Gap 14: EventAttendance.vue's "Add volunteer" link (confirmed tab,
  // !upcoming events only) - gated on canedit too (see EventAttendees.vue's
  // doc comment for why, unlike legacy).
  describe('add-volunteer trigger (gap 14)', () => {
    it('shows the link for a canedit viewer on a non-upcoming event, and emits add-volunteer on click', async () => {
      const wrapper = mountComponent({ upcoming: false, canedit: true })

      const link = wrapper.find('[data-testid="event-attendees-add-volunteer-link"]')
      expect(link.exists()).toBe(true)

      await link.trigger('click')
      expect(wrapper.emitted('add-volunteer')).toBeTruthy()
    })

    it('hides the link for an upcoming event, even when canedit', () => {
      const wrapper = mountComponent({ upcoming: true, canedit: true })
      expect(wrapper.find('[data-testid="event-attendees-add-volunteer-link"]').exists()).toBe(false)
    })

    it('hides the link for a non-editing viewer, even on a non-upcoming event', () => {
      const wrapper = mountComponent({ upcoming: false, canedit: false })
      expect(wrapper.find('[data-testid="event-attendees-add-volunteer-link"]').exists()).toBe(false)
    })
  })

  // Gap 21: develop's !upcoming grid condition shows headcounts for
  // in-progress events too, not just finished ones.
  describe('total headcounts (gap D3/21)', () => {
    it('shows participant/volunteer counts when finished and the counts are available', () => {
      const wrapper = mountComponent({ upcoming: false, finished: true, participants: 12, volunteers: 3 })

      expect(wrapper.find('[data-testid="event-attendees-participants"]').text()).toBe('12Participants')
      expect(wrapper.find('[data-testid="event-attendees-volunteers"]').text()).toBe('3Volunteers')
    })

    it('shows the counts for an in-progress event too (not upcoming, not finished)', () => {
      const wrapper = mountComponent({ upcoming: false, finished: false, participants: 4, volunteers: 1 })

      expect(wrapper.find('[data-testid="event-attendees-headcounts"]').exists()).toBe(true)
    })

    it('does not show headcounts for an upcoming event', () => {
      const wrapper = mountComponent({ upcoming: true, finished: false, participants: 12, volunteers: 3 })
      expect(wrapper.find('[data-testid="event-attendees-headcounts"]').exists()).toBe(false)
    })

    it('does not show headcounts when the counts are unavailable, even if finished', () => {
      const wrapper = mountComponent({ upcoming: false, finished: true, participants: null, volunteers: null })
      expect(wrapper.find('[data-testid="event-attendees-headcounts"]').exists()).toBe(false)
    })

    it('shows just the volunteer count when only that is available', () => {
      const wrapper = mountComponent({ upcoming: false, finished: true, participants: null, volunteers: 3 })
      expect(wrapper.find('[data-testid="event-attendees-participants"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-attendees-volunteers"]').text()).toBe('3Volunteers')
    })

    // Gap 13: EventAttendanceCount.vue's +/- stepper - editable for a
    // canedit viewer, plain text otherwise.
    it('shows editable +/- steppers for a canedit viewer, and emits update-participants/update-volunteers', async () => {
      const wrapper = mountComponent({ upcoming: false, finished: true, participants: 12, volunteers: 3, canedit: true })

      expect(wrapper.find('[data-testid="event-attendees-participants-count-inc"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-attendees-volunteers-count-inc"]').exists()).toBe(true)

      await wrapper.find('[data-testid="event-attendees-participants-count-inc"]').trigger('click')
      expect(wrapper.emitted('update-participants')).toEqual([[13]])

      await wrapper.find('[data-testid="event-attendees-volunteers-count-dec"]').trigger('click')
      expect(wrapper.emitted('update-volunteers')).toEqual([[2]])
    })

    it('shows plain-text counts (no stepper) for a non-editing viewer', () => {
      const wrapper = mountComponent({ upcoming: false, finished: true, participants: 12, volunteers: 3, canedit: false })

      expect(wrapper.find('[data-testid="event-attendees-participants-count-inc"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-attendees-volunteers-count-inc"]').exists()).toBe(false)
    })
  })

  // Gap 22: bordered avatar, plain uppercase "Host" text, and a real
  // confirm modal (not an inline Yes/Cancel row) for the remove button.
  describe('attendee row treatment (gap 22)', () => {
    it('shows a plain uppercase Host label (not a badge) only for role HOST', () => {
      const host = confirmedAttendee({ id: 1, role: 3 })
      const guest = confirmedAttendee({ id: 2, role: 4, user: 11 })
      const wrapper = mountComponent({ confirmed: [host, guest] })

      expect(wrapper.find('[data-testid="event-attendee-host-badge-1"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-attendee-host-badge-2"]').exists()).toBe(false)
    })

    it('opens a shared confirm modal (not an inline row) when removing, and calls the store on confirm', async () => {
      const store = useEventsStore()
      store.removeAttendee = vi.fn().mockResolvedValue()

      const wrapper = mountComponent({ confirmed: [confirmedAttendee({ id: 7 })], canedit: true })

      expect(wrapper.find('[data-testid="event-attendee-remove-modal"]').exists()).toBe(false)

      await wrapper.find('[data-testid="event-attendee-remove-7"]').trigger('click')
      expect(wrapper.find('[data-testid="event-attendee-remove-modal"]').exists()).toBe(true)

      await wrapper.find('[data-testid="event-attendee-remove-confirm-7"]').trigger('click')
      expect(store.removeAttendee).toHaveBeenCalledWith(5, 7)
    })
  })
})
