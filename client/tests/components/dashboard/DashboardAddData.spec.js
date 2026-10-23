import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import DashboardAddData from '../../../app/components/dashboard/DashboardAddData.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DashboardAddData, {
    props,
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } },
  })
}

// The group/event pickers are TagMultiselect.vue instances (finding #13 -
// develop uses vue-multiselect here, not a native <select>), each wrapped in
// a `[data-testid="add-data-group"]`/`[data-testid="add-data-event"]`
// container so the two pickers' internal (identically-named) testids don't
// collide. Open a picker by focusing its search input, then read the
// `.multiselect__option` labels.
function optionLabels(wrapper, testid) {
  return wrapper
    .find(`[data-testid="${testid}"]`)
    .findAll('.multiselect__option')
    .map((o) => o.text())
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

  it('is hidden when the user has events but no groups to add data against (no empty dropdowns)', () => {
    // Regression: a user with events but no group membership previously saw the
    // card with empty group/event selects; hide it when there is nothing to pick.
    const wrapper = mountComponent({ groups: [], events: EVENTS })

    expect(wrapper.find('[data-testid="dashboard-add-data"]').exists()).toBe(false)
  })

  it('offers only groups that have events, newest event first, and links Add to that event', async () => {
    const wrapper = mountComponent({ groups: GROUPS, events: EVENTS })

    expect(wrapper.find('[data-testid="dashboard-add-data"]').exists()).toBe(true)

    // Only groups 1 + 2 have events (group 3 excluded), sorted A-Z.
    await wrapper.find('[data-testid="add-data-group"] input').trigger('focus')
    expect(optionLabels(wrapper, 'add-data-group')).toEqual(['Alpha Fixers', 'Zeta Repairs'])

    // Default group's events, newest first.
    await wrapper.find('[data-testid="add-data-event"] input').trigger('focus')
    expect(optionLabels(wrapper, 'add-data-event')).toEqual(['Alpha Event New', 'Alpha Event Old'])

    // Add link targets the selected (newest) event's view page.
    expect(wrapper.find('[data-testid="add-data-add"]').attributes('href')).toBe('/party/view/11')
  })
})
