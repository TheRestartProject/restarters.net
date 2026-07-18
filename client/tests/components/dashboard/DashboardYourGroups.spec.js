import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import DashboardYourGroups from '../../../app/components/dashboard/DashboardYourGroups.vue'
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

  return mount(DashboardYourGroups, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub },
    },
  })
}

describe('components/dashboard/DashboardYourGroups', () => {
  it('renders the empty state with a link to browse groups when there are no groups', () => {
    const wrapper = mountComponent({ groups: [] })

    expect(wrapper.find('[data-testid="your-groups-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="your-groups-list"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="your-groups-browse-link"]').exists()).toBe(true)
  })

  it('renders groups sorted alphabetically, linking each to its group view page', () => {
    const wrapper = mountComponent({
      groups: [
        { id: 1, name: 'Zeta Repairs', role: 4, archived: false, image_url: null },
        { id: 2, name: 'Alpha Fixers', role: 3, archived: false, image_url: null },
      ],
    })

    const names = wrapper.findAll('[data-testid="your-groups-list"] a').map((a) => a.text())
    expect(names[0]).toBe('Alpha Fixers')

    expect(wrapper.find('[data-testid="your-group-link-2"]').attributes('href')).toBe('/group/view/2')
    expect(wrapper.find('[data-testid="your-groups-see-all"]').attributes('href')).toBe('/group')
  })

  it('shows a role badge for each group', () => {
    const wrapper = mountComponent({
      groups: [{ id: 1, name: 'Host Group', role: 3, archived: false, image_url: null }],
    })

    expect(wrapper.find('[data-testid="your-group-role-1"]').text()).toBe('Host')
  })

  it('shows an archived badge only for archived groups', () => {
    const wrapper = mountComponent({
      groups: [
        { id: 1, name: 'Active Group', role: 4, archived: false, image_url: null },
        { id: 2, name: 'Old Group', role: 4, archived: true, image_url: null },
      ],
    })

    expect(wrapper.find('[data-testid="your-group-archived-1"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="your-group-archived-2"]').exists()).toBe(true)
  })

  it('shows the newly-added highlight banner only when there are new nearby groups', () => {
    const withNew = mountComponent({
      groups: [],
      newNearbyGroups: [{ id: 9, name: 'New Group', distance: 1, location: 'Leeds', image_url: null }],
    })
    expect(withNew.find('[data-testid="your-groups-new-highlight"]').exists()).toBe(true)

    const withoutNew = mountComponent({ groups: [], newNearbyGroups: [] })
    expect(withoutNew.find('[data-testid="your-groups-new-highlight"]').exists()).toBe(false)
  })
})
