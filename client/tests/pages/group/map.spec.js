import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupMapPage from '../../../app/pages/group/map.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useProfileStore } from '../../../app/stores/profile.js'
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
const GroupInfoModalStub = {
  name: 'GroupInfoModal',
  props: ['group'],
  emits: ['close'],
  template: '<div data-testid="group-info-modal-stub" :data-group-id="group ? group.id : \'\'" />',
}

const GroupMapStub = {
  name: 'GroupMap',
  props: ['groups', 'yourGroupIds', 'hoveredId', 'network', 'initialBounds', 'yourLat', 'yourLng', 'yourArea', 'frameRequest'],
  emits: ['update:groupIdsInBounds', 'update:hoveredId', 'select', 'searched', 'update:centre'],
  template: '<div class="stub-groupmap" data-testid="stub-group-map" />',
}

// groups.group_count_map/create_groups_mobile2 are new keys (lang/en/groups.php)
// not yet re-exported to en.json - injected here so these specs exercise the
// real copy rather than the untranslated key fallback.
async function mountPage() {
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

  const wrapper = mount(GroupMapPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub, BBadge: BBadgeStub, GroupMap: GroupMapStub, GroupInfoModal: GroupInfoModalStub },
    },
  })

  // The page waits for the best-effort profile fetch before drawing the map.
  await flushPromises()

  return wrapper
}

