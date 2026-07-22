import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupsTabsNav from '../../../app/components/groups/GroupsTabsNav.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountNav(active, slots) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupsTabsNav, {
    props: { active },
    slots,
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } },
  })
}

describe('components/groups/GroupsTabsNav', () => {
  // Legacy's b-tabs only ever has three panels (Mine/Nearby/All) - no "Map"
  // tab exists there at all (parity-v2/groups-lists.md gap #1). /group/map
  // is a deliberate net-new Nuxt-only route (design.md §6.2 B7) and is
  // intentionally not part of this shared bar; 'active' still accepts
  // 'map' (see this component's own doc comment) so /group/map's existing
  // usage doesn't warn, it just highlights nothing here.
  it('renders exactly the three legacy tabs, linked to their routes', () => {
    const wrapper = mountNav('mine')

    expect(wrapper.find('[data-testid="groups-tab-mine"]').attributes('href')).toBe('/group')
    expect(wrapper.find('[data-testid="groups-tab-nearby"]').attributes('href')).toBe('/group/nearby')
    expect(wrapper.find('[data-testid="groups-tab-all"]').attributes('href')).toBe('/group/all')
    expect(wrapper.find('[data-testid="groups-tab-map"]').exists()).toBe(false)
  })

  it('highlights no tab when active is "map"', () => {
    const wrapper = mountNav('map')

    expect(wrapper.find('[data-testid="groups-tab-mine"]').classes()).not.toContain('active')
    expect(wrapper.find('[data-testid="groups-tab-nearby"]').classes()).not.toContain('active')
    expect(wrapper.find('[data-testid="groups-tab-all"]').classes()).not.toContain('active')
  })

  it('renders resolved translation labels, not raw i18n keys', () => {
    // Regression guard: the /group/all tab used a non-existent key
    // (groups.all_groups) and rendered the raw path, which CSS uppercased to
    // "GROUPS.ALL_GROUPS" in the UI. Every tab label must resolve to real text.
    const wrapper = mountNav('mine')

    expect(wrapper.find('[data-testid="groups-tab-mine"]').text()).toBe('Your Groups')
    expect(wrapper.find('[data-testid="groups-tab-nearby"]').text()).toBe('Find a group')
    expect(wrapper.find('[data-testid="groups-tab-all"]').text()).toBe('All groups')
  })

  // gap #2: legacy's .ourtabs border/shadow box wraps the tab-content as
  // well as the nav - the slot lets pages put their loading/error/table
  // content inside that same box rather than bare on the page background.
  it('renders slot content inside the tabs panel', () => {
    const wrapper = mountNav('mine', { default: '<p data-testid="slot-content">Body</p>' })

    expect(wrapper.find('[data-testid="groups-tabs-panel"] [data-testid="slot-content"]').exists()).toBe(true)
  })
})
