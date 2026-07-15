import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupEventsList from '../../../app/components/groups/GroupEventsList.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupEventsList, {
    props,
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } },
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
})
