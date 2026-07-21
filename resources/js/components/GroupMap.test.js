import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { shallowMount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import L from 'leaflet'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupMap from './GroupMap.vue'

// GroupMap uses the global `L` (window.L) for LatLng / LatLngBounds.
global.L = L

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

// The groups page sends this inverted whole-world box when the user has no
// location set (min_lat 90 > max_lat -90).
const WORLD = [[90, 180], [-90, -180]]

function makeStore(groups = []) {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: { list: () => groups },
      },
    },
  })
}

function fakeMap(size = { x: 688, y: 400 }) {
  return {
    invalidateSize: jest.fn(),
    fitBounds: jest.fn(),
    flyToBounds: jest.fn(),
    getSize: () => size,
    getBounds: () => L.latLngBounds([[50, -1], [52, 1]]),
    getZoom: () => 5,
    getCenter: () => ({ lat: 0, lng: 0 }),
  }
}

function mountMap(initialBounds, groups, props = {}) {
  return shallowMount(GroupMap, {
    localVue,
    store: makeStore(groups),
    propsData: { initialBounds, ...props },
  })
}

describe('GroupMap visibility handling', () => {
  // Regression (grey map): when the map is created inside a hidden tab its
  // Leaflet container is 0x0. When the tab becomes visible nothing tells
  // Leaflet to re-measure, so tiles never fill the now-visible area and most
  // of the map shows as grey.
  test('calls invalidateSize when its container is resized (becomes visible)', () => {
    let resizeCb = null
    let observed = false
    global.ResizeObserver = class {
      constructor(cb) { resizeCb = cb }
      observe() { observed = true }
      unobserve() {}
      disconnect() {}
    }

    const wrapper = mountMap([], [])
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }

    expect(observed).toBe(true)
    expect(typeof resizeCb).toBe('function')

    resizeCb([{ contentRect: { width: 688, height: 400 } }])

    expect(map.invalidateSize).toHaveBeenCalled()
  })
})

describe('GroupMap.hasLocation', () => {
  test('is false for the inverted whole-world box (no user location)', () => {
    expect(mountMap(WORLD, []).vm.hasLocation).toBe(false)
  })

  test('is true for a real bounding box', () => {
    expect(mountMap([[51.0, -0.8], [51.8, 0.4]], []).vm.hasLocation).toBe(true)
  })
})

describe('GroupMap.zoomToGroups', () => {
  const groups = [
    { id: 1, location: { lat: 51.5, lng: -0.1 } },
    { id: 2, location: { lat: 53.4, lng: -2.2 } },
    { id: 3, location: { lat: 55.9, lng: -3.2 } },
  ]

  test('does not frame the map while it is still 0x0 (off-screen), so it can retry later', () => {
    const wrapper = mountMap(WORLD, groups)
    const map = fakeMap({ x: 0, y: 0 })
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    wrapper.vm.zoomedToGroups = false

    wrapper.vm.zoomToGroups()

    expect(map.fitBounds).not.toHaveBeenCalled()
    expect(wrapper.vm.zoomedToGroups).toBe(false)
  })

  test('frames ALL groups via fitBounds (not flyToBounds) when the user has no location', () => {
    const wrapper = mountMap(WORLD, groups)
    const map = fakeMap({ x: 688, y: 400 })
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    wrapper.vm.zoomedToGroups = false

    wrapper.vm.zoomToGroups()

    expect(map.flyToBounds).not.toHaveBeenCalled()
    expect(map.fitBounds).toHaveBeenCalledTimes(1)

    const bounds = map.fitBounds.mock.calls[0][0]
    // The framed bounds must contain every group, including the furthest ones.
    expect(bounds.contains([51.5, -0.1])).toBe(true)
    expect(bounds.contains([55.9, -3.2])).toBe(true)
  })

  // A group with no geocode has null lat/lng; +null is 0, so it used to be
  // framed as if it sat at null island (0,0), dragging the view out to sea.
  test('ignores groups without coordinates when framing', () => {
    const wrapper = mountMap(WORLD, [...groups, { id: 4, location: { lat: null, lng: null } }])
    const map = fakeMap({ x: 688, y: 400 })
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    wrapper.vm.zoomedToGroups = false

    wrapper.vm.zoomToGroups()

    const bounds = map.fitBounds.mock.calls[0][0]
    expect(bounds.contains([0, 0])).toBe(false)
  })
})

describe('GroupMap options', () => {
  // Regression: dragging was `!!window?.L?.Browser?.mobile`, i.e. enabled
  // ONLY on mobile — desktop users could not pan the map at all.
  test('allows dragging the map on desktop', () => {
    expect(mountMap(WORLD, []).vm.mapOptions.dragging).toBe(true)
  })

  test('does not set gestureHandling (the plugin is not installed)', () => {
    expect('gestureHandling' in mountMap(WORLD, []).vm.mapOptions).toBe(false)
  })
})

