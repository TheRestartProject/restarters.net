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
  // PR 887 (RES-1995) reworks the groups page to exactly two tabs: "Your
  // Groups" and "Find a group", where "Find a group" IS the map+list. The
  // port's earlier three-list-tabs layout (mine/nearby/all, map on an
  // unlinked side route) matched pre-887 develop, not the 887 page this
  // branch is integrating - /group/nearby and /group/all now redirect to
  // /group/map.
  it('renders exactly the two 887 tabs: Your Groups and Find a group (the map)', () => {
    const wrapper = mountNav('mine')

    expect(wrapper.find('[data-testid="groups-tab-mine"]').attributes('href')).toBe('/group')
    expect(wrapper.find('[data-testid="groups-tab-map"]').attributes('href')).toBe('/group/map')
    expect(wrapper.find('[data-testid="groups-tab-nearby"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="groups-tab-all"]').exists()).toBe(false)
  })

  it('highlights the active tab', () => {
    const mine = mountNav('mine')
    expect(mine.find('[data-testid="groups-tab-mine"]').classes()).toContain('active')
    expect(mine.find('[data-testid="groups-tab-map"]').classes()).not.toContain('active')

    const map = mountNav('map')
    expect(map.find('[data-testid="groups-tab-map"]').classes()).toContain('active')
    expect(map.find('[data-testid="groups-tab-mine"]').classes()).not.toContain('active')
  })

  it('renders resolved translation labels, not raw i18n keys', () => {
    // Regression guard: a tab once used a non-existent key and rendered the
    // raw path, which CSS uppercased in the UI. Every tab label must resolve
    // to real text - the map tab carries 887's "Find a group" title
    // (groups_title2), not a generic "Map".
    const wrapper = mountNav('mine')

    expect(wrapper.find('[data-testid="groups-tab-mine"]').text()).toBe('Your Groups')
    expect(wrapper.find('[data-testid="groups-tab-map"]').text()).toBe('Find a group')
  })

  // gap #2: legacy's .ourtabs border/shadow box wraps the tab-content as
  // well as the nav - the slot lets pages put their loading/error/table
  // content inside that same box rather than bare on the page background.
  it('renders slot content inside the tabs panel', () => {
    const wrapper = mountNav('mine', { default: '<p data-testid="slot-content">Body</p>' })

    expect(wrapper.find('[data-testid="groups-tabs-panel"] [data-testid="slot-content"]').exists()).toBe(true)
  })
})
