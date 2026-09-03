import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it } from 'vitest'
import DashboardYourGroups from '../../../app/components/dashboard/DashboardYourGroups.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// Nests DashboardNearbyGroups, whose join button now calls the groups store.
beforeEach(() => setActivePinia(createPinia()))

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

const BBadgeStub = {
  template: '<span v-bind="$attrs"><slot /></span>',
}

const BButtonStub = {
  props: ['to'],
  template: '<a :href="to" v-bind="$attrs"><slot /></a>',
}

function mountComponent(props = {}) {
  // dashboard.groups_heading/add_event and groups.archived_group_title are
  // new lang/*.php keys (this cluster's parity fixes) not yet in the
  // generated client i18n JSON - the main agent regenerates that centrally
  // (see DashboardWhatsHappening.spec.js for the same pattern), so they're
  // overlaid here inline rather than editing client/i18n/locales/*.json
  // directly.
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        dashboard: {
          ...en.dashboard,
          groups_heading: 'Groups',
          add_event: 'Add',
        },
        groups: {
          ...en.groups,
          archived_group_title: 'This group was archived on {date}.',
        },
      },
    },
  })

  return mount(DashboardYourGroups, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, BButton: BButtonStub },
    },
  })
}

describe('components/dashboard/DashboardYourGroups', () => {
  it('renders the DashboardNearbyGroups empty-state content nested in a single panel when there are no groups', () => {
    const wrapper = mountComponent({ groups: [] })

    expect(wrapper.find('[data-testid="your-groups-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="your-groups-list"]').exists()).toBe(false)

    // Legacy's "Your Groups" is a SINGLE panel: the no-groups fallback
    // (photo/no-town message/nearby groups/start-your-own-group copy) is
    // DashboardNoGroups nested inside it, not a separate always-visible
    // "Groups near you" panel elsewhere on the page.
    const nearby = wrapper.find('[data-testid="your-groups-empty"] [data-testid="dashboard-nearby-groups"]')
    expect(nearby.exists()).toBe(true)

    // A working, resolved link to browse all groups is present (via
    // dashboard.no_groups_intro's embedded /group/all link) - not a raw i18n
    // key. A prior version called t('groups.all_groups') - a key that never
    // existed - so the literal "groups.all_groups" showed on the dashboard.
    const allGroupsLink = nearby.findAll('a').find((a) => a.attributes('href') === '/group/all')
    expect(allGroupsLink).toBeTruthy()
    expect(allGroupsLink.text()).not.toMatch(/^[a-z][a-z0-9_]*\.[a-z0-9_.]+$/i)
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

  it('does not show a role badge for groups (parity gap #6 - legacy DashboardGroup.vue never shows one here)', () => {
    const wrapper = mountComponent({
      groups: [{ id: 1, name: 'Fixers Group', role: 3, archived: false, image_url: null }],
    })

    expect(wrapper.find('[data-testid="your-group-role-1"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Host')
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

  it('titles the archived badge with the archive date when the API provides one', () => {
    // dashboard's your_groups[] doesn't expose archived_at yet (see
    // docs/nuxt-migration/api-gaps.md) - this future-proofs the binding so
    // it lights up the moment the backend adds the field, without showing a
    // bogus "undefined" tooltip meanwhile.
    const withDate = mountComponent({
      groups: [{ id: 1, name: 'Old Group', role: 4, archived: true, archived_at: '2025-01-01', image_url: null }],
    })
    expect(withDate.find('[data-testid="your-group-archived-1"]').attributes('title')).toBe(
      'This group was archived on 2025-01-01.'
    )

    const withoutDate = mountComponent({
      groups: [{ id: 2, name: 'Old Group', role: 4, archived: true, image_url: null }],
    })
    expect(withoutDate.find('[data-testid="your-group-archived-2"]').attributes('title')).toBeFalsy()
  })

  it('shows the "Groups" sub-heading above the group list', () => {
    const wrapper = mountComponent({
      groups: [{ id: 1, name: 'A Group', role: 4, archived: false, image_url: null }],
    })

    expect(wrapper.find('h3').text()).toBe('Groups')
  })

  it('embeds the upcoming-events section as the right-hand column of the SAME panel, not a sibling panel (parity gap #2)', () => {
    const wrapper = mountComponent({
      groups: [{ id: 1, name: 'A Group', role: 4, archived: false, image_url: null }],
      events: [{ id: 5, title: 'An Event', start: '2026-08-01T10:00:00Z', group: { id: 1, name: 'A Group' } }],
    })

    // One CollapsibleSection (one title bar) wraps both the groups list and
    // the events list.
    expect(wrapper.findAll('[data-testid="dashboard-your-groups"]').length).toBe(1)
    expect(wrapper.find('[data-testid="dashboard-upcoming-events"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="upcoming-event-link-5"]').exists()).toBe(true)
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