// A user who has set only their country gets a box around that country's groups.
// Framing the 5 nearest groups to the middle of a country would be useless - the
// centre of France is not near anyone in particular - so that only happens for a
// user who has coordinates of their own.
describe('GroupMap country-level framing', () => {
  const groups = [
    { id: 1, location: { lat: 51.5, lng: -0.1 } },
    { id: 2, location: { lat: 53.4, lng: -2.2 } },
    { id: 3, location: { lat: 55.9, lng: -3.2 } },
  ]
  const COUNTRY = [[50.0, -5.0], [56.0, 1.0]]

  test('fits the country box as given when the user has no coordinates of their own', () => {
    const wrapper = mountMap(COUNTRY, groups)
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    wrapper.vm.zoomedToGroups = false

    wrapper.vm.zoomToGroups()

    expect(map.fitBounds).toHaveBeenCalledTimes(1)
    expect(wrapper.vm.hasUserPoint).toBe(false)
    // The whole country box, not a tight box round a few groups near its middle.
    const bounds = map.fitBounds.mock.calls[0][0]
    expect(L.latLngBounds(bounds).contains([50.0, -5.0])).toBe(true)
    expect(L.latLngBounds(bounds).contains([56.0, 1.0])).toBe(true)
  })

  test('frames the groups nearest the user when they do have coordinates', () => {
    const wrapper = mountMap(COUNTRY, groups, { yourLat: 51.5, yourLng: -0.1 })
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    wrapper.vm.zoomedToGroups = false

    expect(wrapper.vm.hasUserPoint).toBe(true)

    wrapper.vm.zoomToGroups()

    const bounds = map.fitBounds.mock.calls[0][0]
    // Centred on the user's own point, not the middle of the country box.
    expect(bounds.contains([51.5, -0.1])).toBe(true)
  })

  // Country with no groups in it: the page can't build a box, so we still get the
  // inverted world box and fall back to showing everything.
  test('falls back to framing all groups when the country has none', () => {
    const wrapper = mountMap(WORLD, groups)
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    wrapper.vm.zoomedToGroups = false

    wrapper.vm.zoomToGroups()

    const bounds = map.fitBounds.mock.calls[0][0]
    expect(bounds.contains([51.5, -0.1])).toBe(true)
    expect(bounds.contains([55.9, -3.2])).toBe(true)
  })
})

// The map is centred on the user's town, and that town also goes in the "Search
// for a place..." box, as a hint that the map has already been searched for them.
describe('GroupMap place search preload', () => {
  test('preloads the search box with the area the map was centred on', () => {
    const wrapper = mountMap(WORLD, [], { yourArea: 'Ulverston' })
    const geocoder = { setQuery: jest.fn() }
    wrapper.vm.geocoder = geocoder

    wrapper.vm.presetSearch()

    expect(geocoder.setQuery).toHaveBeenCalledWith('Ulverston')
  })

  test('leaves the box empty when the user has no area set', () => {
    const wrapper = mountMap(WORLD, [], { yourArea: '' })
    const geocoder = { setQuery: jest.fn() }
    wrapper.vm.geocoder = geocoder

    wrapper.vm.presetSearch()

    expect(geocoder.setQuery).not.toHaveBeenCalled()
  })

  // The search box is built inside the Leaflet control, so there's nothing to
  // preload until the map is ready.
  test('does nothing when the geocoder control has not been created yet', () => {
    const wrapper = mountMap(WORLD, [], { yourArea: 'Ulverston' })
    expect(() => wrapper.vm.presetSearch()).not.toThrow()
  })
})

// Searching for a group that isn't in the current view should take you to it,
// not tell you there are no results. Framing is asked for explicitly rather
// than watching the group list, which changes whenever rows are hydrated and
// would otherwise yank the map away from wherever the user had panned to.
describe('GroupMap reframing on request', () => {
  const groups = [
    { id: 1, location: { lat: 51.5, lng: -0.1 } },
    { id: 2, location: { lat: 55.9, lng: -3.2 } },
  ]

  test('frames the groups it is showing when the request changes', async () => {
    const wrapper = mountMap(WORLD, groups, { frameRequest: 0 })
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    map.fitBounds.mockClear()

    await wrapper.setProps({ frameRequest: 1 })

    expect(map.fitBounds).toHaveBeenCalledTimes(1)
    const bounds = map.fitBounds.mock.calls[0][0]
    expect(bounds.contains([51.5, -0.1])).toBe(true)
    expect(bounds.contains([55.9, -3.2])).toBe(true)
  })

  test('does not move the map when there is nothing to frame', async () => {
    const wrapper = mountMap(WORLD, groups, { frameRequest: 0, groupids: [] })
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    map.fitBounds.mockClear()

    await wrapper.setProps({ frameRequest: 1 })

    expect(map.fitBounds).not.toHaveBeenCalled()
  })
})

describe('GroupMap markers', () => {
  test('only renders markers for groups with coordinates', () => {
    const wrapper = mountMap(WORLD, [
      { id: 1, location: { lat: 51.5, lng: -0.1 } },
      { id: 2, location: { lat: null, lng: null } },
      { id: 3, lat: 53.4, lng: -2.2 },
      { id: 4 },
    ])

    expect(wrapper.vm.mappableGroups.map(g => g.id)).toEqual([1, 3])
  })
})
