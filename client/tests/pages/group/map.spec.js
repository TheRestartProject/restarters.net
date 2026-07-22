import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupMapPage from '../../../app/pages/group/map.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

// GroupMap.vue pulls in real Leaflet/markercluster/geocoder - out of scope
// for a page test (covered by GroupMap.spec.js). Stubbed here so the page's
// own wiring (bounds/hover plumbing, search, summary hydration) is what's
// under test, matching resources/js/components/GroupMapAndList.test.js's own
// groupMapStub.
const GroupMapStub = {
  name: 'GroupMap',
  props: ['groups', 'yourGroupIds', 'hoveredId'],
  emits: ['update:groupIdsInBounds', 'update:hoveredId'],
  template: '<div class="stub-groupmap" data-testid="stub-group-map" />',
}

// groups.group_count_map/create_groups_mobile2 are new keys (lang/en/groups.php)
// not yet re-exported to en.json - injected here so these specs exercise the
// real copy rather than the untranslated key fallback.
function mountPage() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        groups: {
          ...en.groups,
          group_count_map: 'There is <b>{count} group</b> in this area.  Search and zoom to find more.|There are <b>{count} groups</b> in this area.  Search and zoom to find more.',
          group_count_none: 'If you can\'t see any here yet, why not <a href="/group/nearby">find a group</a> near you?',
          create_groups_mobile2: 'Add new',
        },
      },
    },
  })

  return mount(GroupMapPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub, BBadge: BBadgeStub, GroupMap: GroupMapStub },
    },
  })
}

describe('pages/group/map', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchNames = vi.fn().mockResolvedValue([])
    groupsStore.fetchMine = vi.fn().mockResolvedValue([])
    groupsStore.fetchSummaries = vi.fn().mockResolvedValue([])
  })

  it('fetches the names index and best-effort seeds membership on mount', () => {
    mountPage()

    expect(groupsStore.fetchNames).toHaveBeenCalledTimes(1)
    expect(groupsStore.fetchMine).toHaveBeenCalledTimes(1)
  })

  it('shows a loading skeleton while the names index loads', () => {
    groupsStore.namesLoading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-map-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="stub-group-map"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls fetchNames again', async () => {
    groupsStore.namesError = { status: 500 }

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-map-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-map-retry"]').trigger('click')
    expect(groupsStore.fetchNames).toHaveBeenCalledTimes(2)
  })

  it('passes only non-archived groups to the map', () => {
    groupsStore.names = [
      { id: 1, name: 'Active', lat: 51, lng: 0, archived_at: null },
      { id: 2, name: 'Archived', lat: 52, lng: 1, archived_at: '2024-01-01T00:00:00Z' },
    ]

    const wrapper = mountPage()

    expect(wrapper.findComponent(GroupMapStub).props('groups').map((g) => g.id)).toEqual([1])
  })

  describe('the list panel', () => {
    // Regression ported from GroupMapAndList.test.js: falls back to every
    // group before the map has reported its viewport (null, not []).
    it('falls back to the full names list before the map reports its bounds', () => {
      groupsStore.names = [
        { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
        { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
      ]

      const wrapper = mountPage()

      expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(2)
    })

    // Regression: an empty bounds report is a real answer, not "no report
    // yet" - must not fall back to listing every group.
    it('shows exactly the groups the map reports in view, including none', async () => {
      groupsStore.names = [
        { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
        { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
      ]

      const wrapper = mountPage()
      await wrapper.findComponent(GroupMapStub).vm.$emit('update:groupIdsInBounds', [])

      // develop's b-table renders no tbody content at all for zero matches -
      // not an invented "no results" row (GroupsTable.vue parity finding #4).
      expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(0)
      expect(wrapper.findAll('tbody tr')).toHaveLength(0)
    })

    it('offers a link to find a group nearby when none are in view', async () => {
      groupsStore.names = [{ id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null }]

      const wrapper = mountPage()
      await wrapper.findComponent(GroupMapStub).vm.$emit('update:groupIdsInBounds', [])

      // PR 887's group_count_none carries the link inline, rendered via v-html.
      const none = wrapper.find('[data-testid="group-map-count-none"]')
      expect(none.exists()).toBe(true)
      expect(none.text()).toContain('find a group')
      expect(none.html()).toContain('href="/group/nearby"')
      // The bare count is replaced, not shown alongside.
      expect(wrapper.find('[data-testid="group-map-count"]').text()).not.toContain('in this area')
    })

    it('shows only the reported ids when the map narrows the view', async () => {
      groupsStore.names = [
        { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
        { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
      ]

      const wrapper = mountPage()
      await wrapper.findComponent(GroupMapStub).vm.$emit('update:groupIdsInBounds', [2])

      const rows = wrapper.findAll('tbody tr[data-testid^="group-row-"]')
      expect(rows).toHaveLength(1)
      expect(rows[0].attributes('data-testid')).toBe('group-row-2')
    })

    it('further filters the visible rows by the search box', async () => {
      groupsStore.names = [
        { id: 1, name: 'Alpha Fixers', lat: 51, lng: 0, archived_at: null },
        { id: 2, name: 'Beta Fixers', lat: 52, lng: 1, archived_at: null },
      ]

      const wrapper = mountPage()
      await wrapper.find('[data-testid="group-map-search"]').setValue('Alpha')

      const rows = wrapper.findAll('tbody tr[data-testid^="group-row-"]')
      expect(rows).toHaveLength(1)
      expect(rows[0].attributes('data-testid')).toBe('group-row-1')
    })

    it('layers hydrated summary fields (location/hosts/restarters) over the names-index row', () => {
      groupsStore.names = [{ id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null }]
      groupsStore.summaryByIds = {
        1: { id: 1, name: 'Alpha', hosts: 3, restarters: 8, location: { location: 'London', country: 'UK' } },
      }

      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="group-row-hosts-1"]').text()).toBe('3')
      expect(wrapper.find('[data-testid="group-row-restarters-1"]').text()).toBe('8')
    })
  })

  it('hydrates summaries for the effective (visible) ids', () => {
    groupsStore.names = [
      { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
      { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
    ]

    mountPage()

    expect(groupsStore.fetchSummaries).toHaveBeenCalledWith([1, 2])
  })

  it('links hover between the map and the list', async () => {
    groupsStore.names = [{ id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null }]

    const wrapper = mountPage()

    await wrapper.findComponent(GroupMapStub).vm.$emit('update:hoveredId', 1)
    expect(wrapper.find('[data-testid="group-row-1"]').classes()).toContain('group-row-hovered')

    await wrapper.find('[data-testid="group-row-1"]').trigger('mouseenter')
    expect(wrapper.findComponent(GroupMapStub).props('hoveredId')).toBe(1)
  })

  // gap #15: the "zoom out to see more" count copy is map-specific
  // (groups.group_count_map), distinct from /group/all's plain
  // groups.group_count.
  it('shows an "in this area, search and zoom" group count above the list', () => {
    groupsStore.names = [
      { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
      { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
    ]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-map-count"]').text()).toContain('There are')
    expect(wrapper.find('[data-testid="group-map-count"]').text()).toContain('in this area')
  })

  it('shows the mobile-length create-group label alongside the full label', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add new')
    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add a new group')
  })
})