describe('pages/group/map', () => {
  let groupsStore
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchNames = vi.fn().mockResolvedValue([])
    groupsStore.fetchMine = vi.fn().mockResolvedValue([])
    groupsStore.fetchSummaries = vi.fn().mockResolvedValue([])
    profileStore = useProfileStore()
    profileStore.fetchProfileInfo = vi.fn().mockResolvedValue({})
  })

  it('fetches the names index and best-effort seeds membership on mount', async () => {
    await mountPage()

    expect(groupsStore.fetchNames).toHaveBeenCalledTimes(1)
    expect(groupsStore.fetchMine).toHaveBeenCalledTimes(1)
  })

  // /group/all?network=N (linked from the networks page) now forwards here
  // with its query intact - the map must apply it as its network filter.
  it('applies a ?network= query as the map network filter', async () => {
    vi.stubGlobal('useRoute', () => ({ query: { network: '5' }, params: {}, fullPath: '/group/map?network=5' }))

    const wrapper = await mountPage()
    expect(wrapper.findComponent(GroupMapStub).props('network')).toBe(5)

    vi.stubGlobal('useRoute', () => ({ query: {}, params: {}, fullPath: '/' }))
  })

  // User feedback: the list under the map gets a distance column, anchored
  // to the user's own location - or, once they search, the searched place -
  // and opens nearest-first.
  describe('distance column anchoring', () => {
    const LONDON = { id: 1, name: 'London Fixers', lat: 51.5, lng: -0.1 }
    const BRUM = { id: 2, name: 'Brum Fixers', lat: 52.48, lng: -1.9 }

    it('fetches the profile for the anchor point on mount', () => {
      mountPage()
      expect(profileStore.fetchProfileInfo).toHaveBeenCalledTimes(1)
    })

    it('hides the distance column with no profile location and no search', async () => {
      groupsStore.names = [LONDON, BRUM]
      const wrapper = await mountPage()

      expect(wrapper.find('[data-testid="groups-table-sort-distance"]').exists()).toBe(false)
    })

    it('anchors distances to the profile location, nearest first', async () => {
      profileStore.info.data = { location: 'London', lat: 51.5074, lng: -0.1278 }
      groupsStore.names = [BRUM, LONDON]

      const wrapper = await mountPage()
      await wrapper.vm.$nextTick()

      // London ~3km away sorts above Birmingham ~160km away, despite the
      // store order, and the cells carry real kilometres.
      const links = wrapper.findAll('[data-testid^="group-row-link-"]').map((a) => a.text())
      expect(links).toEqual(['London Fixers', 'Brum Fixers'])
      expect(wrapper.find('[data-testid="group-row-distance-1"]').text()).toBe('2.1 km')
    })

    it('re-anchors to the searched place after a place search', async () => {
      profileStore.info.data = { location: 'London', lat: 51.5074, lng: -0.1278 }
      groupsStore.names = [BRUM, LONDON]

      const wrapper = await mountPage()
      wrapper.findComponent(GroupMapStub).vm.$emit('searched', { lat: 52.48, lng: -1.9 })
      await wrapper.vm.$nextTick()

      const links = wrapper.findAll('[data-testid^="group-row-link-"]').map((a) => a.text())
      expect(links).toEqual(['Brum Fixers', 'London Fixers'])
      expect(wrapper.find('[data-testid="group-row-distance-2"]').text()).toBe('0 km')
    })
  })

  it('shows a loading skeleton while the names index loads', async () => {
    groupsStore.namesLoading = true

    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="group-map-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="stub-group-map"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls fetchNames again', async () => {
    groupsStore.namesError = { status: 500 }

    const wrapper = await mountPage()
    expect(wrapper.find('[data-testid="group-map-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-map-retry"]').trigger('click')
    expect(groupsStore.fetchNames).toHaveBeenCalledTimes(2)
  })

  // Develop's list includes archived groups and badges them (the old
  // server-rendered page listed them too), so the names index is fetched with
  // them and the map draws them.
  it('asks for archived groups too and passes them to the map', async () => {
    groupsStore.names = [
      { id: 1, name: 'Active', lat: 51, lng: 0, archived_at: null },
      { id: 2, name: 'Archived', lat: 52, lng: 1, archived_at: '2024-01-01T00:00:00Z' },
    ]

    const wrapper = await mountPage()

    expect(groupsStore.fetchNames).toHaveBeenCalledWith({ includeArchived: 'true' })
    expect(wrapper.findComponent(GroupMapStub).props('groups').map((g) => g.id)).toEqual([1, 2])
    expect(wrapper.find('[data-testid="group-row-archived-2"]').exists()).toBe(true)
  })

  describe('the list panel', () => {
    // Regression ported from GroupMapAndList.test.js: falls back to every
    // group before the map has reported its viewport (null, not []).
    it('falls back to the full names list before the map reports its bounds', async () => {
      groupsStore.names = [
        { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
        { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
      ]

      const wrapper = await mountPage()

      expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(2)
    })

    // Regression: an empty bounds report is a real answer, not "no report
    // yet" - must not fall back to listing every group.
    it('shows exactly the groups the map reports in view, including none', async () => {
      groupsStore.names = [
        { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
        { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
      ]

      const wrapper = await mountPage()
      await wrapper.findComponent(GroupMapStub).vm.$emit('update:groupIdsInBounds', [])

      // develop's b-table renders no tbody content at all for zero matches -
      // not an invented "no results" row (GroupsTable.vue parity finding #4).
      expect(wrapper.findAll('tbody tr[data-testid^="group-row-"]')).toHaveLength(0)
      expect(wrapper.findAll('tbody tr')).toHaveLength(0)
    })

    it('offers a link to find a group nearby when none are in view', async () => {
      groupsStore.names = [{ id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null }]

      const wrapper = await mountPage()
      await wrapper.findComponent(GroupMapStub).vm.$emit('update:groupIdsInBounds', [])

      // PR 887's group_count_none carries the link inline, rendered via v-html.
      const none = wrapper.find('[data-testid="group-map-count-none"]')
      expect(none.exists()).toBe(true)
      expect(none.text()).toContain('find a group')
      expect(none.html()).toContain('href="/group/nearby"')
      // The bare count is replaced, not shown alongside.
      expect(wrapper.find('[data-testid="group-map-count"]').text()).not.toContain('in this area')
    })

    // PR 887: clicking a marker opens a group-info modal (next event + Go to
  // group). GroupMap emits `select`; the page shows GroupInfoModal for that
  // group's summary and clears it on close.
  describe('marker selection modal', () => {
    it('opens the info modal for the group a marker selects', async () => {
      groupsStore.names = [{ id: 7, name: 'Selected Group', lat: 51, lng: 0, archived_at: null }]

      const wrapper = await mountPage()
      const modal = () => wrapper.findComponent(GroupInfoModalStub)

      // Nothing selected initially.
      expect(modal().props('group')).toBeNull()

      await wrapper.findComponent(GroupMapStub).vm.$emit('select', 7)

      expect(modal().props('group')).not.toBeNull()
      expect(modal().props('group').id).toBe(7)
      expect(modal().props('group').name).toBe('Selected Group')
    })

    it('closes the info modal, clearing the selection', async () => {
      groupsStore.names = [{ id: 7, name: 'Selected Group', lat: 51, lng: 0, archived_at: null }]

      const wrapper = await mountPage()
      await wrapper.findComponent(GroupMapStub).vm.$emit('select', 7)
      expect(wrapper.findComponent(GroupInfoModalStub).props('group')).not.toBeNull()

      await wrapper.findComponent(GroupInfoModalStub).vm.$emit('close')
      expect(wrapper.findComponent(GroupInfoModalStub).props('group')).toBeNull()
    })
  })

  it('shows only the reported ids when the map narrows the view', async () => {
      groupsStore.names = [
        { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
        { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
      ]

      const wrapper = await mountPage()
      await wrapper.findComponent(GroupMapStub).vm.$emit('update:groupIdsInBounds', [2])

      const rows = wrapper.findAll('tbody tr[data-testid^="group-row-"]')
      expect(rows).toHaveLength(1)
      expect(rows[0].attributes('data-testid')).toBe('group-row-2')
    })

    it('further filters the visible rows by the filter bar', async () => {
      groupsStore.names = [
        { id: 1, name: 'Alpha Fixers', lat: 51, lng: 0, archived_at: null },
        { id: 2, name: 'Beta Fixers', lat: 52, lng: 1, archived_at: null },
      ]

      const wrapper = await mountPage()
      await wrapper.find('[data-testid="groups-table-filter-name"]').setValue('Alpha')

      const rows = wrapper.findAll('tbody tr[data-testid^="group-row-"]')
      expect(rows).toHaveLength(1)
      expect(rows[0].attributes('data-testid')).toBe('group-row-1')
    })

    // Develop (8e754302f2): filtering moves the map, not just the list. The
    // pins follow every keystroke; the viewport waits until typing stops.
    it('narrows the pins to the filter, wherever they are, and reframes once typing stops', async () => {
      vi.useFakeTimers()
      groupsStore.names = [
        { id: 1, name: 'Alpha Fixers', lat: 51, lng: 0, archived_at: null, tag_ids: [] },
        { id: 2, name: 'Beta Fixers', lat: 52, lng: 1, archived_at: null, tag_ids: [] },
      ]

      const wrapper = await mountPage()
      const map = () => wrapper.findComponent(GroupMapStub)
      // The map reports only group 1 in view; the filter still reaches group 2.
      await map().vm.$emit('update:groupIdsInBounds', [1])
      const before = map().props('frameRequest')

      await wrapper.find('[data-testid="groups-table-filter-name"]').setValue('Beta')

      expect(map().props('groups').map((g) => g.id)).toEqual([2])
      expect(map().props('frameRequest')).toBe(before)

      await vi.advanceTimersByTimeAsync(499)
      expect(map().props('frameRequest')).toBe(before)

      await vi.advanceTimersByTimeAsync(2)
      expect(map().props('frameRequest')).toBe(before + 1)
      vi.useRealTimers()
    })

    it('shows image, name, location, next event and no host or restarter counts', async () => {
      groupsStore.names = [{ id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null }]
      groupsStore.summaryByIds = {
        1: { id: 1, name: 'Alpha', hosts: 3, restarters: 8, location: { location: 'London', country: 'UK' } },
      }

      const wrapper = await mountPage()

      expect(wrapper.find('[data-testid="group-row-hosts-1"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="group-row-restarters-1"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="group-row-next-event-1"]').exists()).toBe(true)
    })

    it('layers hydrated summary fields (location, next event) over the names-index row', async () => {
      groupsStore.names = [{ id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null }]
      groupsStore.summaryByIds = {
        1: { id: 1, name: 'Alpha', next_event: { start: '2030-01-01T10:00:00Z' }, location: { location: 'London', country: 'UK' } },
      }

      const wrapper = await mountPage()

      expect(wrapper.find('tbody').text()).toContain('London')
      expect(wrapper.find('[data-testid="group-row-next-event-1"]').text()).not.toBe('')
    })
  })

  it('hydrates summaries for the effective (visible) ids', async () => {
    groupsStore.names = [
      { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
      { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
    ]

    await mountPage()

    expect(groupsStore.fetchSummaries).toHaveBeenCalledWith([1, 2])
  })

  it('links hover between the map and the list', async () => {
    groupsStore.names = [{ id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null }]

    const wrapper = await mountPage()

    await wrapper.findComponent(GroupMapStub).vm.$emit('update:hoveredId', 1)
    expect(wrapper.find('[data-testid="group-row-1"]').classes()).toContain('group-row-hovered')

    await wrapper.find('[data-testid="group-row-1"]').trigger('mouseenter')
    expect(wrapper.findComponent(GroupMapStub).props('hoveredId')).toBe(1)
  })

  // gap #15: the "zoom out to see more" count copy is map-specific
  // (groups.group_count_map), distinct from /group/all's plain
  // groups.group_count.
  it('shows an "in this area, search and zoom" group count above the list', async () => {
    groupsStore.names = [
      { id: 1, name: 'Alpha', lat: 51, lng: 0, archived_at: null },
      { id: 2, name: 'Beta', lat: 52, lng: 1, archived_at: null },
    ]

    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="group-map-count"]').text()).toContain('There are')
    expect(wrapper.find('[data-testid="group-map-count"]').text()).toContain('in this area')
  })

  it('shows the mobile-length create-group label alongside the full label', async () => {
    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add new')
    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add a new group')
  })

  // Develop framed the map for the user server side (GroupController::mine):
  // on their own point if they have one, else a box round the groups in their
  // country, else every group.
  describe('where the map opens', () => {
    const COUNTRIES = [{ code: 'GB', name: 'United Kingdom' }, { code: 'FR', name: 'France' }]
    const names = [
      { id: 1, name: 'London', lat: 51.5, lng: -0.1, country: 'United Kingdom', archived_at: null },
      { id: 2, name: 'Leeds', lat: 53.8, lng: -1.5, country: 'United Kingdom', archived_at: null },
      { id: 3, name: 'Paris', lat: 48.9, lng: 2.3, country: 'France', archived_at: null },
      { id: 4, name: 'Old Bristol', lat: 40, lng: 9, country: 'United Kingdom', archived_at: '2024-01-01T00:00:00Z' },
    ]

    it("hands the map the user's own point and area", async () => {
      profileStore.info.data = { location: 'Brixton', lat: 51.46, lng: -0.11, country_code: 'GB', countries: COUNTRIES }
      groupsStore.names = names

      const wrapper = await mountPage()
      const map = wrapper.findComponent(GroupMapStub)

      expect(map.props('yourLat')).toBe(51.46)
      expect(map.props('yourLng')).toBe(-0.11)
      expect(map.props('yourArea')).toBe('Brixton')
      // A point beats a country box.
      expect(map.props('initialBounds')).toBeNull()
    })

    it('frames the groups in the country of a user who has only set a country', async () => {
      profileStore.info.data = { location: null, lat: null, lng: null, country_code: 'GB', countries: COUNTRIES }
      groupsStore.names = names

      const wrapper = await mountPage()

      // Live groups only: the archived one far away doesn't stretch the box.
      expect(wrapper.findComponent(GroupMapStub).props('initialBounds')).toEqual([[51.5, -1.5], [53.8, -0.1]])
    })

    it('frames every group when the user has no location and the country has no groups', async () => {
      profileStore.info.data = { location: null, lat: null, lng: null, country_code: 'NZ', countries: [{ code: 'NZ', name: 'New Zealand' }] }
      groupsStore.names = names

      const wrapper = await mountPage()
      const map = wrapper.findComponent(GroupMapStub)

      expect(map.props('initialBounds')).toBeNull()
      expect(map.props('yourLat')).toBeNull()
    })

    it('waits for the profile fetch before drawing the map, but draws it if the fetch fails', async () => {
      let rejectProfile
      profileStore.fetchProfileInfo = vi.fn().mockReturnValue(new Promise((_, reject) => { rejectProfile = reject }))
      groupsStore.names = names

      const wrapper = await mountPage()
      expect(wrapper.find('[data-testid="stub-group-map"]').exists()).toBe(false)

      rejectProfile(new Error('guest'))
      await flushPromises()
      expect(wrapper.find('[data-testid="stub-group-map"]').exists()).toBe(true)
    })

    it('orders the list nearest the middle of the map when there is no anchor, without showing a distance column', async () => {
      groupsStore.names = [
        { id: 1, name: 'Aaa Far', lat: 60, lng: 10, archived_at: null },
        { id: 2, name: 'Zzz Near', lat: 51.5, lng: -0.1, archived_at: null },
      ]

      const wrapper = await mountPage()
      await wrapper.findComponent(GroupMapStub).vm.$emit('update:centre', { lat: 51.5, lng: -0.1 })

      const links = wrapper.findAll('[data-testid^="group-row-link-"]').map((a) => a.text())
      expect(links).toEqual(['Zzz Near', 'Aaa Far'])
      expect(wrapper.find('[data-testid="groups-table-sort-distance"]').exists()).toBe(false)
    })
  })
})
