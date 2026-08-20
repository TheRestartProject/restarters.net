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
    flyTo: jest.fn(),
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

  // User feedback: the wheel scrolled the page instead of zooming the map,
  // and people couldn't work out how to zoom in/out.
  test('zooms with the mouse wheel', () => {
    expect(mountMap(WORLD, []).vm.mapOptions.scrollWheelZoom).toBe(true)
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

// Without clustering a large network is an unreadable mass of overlapping pins.
describe('GroupMap clustering', () => {
  // All within the fake map's bounds ([[50,-1],[52,1]]), close enough together
  // to cluster at its zoom.
  const many = Array.from({ length: 20 }, (_, i) => ({
    id: i + 1,
    location: { lat: 51 + i * 0.001, lng: 0 + i * 0.001 },
  }))

  function mountWithMap(groups) {
    const wrapper = mountMap(WORLD, groups)
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }
    return { wrapper, map }
  }

  test('gathers overlapping groups into a single cluster', () => {
    const { wrapper } = mountWithMap(many)

    const clusters = wrapper.vm.clusters
    expect(clusters.length).toBeLessThan(many.length)
    expect(clusters.some(c => c.properties.cluster)).toBe(true)
  })

  // Supercluster can quietly drop points when there are few of them, which is
  // obvious at high zoom. Below the threshold, show the groups themselves.
  test('shows every group individually when there are only a few', () => {
    const few = many.slice(0, 3)
    const { wrapper } = mountWithMap(few)

    const clusters = wrapper.vm.clusters
    expect(clusters).toHaveLength(3)
    expect(clusters.every(c => !c.properties.cluster)).toBe(true)
    expect(clusters.map(c => c.properties.groupId).sort()).toEqual([1, 2, 3])
  })

  // A four-figure count won't fit at the normal size in a fixed-size bubble.
  test('shrinks the text for counts that would overflow the circle', () => {
    const { wrapper } = mountWithMap(many)
    const cluster = wrapper.vm.clusters.find(c => c.properties.cluster)

    expect(wrapper.vm.clusterIcon(cluster).options.html)
      .not.toContain('group-cluster__count--wide')

    const big = { properties: { ...cluster.properties, point_count: 1234 } }
    expect(wrapper.vm.clusterIcon(big).options.html)
      .toContain('group-cluster__count--wide')
  })

  test('the cluster marker shows how many groups are in it', () => {
    const { wrapper } = mountWithMap(many)
    const cluster = wrapper.vm.clusters.find(c => c.properties.cluster)

    expect(wrapper.vm.clusterIcon(cluster).options.html)
      .toContain(String(cluster.properties.point_count))
  })

  test('clicking a cluster zooms in far enough to break it up', () => {
    const { wrapper, map } = mountWithMap(many)
    const cluster = wrapper.vm.clusters.find(c => c.properties.cluster)

    wrapper.vm.clusterClick(cluster)

    expect(map.flyTo).toHaveBeenCalled()
    const [, zoom] = map.flyTo.mock.calls[0]
    expect(zoom).toBeGreaterThan(map.getZoom())
    expect(zoom).toBeLessThanOrEqual(wrapper.vm.maxZoom)
  })

  // Leaflet's own .leaflet-div-icon would otherwise draw a white box with a grey
  // border behind the circle.
  test('the cluster icon replaces Leaflet default styling with its own class', () => {
    const { wrapper } = mountWithMap(many)
    const cluster = wrapper.vm.clusters.find(c => c.properties.cluster)

    const options = wrapper.vm.clusterIcon(cluster).options
    expect(options.className).toBe('group-cluster group-cluster--medium')
    // Anchored at its middle, so the circle sits over the point it represents.
    expect(options.iconSize).toEqual([46, 46])
    expect(options.iconAnchor).toEqual([23, 23])
  })
})

// User feedback on the PR 887 preview (Stratford): zoom 14 was too shallow to
// read street names, clustering stayed active right up to max zoom so a
// cluster of co-located groups could never be broken apart, and the 60px
// cluster radius left too many small bubbles on screen at once.
describe('GroupMap street-level zoom and identical locations', () => {
  // Twelve groups at the exact same venue - above the minCluster threshold,
  // so the clustering path (not the draw-them-all shortcut) is exercised.
  const colocated = Array.from({ length: 12 }, (_, i) => ({
    id: i + 1,
    location: { lat: 51.5417, lng: -0.0035 },
  }))

  test('allows zooming to street level (max zoom 18) by default', () => {
    expect(mountMap(WORLD, []).vm.maxZoom).toBe(18)
  })

  test('uses a wide cluster radius (120px) so fewer, larger bubbles show', () => {
    const wrapper = mountMap(WORLD, [])
    expect(wrapper.vm.clusterIndex.options.radius).toBe(120)
  })

  test('nudges each subsequent duplicate at a location by 0.00015 degrees', () => {
    const groups = [
      { id: 1, location: { lat: 51.5, lng: -0.1 } },
      { id: 2, location: { lat: 51.5, lng: -0.1 } },
    ]
    const wrapper = mountMap(WORLD, groups)

    const [first, second] = wrapper.vm.clusterPoints
    expect(first.geometry.coordinates).toEqual([-0.1, 51.5])
    expect(second.geometry.coordinates[0]).toBeCloseTo(-0.09985, 10)
    expect(second.geometry.coordinates[1]).toBeCloseTo(51.50015, 10)

    // Never mutate the store's own objects - the offset would corrupt the
    // group's real coordinates and accumulate on every recompute (a bug
    // Freegle hit with this exact approach, per its ClusterMarker comment).
    expect(groups[1].location).toEqual({ lat: 51.5, lng: -0.1 })
  })

  test('shows individual pins, not a cluster, at max zoom', () => {
    const wrapper = mountMap(WORLD, colocated)
    const map = fakeMap()
    map.getZoom = () => wrapper.vm.maxZoom
    map.getBounds = () => L.latLngBounds([[51.5, -0.1], [51.6, 0.1]])
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }

    const clusters = wrapper.vm.clusters
    expect(clusters).toHaveLength(12)
    expect(clusters.every((c) => !c.properties.cluster)).toBe(true)
  })

  // User feedback: cluster bubbles should visually indicate how many groups
  // they hold - bigger and more saturated for larger clusters.
  test('scales the cluster bubble and its colour tier with the group count', () => {
    const wrapper = mountMap(WORLD, [])
    const iconFor = (count) => wrapper.vm.clusterIcon({ properties: { cluster: true, point_count: count } }).options

    const small = iconFor(3)
    expect(small.iconSize).toEqual([36, 36])
    expect(small.className).toBe('group-cluster group-cluster--small')

    const medium = iconFor(20)
    expect(medium.iconSize).toEqual([46, 46])
    expect(medium.className).toBe('group-cluster group-cluster--medium')

    const large = iconFor(150)
    expect(large.iconSize).toEqual([56, 56])
    expect(large.className).toBe('group-cluster group-cluster--large')
    expect(large.iconAnchor).toEqual([28, 28])
  })

  test('clicking a cluster of co-located groups flies to max zoom, where it splits', () => {
    const wrapper = mountMap(WORLD, colocated)
    const map = fakeMap()
    map.getZoom = () => 17
    map.getBounds = () => L.latLngBounds([[51.5, -0.1], [51.6, 0.1]])
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }

    const cluster = wrapper.vm.clusters.find((c) => c.properties.cluster)
    expect(cluster).toBeTruthy()
    wrapper.vm.clusterClick(cluster)

    const [, zoom] = map.flyTo.mock.calls[0]
    expect(zoom).toBe(wrapper.vm.maxZoom)
  })
})

