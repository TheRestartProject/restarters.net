import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupsTable from './GroupsTable.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

function makeStore(groups) {
  const list = {}
  groups.forEach(g => { list[g.id] = g })
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: {
          list: () => Object.values(list),
          get: () => id => list[id],
        },
        actions: {
          fetch: () => Promise.resolve(),
          unfollow: () => Promise.resolve(),
        },
      },
    },
  })
}

const group = {
  id: 1,
  name: 'Test Group',
  hosts: 5,
  restarters: 12,
  location: { location: 'Townsville', country: 'United Kingdom', distance: null },
  image: null,
}

function mountTable(groups = [group], props = {}) {
  return mount(GroupsTable, {
    localVue,
    store: makeStore(groups),
    propsData: { groupids: groups.map(g => g.id), ...props },
    stubs: {
      GroupsTableFilters: true,
      ConfirmModal: true,
      GroupArchivedBadge: true,
      InfiniteLoading: true,
      'b-img-lazy': true,
    },
  })
}

describe('GroupsTable column headers', () => {
  // The hosts/restarters columns should show icons in the header, like the
  // location and next-event columns do. Regression: the header-slot names did
  // not match the field keys, so b-table fell back to the text labels
  // "Hosts" / "Restarters".
  test('renders the hosts column header as the user icon, not text', () => {
    const thead = mountTable().find('thead').html()
    expect(thead).toContain('user_ico')
  })

  test('renders the restarters column header as the volunteer icon, not text', () => {
    const thead = mountTable().find('thead').html()
    expect(thead).toContain('volunteer_ico-thick')
  })
})

describe('GroupsTable group images', () => {
  // The v2 APIs return a bare image path (e.g. "abc.png"); the old
  // server-rendered props used asset('uploads/mid_...'). Rendering the bare
  // path 404s and every logo falls back to the placeholder.
  test('prefixes a bare API image path with /uploads/mid_', () => {
    const wrapper = mountTable([{ ...group, image: 'abc123.png' }])
    const img = wrapper.find('b-img-lazy-stub.profile')
    expect(img.attributes('src')).toBe('/uploads/mid_abc123.png')
  })

  test('leaves an already-prefixed image path alone (moderation store shape)', () => {
    const wrapper = mountTable([{ ...group, image: '/uploads/mid_abc123.png' }])
    const img = wrapper.find('b-img-lazy-stub.profile')
    expect(img.attributes('src')).toBe('/uploads/mid_abc123.png')
  })

  test('shows the default profile image when there is no image', () => {
    const wrapper = mountTable([{ ...group, image: null }])
    const img = wrapper.find('b-img-lazy-stub.profile')
    expect(img.attributes('src')).toContain('placeholder')
  })
})

describe('GroupsTable sorting', () => {
  // Items are now flat group objects ({name}), but sortCompare still read
  // aRow['group_name'] (the old nested shape) which throws a TypeError as
  // soon as the user clicks the Name column header.
  test('sortCompare on group_name compares names without throwing', () => {
    const vm = mountTable().vm
    expect(vm.sortCompare({ name: 'Alpha' }, { name: 'Beta' }, 'group_name')).toBeLessThan(0)
    expect(vm.sortCompare({ name: 'Beta' }, { name: 'Alpha' }, 'group_name')).toBeGreaterThan(0)
  })

  test('sortCompare on next_event compares the start of the event object', () => {
    const vm = mountTable().vm
    const early = { next_event: { start: '2026-08-01T10:00:00+00:00' } }
    const late = { next_event: { start: '2026-09-01T10:00:00+00:00' } }
    expect(vm.sortCompare(early, late, 'next_event')).toBeLessThan(0)
    expect(vm.sortCompare(late, early, 'next_event')).toBeGreaterThan(0)
    // Groups without events sort last.
    expect(vm.sortCompare(early, { next_event: null }, 'next_event')).toBeLessThan(0)
  })

  // The requirement was an alphabetical list; sorting after slicing means the
  // first page is whatever N groups are first in the store, sorted amongst
  // themselves, rather than the alphabetically-first N groups.
  test('itemsToShow returns the alphabetically-first groups, not the first-loaded ones', () => {
    const groups = [
      { ...group, id: 1, name: 'Delta' },
      { ...group, id: 2, name: 'Charlie' },
      { ...group, id: 3, name: 'Bravo' },
      { ...group, id: 4, name: 'Alpha' },
    ]
    const wrapper = mountTable(groups)
    // Default page size is 3.
    expect(wrapper.vm.itemsToShow.map(g => g.name)).toEqual(['Alpha', 'Bravo', 'Charlie'])
  })
})

