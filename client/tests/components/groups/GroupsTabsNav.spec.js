import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupsTabsNav from '../../../app/components/groups/GroupsTabsNav.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountNav(active) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupsTabsNav, {
    props: { active },
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } },
  })
}

describe('components/groups/GroupsTabsNav', () => {
  it('renders all four tabs, linked to their routes', () => {
    const wrapper = mountNav('mine')

    expect(wrapper.find('[data-testid="groups-tab-mine"]').attributes('href')).toBe('/group')
    expect(wrapper.find('[data-testid="groups-tab-nearby"]').attributes('href')).toBe('/group/nearby')
    expect(wrapper.find('[data-testid="groups-tab-all"]').attributes('href')).toBe('/group/all')
    expect(wrapper.find('[data-testid="groups-tab-map"]').attributes('href')).toBe('/group/map')
  })

  it('marks the map tab active on /group/map', () => {
    const wrapper = mountNav('map')

    expect(wrapper.find('[data-testid="groups-tab-map"]').classes()).toContain('active')
    expect(wrapper.find('[data-testid="groups-tab-mine"]').classes()).not.toContain('active')
  })

  it('renders resolved translation labels, not raw i18n keys', () => {
    // Regression guard: the /group/all tab used a non-existent key
    // (groups.all_groups) and rendered the raw path, which CSS uppercased to
    // "GROUPS.ALL_GROUPS" in the UI. Every tab label must resolve to real text.
    const wrapper = mountNav('mine')

    expect(wrapper.find('[data-testid="groups-tab-mine"]').text()).toBe('Your Groups')
    expect(wrapper.find('[data-testid="groups-tab-nearby"]').text()).toBe('Other groups nearby')
    expect(wrapper.find('[data-testid="groups-tab-all"]').text()).toBe('All groups')
    expect(wrapper.find('[data-testid="groups-tab-map"]').text()).toBe('Map')
  })
})
