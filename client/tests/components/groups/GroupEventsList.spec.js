import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it } from 'vitest'
import GroupEventsList from '../../../app/components/groups/GroupEventsList.vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupEventsList, {
    props: { groupId: 5, groupName: 'Fixers United', ...props },
    global: { plugins: [i18n], stubs: GROUP_VIEW_STUBS },
  })
}

const DAY = 24 * 60 * 60 * 1000
const PAST_ISO = new Date(Date.now() - 7 * DAY).toISOString()
const FUTURE_ISO = new Date(Date.now() + 7 * DAY).toISOString()

const EVENTS = [
  { id: 1, title: 'Past Cafe', start: PAST_ISO, end: PAST_ISO, location: 'Town Hall', group: { id: 5, name: 'Fixers United' } },
  { id: 2, title: 'Future Cafe', start: FUTURE_ISO, end: FUTURE_ISO, location: 'Village Hall', group: { id: 5, name: 'Fixers United' } },
]

function rowIds(wrapper) {
  return wrapper.findAll('tbody tr').map((row) => row.attributes('data-testid'))
}

describe('components/groups/GroupEventsList', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('shows a loading skeleton', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="group-events-loading"]').exists()).toBe(true)
  })

  it('defaults to the upcoming tab, splitting events by end date', () => {
    const wrapper = mountComponent({ events: EVENTS })

    expect(wrapper.find('[data-testid="group-events-panel-upcoming"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-1"]').exists()).toBe(false)
  })

  it('switches to the past tab and shows past events', async () => {
    const wrapper = mountComponent({ events: EVENTS })
    await wrapper.find('[data-testid="group-events-tab-past"]').trigger('click')

    expect(wrapper.find('[data-testid="group-events-panel-past"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-link-1"]').attributes('href')).toBe('/party/view/1')
  })

  it('shows empty states per tab when there are no events', async () => {
    const wrapper = mountComponent({ events: [] })
    expect(wrapper.find('[data-testid="events-list-empty"]').text()).toBe(en.groups.no_upcoming_events)

    await wrapper.find('[data-testid="group-events-tab-past"]').trigger('click')
    expect(wrapper.find('[data-testid="events-list-empty"]').text()).toBe(en.groups.no_past_events)
  })

  // GroupEventScrollTable.vue's rows: date box, long date/time/timezone, title,
  // invited and volunteers, then "You're going!" for events you're attending.
  it('shows live\'s upcoming row: date, time and timezone, counts and "You\'re going!"', () => {
    const wrapper = mountComponent({
      events: [{ ...EVENTS[1], timezone: 'Europe/London', attending: true, stats: { invited: 3, volunteers: 2 } }],
    })

    expect(wrapper.find('[data-testid="event-card-date-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-card-datelong-2"]').text()).toContain('Europe/London')
    expect(wrapper.find('[data-testid="event-card-stat-invited-2"]').text()).toBe('3')
    expect(wrapper.find('[data-testid="event-card-stat-volunteers-2"]').text()).toBe('2')
    expect(wrapper.find('[data-testid="event-attending-text-2"]').text()).toBe(en.events.youre_going)
    expect(wrapper.find('[data-testid="event-card-2"]').classes()).toContain('attending')
  })

  // addGroupName is off on the group's own page: every row is this group.
  it('leaves out the group name under each title', () => {
    const wrapper = mountComponent({ events: EVENTS })

    expect(wrapper.find('[data-testid="event-card-group-2"]').exists()).toBe(false)
  })

  it('shows the per-event stats on past events', async () => {
    const wrapper = mountComponent({
      events: [{ ...EVENTS[0], stats: { participants: 12, volunteers: 4, waste_total: 37.4, co2_total: 128.6, fixed_devices: 7, repairable_devices: 2, dead_devices: 1 } }],
    })
    await wrapper.find('[data-testid="group-events-tab-past"]').trigger('click')

    expect(wrapper.find('[data-testid="event-card-stat-participants-1"]').text()).toBe('12')
    expect(wrapper.find('[data-testid="event-card-stat-waste-1"]').text()).toBe('37 kg')
    expect(wrapper.find('[data-testid="event-card-stat-dead-1"]').text()).toBe('1')
  })

  it('starts upcoming soonest first and past most recent first, and sorts by a clicked column', async () => {
    const events = [
      { id: 3, title: 'B', start: new Date(Date.now() + 9 * DAY).toISOString(), end: new Date(Date.now() + 9 * DAY).toISOString(), stats: { invited: 1 } },
      { id: 4, title: 'A', start: new Date(Date.now() + 2 * DAY).toISOString(), end: new Date(Date.now() + 2 * DAY).toISOString(), stats: { invited: 5 } },
      { id: 5, title: 'C', start: new Date(Date.now() - 9 * DAY).toISOString(), end: new Date(Date.now() - 9 * DAY).toISOString() },
      { id: 6, title: 'D', start: new Date(Date.now() - 2 * DAY).toISOString(), end: new Date(Date.now() - 2 * DAY).toISOString() },
    ]
    const wrapper = mountComponent({ events })

    expect(rowIds(wrapper)).toEqual(['event-card-4', 'event-card-3'])

    await wrapper.find('[data-testid="events-list-sort-invited"]').trigger('click')
    expect(rowIds(wrapper)).toEqual(['event-card-3', 'event-card-4'])

    await wrapper.find('[data-testid="group-events-tab-past"]').trigger('click')
    expect(rowIds(wrapper)).toEqual(['event-card-6', 'event-card-5'])
  })

  // RSVPing from here goes through the events store, which doesn't hold this
  // list - so the row would keep offering RSVP without this.
  it('marks an event as attended in the group\'s list after an RSVP from it', async () => {
    const groupsStore = useGroupsStore()
    groupsStore.events.data = [EVENTS[1]]
    const wrapper = mountComponent({ events: groupsStore.events.data })

    wrapper.findComponent({ name: 'EventsList' }).vm.$emit('attendance', { id: 2, attending: true })

    expect(groupsStore.events.data[0].attending).toBe(true)
  })

  it('shows Add new event and Export event list only when canedit is true (gap 2, 5)', () => {
    const editor = mountComponent({ events: EVENTS, canedit: true })
    expect(editor.find('[data-testid="group-events-add"]').attributes('href')).toBe('/party/create/5')
    expect(editor.find('[data-testid="group-events-export"]').attributes('href')).toBe('/export/groups/5/events')

    const viewer = mountComponent({ events: EVENTS, canedit: false })
    expect(viewer.find('[data-testid="group-events-add"]').exists()).toBe(false)
    expect(viewer.find('[data-testid="group-events-export"]').exists()).toBe(false)
  })

  // develop #946: the group name starts the heading, so "events" is lower case, in sentence case like the rest of
  // the page.
  it('shows the group name + "events" heading, with the calendar button beside it (parity: heading + icon)', () => {
    const wrapper = mountComponent({ events: EVENTS, showCalendar: true })

    const heading = wrapper.find('[data-testid="group-events-heading"]')
    expect(heading.text()).toBe('Fixers United events')

    // The calendar-subscribe button sits next to the heading, not grouped
    // with the export/add-event actions on the right.
    const headingRow = heading.element.parentElement
    expect(headingRow.querySelector('[data-testid="group-events-calendar"]')).not.toBeNull()
  })

  it('wraps the tab nav and panels in a bordered box (parity: tabs styling)', () => {
    const wrapper = mountComponent({ events: EVENTS })

    const box = wrapper.find('[data-testid="group-events-tabs"]')
    expect(box.exists()).toBe(true)
    expect(box.classes()).toContain('events-tabs')
    expect(box.find('[data-testid="group-events-tab-upcoming"]').exists()).toBe(true)
    expect(box.find('[data-testid="group-events-panel-upcoming"]').exists()).toBe(true)
  })

  it('shows a calendar-subscribe button and modal only when showCalendar is true (gap 5)', async () => {
    const hidden = mountComponent({ events: EVENTS, showCalendar: false })
    expect(hidden.find('[data-testid="group-events-calendar"]').exists()).toBe(false)

    const wrapper = mountComponent({ events: EVENTS, showCalendar: true })
    expect(wrapper.find('[data-testid="group-events-calendar"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-events-calendar-modal"]').exists()).toBe(false)

    await wrapper.find('[data-testid="group-events-calendar"]').trigger('click')
    expect(wrapper.find('[data-testid="group-events-calendar-modal"]').exists()).toBe(true)
  })

  // develop passes url("/calendar/group/{id}"): a calendar app can't subscribe
  // to a bare path.
  it('offers the full calendar address, and links to your calendar settings', async () => {
    useAuthStore().user = { id: 42 }
    const wrapper = mountComponent({ events: EVENTS, showCalendar: true })
    await wrapper.find('[data-testid="group-events-calendar"]').trigger('click')

    expect(wrapper.find('[data-testid="group-events-calendar-url"]').element.value).toBe(`${window.location.origin}/calendar/group/5`)
    expect(wrapper.find('[data-testid="group-events-calendar-see-all"]').attributes('href')).toBe('/profile/edit/42')
  })
})
