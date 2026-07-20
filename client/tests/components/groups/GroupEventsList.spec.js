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
