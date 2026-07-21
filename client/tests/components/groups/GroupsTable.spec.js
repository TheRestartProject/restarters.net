import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupsTable from '../../../app/components/groups/GroupsTable.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useNetworksStore } from '../../../app/stores/networks.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const GroupJoinButtonStub = {
  props: ['groupId', 'groupName', 'isMember'],
  template: '<button :data-testid="`stub-join-${groupId}`">{{ isMember ? "Leave" : "Join" }}</button>',
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupsTable, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, GroupJoinButton: GroupJoinButtonStub },
    },
  })
}

const rows = [
  { id: 1, name: 'Zeta Fixers', hosts: 2, restarters: 10, nextEvent: { start: '2026-08-01T10:00:00Z' } },
  { id: 2, name: 'Alpha Fixers', hosts: 5, restarters: 3, nextEvent: null },
  { id: 3, name: 'Middle Fixers', hosts: null, restarters: 7, nextEvent: { start: '2026-07-20T10:00:00Z' } },
]

describe('components/groups/GroupsTable', () => {
  // ARIA only honours aria-sort on the columnheader - the <th> - so that is
  // where it has to live. FixometerSortHeader had it on the sort <button>,
  // where assistive tech ignores it: the column looked instrumented and
  // announced nothing. develop gets both this and the "Click to sort
  // ascending" hint free from b-table.
  describe('sortable header accessibility', () => {
    it('marks the active column ascending and the rest none', async () => {
      const wrapper = mountComponent({ groups: rows })
      const th = () => wrapper.findAll('th')

      const nameTh = th().find((h) => h.find('[data-testid="groups-table-sort-name"]').exists())
      expect(nameTh.attributes('aria-sort')).toBe('ascending')

      const otherSorted = th().filter(
        (h) => h.attributes('aria-sort') && h.attributes('aria-sort') !== 'none'
      )
      expect(otherSorted).toHaveLength(1)
    })

    it('flips to descending when the active column is clicked again', async () => {
      const wrapper = mountComponent({ groups: rows })

      await wrapper.find('[data-testid="groups-table-sort-name"]').trigger('click')

      const nameTh = wrapper
        .findAll('th')
        .find((h) => h.find('[data-testid="groups-table-sort-name"]').exists())
      expect(nameTh.attributes('aria-sort')).toBe('descending')
    })

    it('announces what a click will do on the control itself', () => {
      const wrapper = mountComponent({ groups: rows })

      // Name is already ascending, so its control offers descending; an
      // unsorted column offers ascending.
      const nameBtn = wrapper.find('[data-testid="groups-table-sort-name"]')
      expect(nameBtn.text()).toContain(clientEn.client.common.sort_descending)
    })
  })

  // GroupsTableFilters (rendered whenever showFilters is set) reads network/
  // tag options straight from the shared stores rather than via props.
  beforeEach(() => {
    setActivePinia(createPinia())
    useNetworksStore().fetchList = vi.fn().mockResolvedValue([])
    useGroupsStore().fetchTags = vi.fn().mockResolvedValue([])
  })

  it('shows an empty-state row when there are no groups', () => {
    const wrapper = mountComponent({ groups: [] })

    expect(wrapper.find('[data-testid="groups-table-empty"]').exists()).toBe(true)
  })

  it('renders one row per group, linked to its view page', () => {
    const wrapper = mountComponent({ groups: rows })

    expect(wrapper.find('[data-testid="group-row-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-row-link-1"]').attributes('href')).toBe('/group/view/1')
  })

  it('defaults to sorting by name ascending', () => {
    const wrapper = mountComponent({ groups: rows })

    const names = wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-testid'))
    expect(names).toEqual(['group-row-2', 'group-row-3', 'group-row-1'])
  })

  it('clicking the name header again reverses the sort', async () => {
    const wrapper = mountComponent({ groups: rows })

    await wrapper.find('[data-testid="groups-table-sort-name"]').trigger('click')

    const names = wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-testid'))
    expect(names).toEqual(['group-row-1', 'group-row-3', 'group-row-2'])
  })

  it('sorts by hosts numerically, with null hosts last', async () => {
    const wrapper = mountComponent({ groups: rows })

    await wrapper.find('[data-testid="groups-table-sort-hosts"]').trigger('click')

    const order = wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-testid'))
    expect(order).toEqual(['group-row-1', 'group-row-2', 'group-row-3'])
  })

  it('sorts by next_event date, with no-event rows last', async () => {
    const wrapper = mountComponent({ groups: rows })

    await wrapper.find('[data-testid="groups-table-sort-next_event"]').trigger('click')

    const order = wrapper.findAll('tbody tr').map((tr) => tr.attributes('data-testid'))
    expect(order).toEqual(['group-row-3', 'group-row-1', 'group-row-2'])
  })

  it('hides optional columns per the optionalColumns prop', () => {
    const wrapper = mountComponent({
      groups: rows,
      optionalColumns: { location: false, hosts: false, restarters: false, next_event: false },
    })

    expect(wrapper.find('[data-testid="groups-table-sort-hosts"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-row-hosts-1"]').exists()).toBe(false)
  })

  it('hides the join column when showJoin is false', () => {
    const wrapper = mountComponent({ groups: rows, showJoin: false })

    expect(wrapper.find('[data-testid="stub-join-1"]').exists()).toBe(false)
  })

  it('shows a role badge only when showRole is true and the row has a role', () => {
    const wrapper = mountComponent({
      groups: [{ id: 9, name: 'Role Group', role: 3 }],
      showRole: true,
    })

    expect(wrapper.find('[data-testid="group-row-role-9"]').text()).toBe('Host')
  })

  it('shows an archived badge only for archived rows', () => {
    const wrapper = mountComponent({
      groups: [
        { id: 10, name: 'Active', archivedAt: null },
        { id: 11, name: 'Old', archivedAt: '2024-01-01T00:00:00Z' },
      ],
    })

    expect(wrapper.find('[data-testid="group-row-archived-10"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-row-archived-11"]').exists()).toBe(true)
  })

  // /group/map's row<->marker hover linking (GroupMap.vue) - Neil's PR
  // feedback on the legacy GroupMapAndList: hovering a pin highlights the
  // matching row, and vice versa.
  describe('hover linking', () => {
    it('emits update:hoveredId on mouseenter/mouseleave', async () => {
      const wrapper = mountComponent({ groups: rows })

      await wrapper.find('[data-testid="group-row-1"]').trigger('mouseenter')
      expect(wrapper.emitted('update:hoveredId').at(-1)).toEqual([1])

      await wrapper.find('[data-testid="group-row-1"]').trigger('mouseleave')
      expect(wrapper.emitted('update:hoveredId').at(-1)).toEqual([null])
    })

    it('highlights the row matching hoveredId', () => {
      const wrapper = mountComponent({ groups: rows, hoveredId: 2 })

      expect(wrapper.find('[data-testid="group-row-2"]').classes()).toContain('group-row-hovered')
      expect(wrapper.find('[data-testid="group-row-1"]').classes()).not.toContain('group-row-hovered')
    })

    it('defaults to no row highlighted', () => {
      const wrapper = mountComponent({ groups: rows })

      expect(wrapper.find('[data-testid="group-row-1"]').classes()).not.toContain('group-row-hovered')
    })
  })

  describe('filter bar', () => {
    // tagIds/networkIds mirror what pages/group/all.vue's `rows` computed
    // sources straight from the names index (network_ids/tag_ids, PR #887 -
    // see GroupsTable.vue's matchesFilters doc comment), available
    // immediately rather than waiting on per-row hydration.
    const filterableRows = [
      { id: 1, name: 'London Fixers', location: { location: 'London', country: 'UK' }, tagIds: [5], networkIds: [10] },
      { id: 2, name: 'Paris Repairers', location: { location: 'Paris', country: 'France' }, tagIds: [6], networkIds: [11] },
    ]

    it('does not render the filter bar unless showFilters is set', () => {
      const wrapper = mountComponent({ groups: filterableRows })
      expect(wrapper.find('[data-testid="groups-table-filters"]').exists()).toBe(false)
    })

    it('renders the filter bar when showFilters is set', () => {
      const wrapper = mountComponent({ groups: filterableRows, showFilters: true })
      expect(wrapper.find('[data-testid="groups-table-filters"]').exists()).toBe(true)
    })

    it('filters rows by name', async () => {
      const wrapper = mountComponent({ groups: filterableRows, showFilters: true })
      await wrapper.find('[data-testid="groups-table-filters-toggle"]').trigger('click')
      await wrapper.find('[data-testid="groups-table-filter-name"]').setValue('paris')

      expect(wrapper.find('[data-testid="group-row-2"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-row-1"]').exists()).toBe(false)
    })

    it('filters rows by location and country', async () => {
      const wrapper = mountComponent({ groups: filterableRows, showFilters: true })
      await wrapper.find('[data-testid="groups-table-filters-toggle"]').trigger('click')
      await wrapper.find('[data-testid="groups-table-filter-country"]').setValue('UK')

      expect(wrapper.find('[data-testid="group-row-1"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-row-2"]').exists()).toBe(false)
    })

    it('filters rows by network', async () => {
      useNetworksStore().list.data = [
        { id: 10, name: 'Network A' },
        { id: 11, name: 'Network B' },
      ]

      const wrapper = mountComponent({ groups: filterableRows, showFilters: true })
      await wrapper.find('[data-testid="groups-table-filters-toggle"]').trigger('click')
      await wrapper.find('[data-testid="groups-table-filter-network"]').setValue('11')

      expect(wrapper.find('[data-testid="group-row-2"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-row-1"]').exists()).toBe(false)
    })

    it('hides the tag filter unless showTags is set', () => {
      const wrapper = mountComponent({ groups: filterableRows, showFilters: true })
      expect(wrapper.find('[data-testid="groups-table-filter-tags"]').exists()).toBe(false)
    })

    // gap #6/#11: the tag filter is a dropdown gated on showTags (matching
    // legacy's Administrator/NetworkCoordinator-only tag search), not the
    // free-text search every other field uses.
    it('filters rows by tag, when showTags is set', async () => {
      useGroupsStore().tags.data = [
        { id: 5, name: 'electronics' },
        { id: 6, name: 'textiles' },
      ]

      const wrapper = mountComponent({ groups: filterableRows, showFilters: true, showTags: true })
      await wrapper.find('[data-testid="groups-table-filters-toggle"]').trigger('click')
      await wrapper.find('[data-testid="groups-table-filter-tags"]').setValue('6')

      expect(wrapper.find('[data-testid="group-row-2"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="group-row-1"]').exists()).toBe(false)
    })
  })

  // Legacy hides the whole sortable header row below md (thead-tr-class=
  // "d-none d-md-table-row"), and the location/hosts/restarters/next_event
  // columns individually below md (.hidecell) - without both, every opted-in
  // column rendered at every width, overflowing a 390px mobile viewport and
  // running the text together illegibly (mobile parity pass). CSS media
  // queries aren't evaluated in this test environment, so this pins the
  // classes that carry the responsive behaviour rather than actual computed
  // widths.
  describe('mobile responsiveness', () => {
    it('marks the header row hidden below md', () => {
      const wrapper = mountComponent({ groups: rows })

      expect(wrapper.find('thead tr').classes()).toContain('groups-table-head-row')
    })

    it('marks the optional data columns hidden below md, when shown at all', () => {
      const wrapper = mountComponent({ groups: rows })

      expect(wrapper.find('[data-testid="group-row-hosts-1"]').classes()).toContain('hidecell')
      expect(wrapper.find('[data-testid="group-row-restarters-1"]').classes()).toContain('hidecell')
      expect(wrapper.find('[data-testid="group-row-next-event-1"]').classes()).toContain('hidecell')
    })

    it('does not mark the always-visible photo/name/join columns hidden', () => {
      const wrapper = mountComponent({ groups: rows })

      expect(wrapper.find('[data-testid="group-row-photo-1"]').element.closest('td').classList.contains('hidecell')).toBe(false)
    })
  })

  // gap #3: legacy always has a leading photo column, falling back to the
  // default profile placeholder when a group has no image.
  describe('photo column', () => {
    it('renders a photo cell for every row, falling back to the placeholder', () => {
      const wrapper = mountComponent({ groups: rows })

      const photo = wrapper.find('[data-testid="group-row-photo-1"]')
      expect(photo.exists()).toBe(true)
      expect(photo.attributes('src')).toContain('/images/placeholder-avatar.webp')
    })

    it('uses the row image when present', () => {
      const wrapper = mountComponent({ groups: [{ id: 1, name: 'Zeta Fixers', image: 'https://example.com/photo.jpg' }] })

      expect(wrapper.find('[data-testid="group-row-photo-1"]').attributes('src')).toBe('https://example.com/photo.jpg')
    })
  })

  // gap #11: tag badges under the name, gated by showTags (the page
  // role-gates this to Administrator/NetworkCoordinator).
  describe('tag badges', () => {
    it('are hidden unless showTags is set', () => {
      const wrapper = mountComponent({ groups: [{ id: 1, name: 'Zeta Fixers', tags: [{ id: 5, name: 'Scotland' }] }] })

      expect(wrapper.find('[data-testid="group-row-tags-1"]').exists()).toBe(false)
    })

    it('render under the name when showTags is set', () => {
      const wrapper = mountComponent({
        groups: [{ id: 1, name: 'Zeta Fixers', tags: [{ id: 5, name: 'Scotland' }] }],
        showTags: true,
      })

      expect(wrapper.find('[data-testid="group-row-tags-1"]').text()).toBe('Scotland')
    })
  })
})
