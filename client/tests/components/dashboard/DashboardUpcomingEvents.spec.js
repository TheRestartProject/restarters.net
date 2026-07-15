import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import DashboardUpcomingEvents from '../../../app/components/dashboard/DashboardUpcomingEvents.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

const BBadgeStub = {
  template: '<span v-bind="$attrs"><slot /></span>',
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DashboardUpcomingEvents, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub },
    },
  })
}

describe('components/dashboard/DashboardUpcomingEvents', () => {
  it('renders an empty state when there are no upcoming events', () => {
    const wrapper = mountComponent({ events: [] })

    expect(wrapper.find('[data-testid="upcoming-events-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="upcoming-events-list"]').exists()).toBe(false)
  })

  it('renders events sorted soonest-first, linking to the event and its group', () => {
    const wrapper = mountComponent({
      events: [
        {
          id: 10,
          title: 'Later Event',
          start: '2026-08-20T10:00:00Z',
          end: '2026-08-20T12:00:00Z',
          attending: false,
          group: { id: 1, name: 'Group A' },
        },
        {
          id: 11,
          title: 'Sooner Event',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T12:00:00Z',
          attending: true,
          group: { id: 2, name: 'Group B' },
        },
      ],
    })

    const titles = wrapper.findAll('[data-testid="upcoming-events-list"] a').map((a) => a.text())
    expect(titles[0]).toBe('Sooner Event')

    expect(wrapper.find('[data-testid="upcoming-event-link-11"]').attributes('href')).toBe('/party/view/11')
    expect(wrapper.find('[data-testid="upcoming-event-attending-11"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="upcoming-event-attending-10"]').exists()).toBe(false)
  })

  it('links each event to its group page', () => {
    const wrapper = mountComponent({
      events: [
        {
          id: 12,
          title: 'An Event',
          start: '2026-08-01T10:00:00Z',
          end: '2026-08-01T12:00:00Z',
          attending: false,
          group: { id: 7, name: 'Group Seven' },
        },
      ],
    })

    const groupLink = wrapper
      .find('[data-testid="upcoming-event-12"]')
      .findAll('a')
      .find((a) => a.text() === 'Group Seven')

    expect(groupLink.attributes('href')).toBe('/group/view/7')
  })
})
