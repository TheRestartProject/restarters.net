import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupAllPage from '../../../app/pages/group/all.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useModerationStore } from '../../../app/stores/moderation.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

// groups.create_groups_mobile2 is a new key (lang/en/groups.php) not yet
// re-exported to en.json - injected here so the mobile-label test exercises
// the real copy rather than the untranslated key fallback.
function mountPage() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn, groups: { ...en.groups, create_groups_mobile2: 'Add new' } } },
  })

  return mount(GroupAllPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub, BBadge: BBadgeStub },
    },
  })
}

function setLoggedInUser(user) {
  useAuthStore().user = user
}

function namedGroups(count) {
  return Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    name: `Group ${String(i + 1).padStart(2, '0')}`,
    archived_at: null,
  }))
}

describe('pages/group/all', () => {
  let groupsStore

  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchNames = vi.fn().mockResolvedValue([])
    groupsStore.fetchDetails = vi.fn().mockResolvedValue(null)
    useModerationStore().fetchGroups = vi.fn().mockResolvedValue([])
  })

  it('calls groupsStore.fetchNames() with includeArchived on mount', () => {
    mountPage()
    expect(groupsStore.fetchNames).toHaveBeenCalledWith({ includeArchived: 'true' })
  })

  it('shows a loading skeleton while loading', () => {
    groupsStore.namesLoading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-all-loading"]').exists()).toBe(true)
  })

  it('shows an error state with a retry button that calls fetchNames again', async () => {
    groupsStore.namesError = { status: 500 }

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-all-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-all-retry"]').trigger('click')
    expect(groupsStore.fetchNames).toHaveBeenCalledTimes(2)
  })

  // The name/location/country/tags search is GroupsTable's own built-in
  // filter bar now (show-filters, gap #6), not a page-level search box.
  it('filters rows via the GroupsTable filter bar', async () => {
    groupsStore.names = [
      { id: 1, name: 'Alpha Fixers', archived_at: null },
      { id: 2, name: 'Beta Fixers', archived_at: null },
    ]

    const wrapper = mountPage()
    expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(2)
    expect(wrapper.find('[data-testid="groups-table-filters"]').exists()).toBe(true)

    await wrapper.find('[data-testid="groups-table-filter-name"]').setValue('Alpha')

    const rows = wrapper.findAll('tbody tr[data-testid^="group-row-"]')
    expect(rows).toHaveLength(1)
    expect(rows[0].attributes('data-testid')).toBe('group-row-1')
  })

  // Legacy has no archived-hiding toggle anywhere - archived groups are
  // always listed, just badge-marked (gap #14/#3): no such checkbox exists
  // on this page, and archived groups are never excluded from the list.
  it('shows archived groups by default, with no toggle to hide them', () => {
    groupsStore.names = [
      { id: 1, name: 'Active Group', archived_at: null },
      { id: 2, name: 'Old Group', archived_at: '2024-01-01T00:00:00Z' },
    ]

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-all-include-archived"]').exists()).toBe(false)
    expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(2)
  })

  // GET /api/v2/groups/names carries network_ids/tag_ids per group (PR
  // #887) - GroupsTable's network/tag filter dropdowns need these
  // immediately, without waiting on per-row fetchDetails hydration.
  it('passes each row its network_ids/tag_ids from the names index', () => {
    groupsStore.names = [{ id: 1, name: 'Alpha Fixers', archived_at: null, network_ids: [3], tag_ids: [7] }]

    const wrapper = mountPage()
    const table = wrapper.findComponent({ name: 'GroupsTable' })

    expect(table.props('groups')[0].networkIds).toEqual([3])
    expect(table.props('groups')[0].tagIds).toEqual([7])
  })

  // gap #1/#2: legacy's shared tab bar only ever has three tabs (no "Map"),
  // and its .ourtabs border/shadow box wraps the tab-content (here: the
  // count + filters + table) as well as the nav itself.
  it('shows the Map tab, and renders the table inside the shared tabs panel box', () => {
    groupsStore.names = [{ id: 1, name: 'Alpha Fixers', archived_at: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="groups-tab-map"]').exists()).toBe(true)
    expect(
      wrapper.find('[data-testid="groups-tabs-panel"] [data-testid="groups-table"]').exists()
    ).toBe(true)
  })

  // No pagination UI at all (gap #7) - the full filtered list renders.
  it('renders the full filtered list with no pagination controls', () => {
    groupsStore.names = namedGroups(25)

    const wrapper = mountPage()

    expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(25)
    expect(wrapper.find('[data-testid="group-all-next-page"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-all-prev-page"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-all-page-indicator"]').exists()).toBe(false)
  })

  it('hydrates details for every row in the filtered list', () => {
    groupsStore.names = [
      { id: 1, name: 'Alpha Fixers', archived_at: null },
      { id: 2, name: 'Beta Fixers', archived_at: null },
    ]

    mountPage()

    expect(groupsStore.fetchDetails).toHaveBeenCalledWith(1)
    expect(groupsStore.fetchDetails).toHaveBeenCalledWith(2)
  })

  it('shows the mobile-length create-group label alongside the full label', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add new')
    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add a new group')
  })

  describe('moderation queue and tag badges (Administrator/NetworkCoordinator only)', () => {
    it('are hidden for a plain Host', () => {
      setLoggedInUser({ id: 1, role_name: 'Host' })
      groupsStore.names = [{ id: 1, name: 'Alpha Fixers', archived_at: null }]
      groupsStore.details = { 1: { location: null, hosts: null, restarters: null, next_event: null, tags: [{ id: 5, name: 'Scotland' }] } }

      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="network-groups-moderation-table"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="group-row-tags-1"]').exists()).toBe(false)
    })

    it('show for an Administrator', () => {
      setLoggedInUser({ id: 1, role_name: 'Administrator' })
      useModerationStore().groups.data = [{ id: 9, name: 'Pending Fixers' }]
      groupsStore.names = [{ id: 1, name: 'Alpha Fixers', archived_at: null }]
      groupsStore.details = { 1: { location: null, hosts: null, restarters: null, next_event: null, tags: [{ id: 5, name: 'Scotland' }] } }

      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="network-groups-moderation-table"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-row-tags-1"]').text()).toContain('Scotland')
    })

    // Legacy's group.index.blade.php renders GroupsRequiringModeration
    // BEFORE GroupsPage, whose own template opens with the "Groups" h1 -
    // order, not just presence (a prior version had it after the h1,
    // caught only by a mobile screenshot pass where the reordering was
    // obvious; desktop's wider layout made it easy to miss).
    it('renders before the page heading, not after it', () => {
      setLoggedInUser({ id: 1, role_name: 'Administrator' })
      useModerationStore().groups.data = [{ id: 9, name: 'Pending Fixers' }]

      const wrapper = mountPage()
      const html = wrapper.html()

      expect(html.indexOf('network-groups-moderation-table')).toBeLessThan(html.indexOf('group-create-link'))
    })
  })
})
