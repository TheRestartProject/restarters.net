import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupEventsList from '../../../app/components/groups/GroupEventsList.vue'
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

const PAST_ISO = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString()
const FUTURE_ISO = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString()

const EVENTS = [
  { id: 1, title: 'Past Cafe', start: PAST_ISO, end: PAST_ISO, location: 'Town Hall' },
  { id: 2, title: 'Future Cafe', start: FUTURE_ISO, end: FUTURE_ISO, location: 'Village Hall' },
]

describe('components/groups/GroupEventsList', () => {
  // GroupEventScrollTable.vue shows per-event stats on past events. The
  // columns are icon-only with the label as a tooltip, so they are asserted
  // by testid rather than by visible text.
  describe('per-event stats columns', () => {
    const WITH_STATS = [
      {
        id: 1,
        title: 'Past Cafe',
        start: PAST_ISO,
        end: PAST_ISO,
        location: 'Town Hall',
        stats: {
          participants: 12,
          volunteers: 4,
          waste_total: 37.4,
          co2_total: 128.6,
          fixed_devices: 7,
          repairable_devices: 2,
          dead_devices: 1,
        },
      },
    ]

    function pastTable(events) {
      const wrapper = mountComponent({ events })
      wrapper.find('[data-testid="group-events-tab-past"]').trigger('click')
      return wrapper
    }

    it('renders every stat column on past events', async () => {
      const wrapper = pastTable(WITH_STATS)
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-event-1-participants"]').text()).toBe('12')
      expect(wrapper.find('[data-testid="group-event-1-volunteers"]').text()).toBe('4')
      expect(wrapper.find('[data-testid="group-event-1-waste"]').text()).toBe('37 kg')
      expect(wrapper.find('[data-testid="group-event-1-co2"]').text()).toBe('129 kg')
      expect(wrapper.find('[data-testid="group-event-1-fixed"]').text()).toBe('7')
      expect(wrapper.find('[data-testid="group-event-1-repairable"]').text()).toBe('2')
      expect(wrapper.find('[data-testid="group-event-1-dead"]').text()).toBe('1')
    })

    it('leaves cells blank rather than showing zero when an event has no stats', async () => {
      const wrapper = pastTable([{ ...WITH_STATS[0], stats: undefined }])
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-event-1-participants"]').text()).toBe('')
      expect(wrapper.find('[data-testid="group-event-1-waste"]').text()).toBe('')
    })

    it('flags an event nobody attended, and one with a lone volunteer', async () => {
      const wrapper = pastTable([
        { ...WITH_STATS[0], stats: { ...WITH_STATS[0].stats, participants: 0, volunteers: 1 } },
      ])
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-event-1-participants"]').classes()).toContain('cell-danger')
      expect(wrapper.find('[data-testid="group-event-1-volunteers"]').classes()).toContain('cell-danger')
      expect(wrapper.find('[data-testid="group-event-1-fixed"]').classes()).not.toContain('cell-danger')
    })

    it('flags a finished event with no devices logged', async () => {
      const wrapper = pastTable([
        {
          ...WITH_STATS[0],
          stats: { ...WITH_STATS[0].stats, fixed_devices: 0, repairable_devices: 0, dead_devices: 0 },
        },
      ])
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="group-event-1-waste"]').classes()).toContain('cell-danger')
    })

    it('does not show stat columns on upcoming events', () => {
      const wrapper = mountComponent({ events: EVENTS })

      expect(wrapper.find('[data-testid="group-events-col-participants"]').exists()).toBe(false)
    })
  })

  it('shows a loading skeleton', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="group-events-loading"]').exists()).toBe(true)
  })

  it('defaults to the upcoming tab, splitting events by end date', () => {
    const wrapper = mountComponent({ events: EVENTS })

    expect(wrapper.find('[data-testid="group-events-panel-upcoming"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-event-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-event-1"]').exists()).toBe(false)
  })

  it('switches to the past tab and shows past events', async () => {
    const wrapper = mountComponent({ events: EVENTS })

    await wrapper.find('[data-testid="group-events-tab-past"]').trigger('click')

    expect(wrapper.find('[data-testid="group-events-panel-past"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-event-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-event-link-1"]').attributes('href')).toBe('/party/view/1')
  })

  it('shows empty states per tab when there are no events', async () => {
    const wrapper = mountComponent({ events: [] })

    expect(wrapper.find('[data-testid="group-events-empty-upcoming"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-events-tab-past"]').trigger('click')
    expect(wrapper.find('[data-testid="group-events-empty-past"]').exists()).toBe(true)
  })

  it('renders events as a sortable table with a location column (gap 5)', () => {
    const wrapper = mountComponent({ events: EVENTS })

    const table = wrapper.find('[data-testid="group-events-table-upcoming"]')
    expect(table.exists()).toBe(true)
    expect(table.text()).toContain('Village Hall')

    const header = table.find('th.sortable')
    expect(header.exists()).toBe(true)
  })

  it('toggling the sortable header reverses row order', async () => {
    const events = [
      { id: 3, title: 'Soonest', start: FUTURE_ISO, end: FUTURE_ISO, location: 'A' },
      {
        id: 4,
        title: 'Latest',
        start: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString(),
        end: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString(),
        location: 'B',
      },
    ]
    const wrapper = mountComponent({ events })

    const rowsBefore = wrapper.findAll('[data-testid="group-events-table-upcoming"] tbody tr')
    expect(rowsBefore[0].attributes('data-testid')).toBe('group-event-3')

    await wrapper.find('[data-testid="group-events-table-upcoming"] th.sortable').trigger('click')

    const rowsAfter = wrapper.findAll('[data-testid="group-events-table-upcoming"] tbody tr')
    expect(rowsAfter[0].attributes('data-testid')).toBe('group-event-4')
  })

  it('shows Add new event and Export event list only when canedit is true (gap 2, 5)', () => {
    const editor = mountComponent({ events: EVENTS, canedit: true })
    expect(editor.find('[data-testid="group-events-add"]').attributes('href')).toBe('/party/create/5')
    expect(editor.find('[data-testid="group-events-export"]').attributes('href')).toBe('/export/groups/5/events')

    const viewer = mountComponent({ events: EVENTS, canedit: false })
    expect(viewer.find('[data-testid="group-events-add"]').exists()).toBe(false)
    expect(viewer.find('[data-testid="group-events-export"]').exists()).toBe(false)
  })

  it('shows the group name + "Events" heading, with the calendar button beside it (parity: heading + icon)', () => {
    const wrapper = mountComponent({ events: EVENTS, showCalendar: true })

    const heading = wrapper.find('[data-testid="group-events-heading"]')
    expect(heading.text()).toBe('Fixers United Events')

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
    expect(wrapper.find('[data-testid="group-events-calendar-url"]').element.value).toBe('/calendar/group/5')
  })
})
