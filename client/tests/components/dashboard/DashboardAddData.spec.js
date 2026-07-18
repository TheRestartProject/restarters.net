import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import DashboardAddData from '../../../app/components/dashboard/DashboardAddData.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BFormSelectStub = {
  props: ['modelValue', 'options'],
  emits: ['update:modelValue'],
  template: '<select><option v-for="o in options" :key="o.value" :value="o.value">{{ o.text }}</option></select>',
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DashboardAddData, {
    props,
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub, BFormSelect: BFormSelectStub } },
  })
}

const GROUPS = [
  { id: 1, name: 'Alpha Fixers' },
  { id: 2, name: 'Zeta Repairs' },
  { id: 3, name: 'No Events Group' },
]
const EVENTS = [
  { id: 10, title: 'Alpha Event Old', group: { id: 1, name: 'Alpha Fixers' }, start: '2024-01-01T10:00:00Z' },
  { id: 11, title: 'Alpha Event New', group: { id: 1, name: 'Alpha Fixers' }, start: '2024-06-01T10:00:00Z' },
  { id: 20, title: 'Zeta Event', group: { id: 2, name: 'Zeta Repairs' }, start: '2024-03-01T10:00:00Z' },
]

describe('components/dashboard/DashboardAddData', () => {
  it('is hidden when the user has no events', () => {
    const wrapper = mountComponent({ groups: GROUPS, events: [] })

    expect(wrapper.find('[data-testid="dashboard-add-data"]').exists()).toBe(false)
  })

  it('offers only groups that have events, newest event first, and links Add to that event', () => {
    const wrapper = mountComponent({ groups: GROUPS, events: EVENTS })

    expect(wrapper.find('[data-testid="dashboard-add-data"]').exists()).toBe(true)

    // Only groups 1 + 2 have events (group 3 excluded), sorted A-Z.
    const groupOpts = wrapper.find('[data-testid="add-data-group"]').findAll('option')
    expect(groupOpts.map((o) => o.text())).toEqual(['Alpha Fixers', 'Zeta Repairs'])

    // Default group's events, newest first.
    const eventOpts = wrapper.find('[data-testid="add-data-event"]').findAll('option')
    expect(eventOpts.map((o) => o.text())).toEqual(['Alpha Event New', 'Alpha Event Old'])

    // Add link targets the selected (newest) event's view page.
    expect(wrapper.find('[data-testid="add-data-add"]').attributes('href')).toBe('/party/view/11')
  })
})
