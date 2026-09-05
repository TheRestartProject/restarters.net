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

describe('GroupsTable columns', () => {
  // A row says what the group is and where: logo, name, tags, location and when
  // they next meet. Volunteer headcounts don't help anyone choose a group.
  test('shows no host or restarter counts', () => {
    const keys = mountTable().vm.fields.map(f => f.key)

    expect(keys).not.toContain('hosts')
    expect(keys).not.toContain('restarters')
    expect(keys).toEqual(['group_image', 'group_name', 'location', 'next_event'])
  })

  // Following a group is a decision to make on the group's own page, having
  // read about it - not something to click down a list.
  test('offers no follow or leave button', () => {
    const html = mountTable([group], { yourGroups: [] }).html()

    expect(html).not.toContain('groups.join_group_button')
    expect(html).not.toContain('groups.leave_group_button')
  })

  // Moderation reuses this table, and its rows do need an action.
  test('keeps the moderation link when approving', () => {
    const wrapper = mountTable([group], { approve: true })

    expect(wrapper.vm.fields.map(f => f.key)).toContain('following')
    expect(wrapper.html()).toContain('groups.group_requires_moderation')
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
    expect(wrapper.vm.itemsToShow.map(g => g.name)).toEqual(['Alpha', 'Bravo', 'Charlie', 'Delta'])
  })
})

// Zooming out puts far more groups in view, but the row count was frozen at
// whatever the infinite scroll had reached - 3 groups in view or 230, the list
// showed the same handful, which reads as the list being stuck.
describe('GroupsTable count text', () => {
  test('counts the groups in the area when there are some', () => {
    const wrapper = mountTable([group], { count: true })

    expect(wrapper.vm.translatedGroupCount).toBe('groups.group_count')
  })

  // "There are 0 groups in this area" is a dead end. When nothing is in view,
  // point the user at finding a group near them instead.
  test('points elsewhere when there are none in the area', () => {
    const wrapper = mountTable([], { count: true })

    expect(wrapper.vm.translatedGroupCount).toBe('groups.group_count_none')
  })
})

describe('GroupsTable paging', () => {
  const many = Array.from({ length: 60 }, (_, i) => ({
    ...group,
    id: i + 1,
    name: 'Group ' + String(i + 1).padStart(3, '0'),
  }))

  test('shows a useful page of rows, not a handful', () => {
    const wrapper = mountTable(many)

    expect(wrapper.vm.itemsToShow.length).toBeGreaterThanOrEqual(20)
  })

  test('starts a fresh page when the map viewport changes', async () => {
    const wrapper = mountTable(many)
    wrapper.vm.show = 60
    await wrapper.vm.$nextTick()

    // The map reports a different set of groups in view.
    await wrapper.setProps({ groupids: many.slice(0, 30).map(g => g.id) })

    expect(wrapper.vm.show).toBe(wrapper.vm.pageSize)
  })

  test('leaves the page alone when the same groups are reported again', async () => {
    const wrapper = mountTable(many)
    wrapper.vm.show = 60
    await wrapper.vm.$nextTick()

    // Hydration re-runs the computed and hands over an equal, but new, array.
    await wrapper.setProps({ groupids: [...many.map(g => g.id)] })

    expect(wrapper.vm.show).toBe(60)
  })

  test('loads a page at a time, not a row at a time', () => {
    const wrapper = mountTable(many)
    const before = wrapper.vm.show

    wrapper.vm.loadMore({ loaded: () => {}, complete: () => {} })

    expect(wrapper.vm.show).toBe(before + wrapper.vm.pageSize)
  })
})

describe('GroupsTable distance sort', () => {
  // Someone looking at a map wants the groups they can see, nearest first.
  // Alphabetical order tells them nothing about where anything is.
  const far = { ...group, id: 1, name: 'Alpha', location: { lat: 55.9, lng: -3.2 } }
  const near = { ...group, id: 2, name: 'Zulu', location: { lat: 51.5, lng: -0.1 } }
  // Named so that neither expected distance order matches alphabetical order,
  // or the test would pass without any distance sorting at all.
  const middle = { ...group, id: 3, name: 'Aaa', location: { lat: 53.4, lng: -2.2 } }

  test('orders by distance from the centre of the map, closest first', () => {
    const wrapper = mountTable([far, near, middle], { centre: { lat: 51.5, lng: -0.1 } })

    expect(wrapper.vm.itemsToShow.map(g => g.name)).toEqual(['Zulu', 'Aaa', 'Alpha'])
  })

  test('re-orders when the map moves', async () => {
    const wrapper = mountTable([far, near, middle], { centre: { lat: 51.5, lng: -0.1 } })

    await wrapper.setProps({ centre: { lat: 55.9, lng: -3.2 } })

    expect(wrapper.vm.itemsToShow.map(g => g.name)).toEqual(['Alpha', 'Aaa', 'Zulu'])
  })

  // The table is used away from the map too, where there is no centre to
  // measure from.
  test('falls back to alphabetical when there is no map centre', () => {
    const wrapper = mountTable([far, near, middle])

    expect(wrapper.vm.itemsToShow.map(g => g.name)).toEqual(['Aaa', 'Alpha', 'Zulu'])
  })

  test('sorts groups with no coordinates to the end', () => {
    const nowhere = { ...group, id: 4, name: 'Bravo', location: { lat: null, lng: null } }
    const wrapper = mountTable([nowhere, near], { centre: { lat: 51.5, lng: -0.1 } })

    expect(wrapper.vm.itemsToShow.map(g => g.name)).toEqual(['Zulu', 'Bravo'])
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

// Hovering a map pin highlights the matching row.
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

// User feedback: the list under the map should say how far away each group
// is - from your own location, or from the place you searched for - and be
// ordered nearest-first by default.
describe('GroupsTable distance column', () => {
  const reference = { lat: 51.5, lng: -0.1 }
  const near = { ...group, id: 1, name: 'Zed Near', location: { location: 'Town', country: 'UK', distance: null, lat: 51.5, lng: -0.2 } }
  const far = { ...group, id: 2, name: 'Alpha Far', location: { location: 'City', country: 'UK', distance: null, lat: 52.5, lng: -1.9 } }
  const nowhere = { ...group, id: 3, name: 'Mid Nowhere', location: { location: 'X', country: 'UK', distance: null, lat: null, lng: null } }

  test('hides the distance column without a reference point', () => {
    const wrapper = mountTable([near, far])
    expect(wrapper.vm.fields.map(f => f.key)).not.toContain('distance')
  })

  test('shows a sortable distance column when a reference point is given', () => {
    const wrapper = mountTable([near, far], { referencePoint: reference })
    const field = wrapper.vm.fields.find(f => f.key === 'distance')
    expect(field).toBeTruthy()
    expect(field.sortable).toBe(true)
    expect(wrapper.text()).toContain('km')
  })

  test('measures real km from the reference point', () => {
    const wrapper = mountTable([near], { referencePoint: reference })
    // 0.1 degrees of longitude at 51.5N is ~6.9 km - a haversine result, not
    // the flat-degree approximation used for relative ordering.
    expect(wrapper.vm.distanceKmFrom(near)).toBeCloseTo(6.9, 0)
  })

  test('orders nearest-first by default, groups without coordinates last', () => {
    // Alphabetical order would put Alpha Far first - nearest-first must win.
    const wrapper = mountTable([far, nowhere, near], { referencePoint: reference })
    expect(wrapper.vm.itemsToShow.map(g => g.name)).toEqual(['Zed Near', 'Alpha Far', 'Mid Nowhere'])
  })
})
