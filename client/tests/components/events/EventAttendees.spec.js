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
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventAttendees, {
    props: { eventId: 5, confirmed: [], invited: [], ...props },
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, BButton: BButtonStub },
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

  it('shows the attendee email only when volunteer.email is present (server-gated visibility)', () => {
    const withEmail = confirmedAttendee({ id: 1, volunteer: { id: 10, name: 'Sam', email: 'sam@example.com', user_skills: [] } })
    const withoutEmail = confirmedAttendee({ id: 2, user: 11, volunteer: { id: 11, name: 'Jo', user_skills: [] } })
    const wrapper = mountComponent({ confirmed: [withEmail, withoutEmail] })

    expect(wrapper.find('[data-testid="event-attendee-email-1"]').text()).toBe('sam@example.com')
    expect(wrapper.find('[data-testid="event-attendee-email-2"]').exists()).toBe(false)
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
})