describe('GroupsTable network filter', () => {
  test('filters by the selected network (summary shape: networks = [{id}])', async () => {
    const groups = [
      { ...group, id: 1, name: 'In network', networks: [{ id: 2, name: 'N2' }] },
      { ...group, id: 2, name: 'Other network', networks: [{ id: 3, name: 'N3' }] },
    ]
    const wrapper = mountTable(groups)
    await wrapper.setData({ searchNetwork: 2 })
    expect(wrapper.vm.filteredItems.map(g => g.name)).toEqual(['In network'])
  })

  test('filters by the selected network (old shape: networks = [id])', async () => {
    const groups = [
      { ...group, id: 1, name: 'In network', networks: [2] },
      { ...group, id: 2, name: 'Other network', networks: [3] },
    ]
    const wrapper = mountTable(groups)
    await wrapper.setData({ searchNetwork: 2 })
    expect(wrapper.vm.filteredItems.map(g => g.name)).toEqual(['In network'])
  })
})

describe('GroupsTable next event cell', () => {
  test('formats a summary-shaped next_event object', () => {
    const wrapper = mountTable([{ ...group, next_event: { start: '2026-08-01T10:00:00+00:00' } }])
    expect(wrapper.text()).toContain('Sat 1st Aug 2026')
  })

  test('formats a moderation-store (newToOld) date string, not today', () => {
    const wrapper = mountTable([{ ...group, next_event: '2026-08-01' }])
    expect(wrapper.text()).toContain('Sat 1st Aug 2026')
  })
})

describe('GroupsTable mobile filter toggle', () => {
  // The template wired @click="toggleFilters" but the method was never
  // defined, so tapping "Show filters" on mobile threw and did nothing.
  test('clicking the toggle shows and hides the filter bar', async () => {
    const wrapper = mountTable([group], { search: true })
    expect(wrapper.vm.searchShow).toBe(false)

    await wrapper.find('.clickme').trigger('click')
    expect(wrapper.vm.searchShow).toBe(true)

    await wrapper.find('.clickme').trigger('click')
    expect(wrapper.vm.searchShow).toBe(false)
  })
})

describe('GroupsTable follow/unfollow button', () => {
  test('shows Unfollow for groups in yourGroups and Follow for others', () => {
    const groups = [
      { ...group, id: 1, name: 'Mine' },
      { ...group, id: 2, name: 'Not mine' },
    ]
    const wrapper = mountTable(groups, { yourGroups: [1] })
    const rows = wrapper.findAll('tbody tr')
    expect(rows.at(0).text()).toContain('groups.leave_group_button')
    expect(rows.at(0).text()).not.toContain('groups.join_group_button')
    expect(rows.at(1).text()).toContain('groups.join_group_button')
  })

  test('flips the button to Follow after unfollowing', async () => {
    const wrapper = mountTable([group], { yourGroups: [1] })
    expect(wrapper.find('tbody tr').text()).toContain('groups.leave_group_button')
    await wrapper.vm.leaveConfirmed(1)
    await wrapper.vm.$nextTick()
    expect(wrapper.find('tbody tr').text()).toContain('groups.join_group_button')
  })
})

// Neil's PR feedback: hovering a map pin should highlight the matching row.
describe('GroupsTable pin-hover row highlight', () => {
  test('row gets the highlight class when hover matches its id', () => {
    const wrapper = mountTable([group], { hover: 1 })
    expect(wrapper.find('tbody tr').classes()).toContain('group-row-hover')
  })

  test('no highlight when hover is another id or null', () => {
    expect(mountTable([group], { hover: 999 }).find('tbody tr').classes()).not.toContain('group-row-hover')
    expect(mountTable([group]).find('tbody tr').classes()).not.toContain('group-row-hover')
  })
})