describe('GroupMap place search', () => {
  // Photon returns a London in England, another in Ontario, and more in
  // Kentucky, Ohio, Arkansas and California. Without the state and the country
  // every one of them renders as an identical bare "London".
  test('labels results with enough to tell same-named places apart', () => {
    const props = mountMap(WORLD, []).vm.placeNameProperties

    expect(props).toContain('state')
    expect(props).toContain('country')
    // Still leads with the specific part of the name.
    expect(props[0]).toBe('name')
  })

  // flyToBounds arcs out and back: going from Aberdeen to London it pulls out
  // two zoom levels below the destination over about three seconds. Once a
  // place has been picked from the dropdown, the journey isn't worth watching.
  test('cuts straight to a searched place instead of flying out and back', () => {
    const wrapper = mountMap(WORLD, [])
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }

    const bbox = L.latLngBounds([[51.28, -0.51], [51.69, 0.33]])
    wrapper.vm.goToPlace(bbox)

    expect(map.fitBounds).toHaveBeenCalledWith(bbox)
    expect(map.flyToBounds).not.toHaveBeenCalled()
  })

  // The distance column in the list below anchors to the searched place, so
  // the parent needs to know where the search landed.
  test('tells the parent where a search landed', () => {
    const wrapper = mountMap(WORLD, [])
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }

    const bbox = L.latLngBounds([[51.28, -0.51], [51.69, 0.33]])
    wrapper.vm.goToPlace(bbox)

    const [[point]] = wrapper.emitted().searched
    expect(point.lat).toBeCloseTo(51.485, 2)
    expect(point.lng).toBeCloseTo(-0.09, 2)
  })

  test('counts a search as having moved the map, so it stops reframing itself', () => {
    const wrapper = mountMap(WORLD, [])
    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }

    wrapper.vm.goToPlace(L.latLngBounds([[51.28, -0.51], [51.69, 0.33]]))

    expect(wrapper.vm.moved).toBe(true)
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
