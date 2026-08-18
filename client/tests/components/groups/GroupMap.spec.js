import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupMap from '../../../app/components/groups/GroupMap.vue'
import L from '../../../app/utils/leafletGlobal.js'
import en from '../../../i18n/locales/en.json'

// leaflet-control-geocoder's real Geocoder control needs a live Leaflet Map
// (control-container DOM, addControl internals) that a plain fake map object
// doesn't provide - mock it so onReady's geocoder wiring is verifiable
// without one. Keeps `geocoders.Photon` as a spyable constructor too.
const geocoderInstances = []
vi.mock('leaflet-control-geocoder', () => {
  class FakeGeocoder {
    constructor(options) {
      this.options = options
      geocoderInstances.push(this)
    }

    on(event, handler) {
      this._handlers ||= {}
      this._handlers[event] = handler
      return this
    }

    fire(event, payload) {
      this._handlers?.[event]?.(payload)
    }

    setQuery = vi.fn()
    addTo = vi.fn().mockReturnThis()
  }

  class FakePhoton {
    constructor(options) {
      this.type = 'photon'
      this.options = options
    }
  }

  return {
    Geocoder: FakeGeocoder,
    geocoders: { Photon: FakePhoton },
  }
})

// The real @vue-leaflet/vue-leaflet package does its own dynamic
// import()s of Leaflet's marker PNGs to patch L.Icon.Default (a fix that
// needs a bundler/browser, not Node's module loader - Vitest runs
// component tests under Node) - mock the whole package rather than mount
// it for real, same approach this repo already takes for Quill/Uppy
// (RichTextEditor.spec.js, TusImageUpload.spec.js). vi.mock() factories are
// hoisted above other top-level code, so the stubs are defined via
// vi.hoisted() rather than plain consts.
const { LMapStub, LTileLayerStub } = vi.hoisted(() => ({
  LMapStub: {
    name: 'LMap',
    props: ['minZoom', 'maxZoom', 'bounds', 'options', 'useGlobalLeaflet'],
    emits: ['ready', 'moveend', 'zoomend', 'dragend'],
    template: '<div class="stub-lmap"><slot /></div>',
  },
  LTileLayerStub: {
    name: 'LTileLayer',
    props: ['url', 'attribution'],
    template: '<div class="stub-ltilelayer" />',
  },
}))

vi.mock('@vue-leaflet/vue-leaflet', () => ({ LMap: LMapStub, LTileLayer: LTileLayerStub }))

function fakeLeafletMap({ size = { x: 688, y: 400 }, bounds } = {}) {
  return {
    addLayer: vi.fn(),
    invalidateSize: vi.fn(),
    fitBounds: vi.fn(),
    flyToBounds: vi.fn(),
    getSize: () => size,
    getBounds: () => bounds ?? L.latLngBounds([50, -1], [52, 1]),
    getZoom: () => 5,
    getCenter: () => ({ lat: 51, lng: 0 }),
  }
}

function mountMap(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })

  return mount(GroupMap, {
    props: { groups: [], ...props },
    global: {
      plugins: [i18n],
    },
  })
}

describe('components/groups/GroupMap', () => {
  let fakeClusterGroup

  beforeEach(() => {
    geocoderInstances.length = 0
    fakeClusterGroup = {
      addLayer: vi.fn(),
      clearLayers: vi.fn(),
      addLayers: vi.fn(),
    }
    vi.spyOn(L, 'markerClusterGroup').mockReturnValue(fakeClusterGroup)
  })

  // User feedback on PR 887: zoom 14 was too shallow to read street names or
  // tell close-together pins apart. CARTO's raster tiles serve up to z18.
  it('defaults to street-level max zoom (18)', () => {
    const wrapper = mountMap()
    expect(wrapper.findComponent(LMapStub).props('maxZoom')).toBe(18)
  })

  it('passes zoom bounds and CARTO tiles through to the underlying map/tile layer', () => {
    const wrapper = mountMap({ minZoom: 3, maxZoom: 12 })

    const lmap = wrapper.findComponent(LMapStub)
    expect(lmap.props('minZoom')).toBe(3)
    expect(lmap.props('maxZoom')).toBe(12)

    const tiles = wrapper.findComponent(LTileLayerStub)
    expect(tiles.props('url')).toContain('cartocdn.com')
  })

  describe('mapOptions', () => {
    it('allows dragging on desktop (regression: used to be mobile-only)', () => {
      const wrapper = mountMap()
      expect(wrapper.findComponent(LMapStub).props('options').dragging).toBe(true)
    })

    it('does not set gestureHandling (the plugin is not installed)', () => {
      const wrapper = mountMap()
      expect('gestureHandling' in wrapper.findComponent(LMapStub).props('options')).toBe(false)
    })
  })

  it('adds a marker-cluster layer to the map on ready', async () => {
    const wrapper = mountMap({ groups: [{ id: 1, name: 'Alpha', lat: 51.5, lng: -0.1 }] })
    const map = fakeLeafletMap()

    await wrapper.findComponent(LMapStub).vm.$emit('ready', map)

    expect(L.markerClusterGroup).toHaveBeenCalled()
    expect(map.addLayer).toHaveBeenCalledWith(fakeClusterGroup)
    expect(fakeClusterGroup.addLayers).toHaveBeenCalledTimes(1)
    expect(fakeClusterGroup.addLayers.mock.calls[0][0]).toHaveLength(1)
  })

  // User feedback on PR 887: the default 80px cluster radius left too many
  // small cluster bubbles on screen at once - a wider radius merges them
  // into fewer, larger clusters.
  it('widens the cluster radius to 120px to cut cluster-icon clutter', async () => {
    const wrapper = mountMap({ groups: [{ id: 1, name: 'Alpha', lat: 51.5, lng: -0.1 }] })

    await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap())

    expect(L.markerClusterGroup).toHaveBeenCalledWith(expect.objectContaining({ maxClusterRadius: 120 }))
  })

  // User feedback: cluster bubbles should visually indicate how many groups
  // they hold - bigger and more saturated for larger clusters, in the
  // restart style (black ring) rather than markercluster's default green/
  // yellow/orange discs.
  it('sizes and tiers the cluster bubbles by group count', async () => {
    const wrapper = mountMap({ groups: [{ id: 1, name: 'Alpha', lat: 51.5, lng: -0.1 }] })

    await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap())

    const { iconCreateFunction } = L.markerClusterGroup.mock.calls[0][0]
    const iconFor = (count) => iconCreateFunction({ getChildCount: () => count }).options

    const small = iconFor(3)
    expect(small.className).toBe('group-cluster group-cluster--small')
    expect(small.iconSize).toEqual([36, 36])
    expect(small.html).toContain('>3<')

    const medium = iconFor(20)
    expect(medium.className).toBe('group-cluster group-cluster--medium')
    expect(medium.iconSize).toEqual([46, 46])

    const large = iconFor(150)
    expect(large.className).toBe('group-cluster group-cluster--large')
    expect(large.iconSize).toEqual([56, 56])
    expect(large.iconAnchor).toEqual([28, 28])
  })

  // The pin matches the cluster's restart style: an inline-SVG divIcon with
  // black border and white inner, not the stock blue Leaflet PNG.
  it('draws restart-style SVG pins', async () => {
    const wrapper = mountMap({ groups: [{ id: 1, name: 'Alpha', lat: 51.5, lng: -0.1 }] })

    await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap())

    const marker = fakeClusterGroup.addLayers.mock.calls[0][0][0]
    const options = marker.getIcon().options
    expect(options.html).toContain('<svg')
    expect(options.className).toBe('group-pin')
    expect(options.iconSize).toEqual([30, 42])
    expect(options.iconAnchor).toEqual([15, 42])
  })

  // The identical-location nudge is only ~tens of px at z18, well inside the
  // cluster radius - without this, co-located pins would render as a "2"
  // cluster bubble even at max zoom and never visibly split.
  it('disables clustering at the map\'s max zoom so split pins render individually', async () => {
    const wrapper = mountMap({ groups: [{ id: 1, name: 'Alpha', lat: 51.5, lng: -0.1 }], maxZoom: 12 })

    await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap())

    expect(L.markerClusterGroup).toHaveBeenCalledWith(expect.objectContaining({ disableClusteringAtZoom: 12 }))
  })

  // Freegle's identical-location separation (ClusterMarker.vue): two groups
  // sharing exact coordinates must not render as one unclickable stack.
  it('separates markers for groups at identical coordinates', async () => {
    const wrapper = mountMap({
      groups: [
        { id: 1, name: 'Alpha', lat: 51.5, lng: -0.1 },
        { id: 2, name: 'Beta', lat: 51.5, lng: -0.1 },
      ],
    })

    await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap())

    const [first, second] = fakeClusterGroup.addLayers.mock.calls[0][0]
    expect(first.getLatLng()).not.toEqual(second.getLatLng())
  })

  it('only clusters groups with real coordinates', async () => {
    const wrapper = mountMap({
      groups: [
        { id: 1, name: 'Alpha', lat: 51.5, lng: -0.1 },
        { id: 2, name: 'No location', lat: null, lng: null },
      ],
    })

    await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap())

    expect(fakeClusterGroup.addLayers.mock.calls[0][0]).toHaveLength(1)
  })

  describe('groups-in-bounds reporting', () => {
    it('emits update:groupIdsInBounds with the ids inside the current view on ready', async () => {
      const wrapper = mountMap({
        groups: [
          { id: 1, name: 'In view', lat: 51.0, lng: 0.0 },
          { id: 2, name: 'Out of view', lat: 60.0, lng: 10.0 },
        ],
      })

      await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap({ bounds: L.latLngBounds([50, -1], [52, 1]) }))

      const emitted = wrapper.emitted('update:groupIdsInBounds')
      expect(emitted[emitted.length - 1]).toEqual([[1]])
    })

    // Regression ported from GroupMapAndList.test.js: an empty result is a
    // real answer and must be emitted as [], not swallowed.
    it('emits an empty array when nothing is in view', async () => {
      const wrapper = mountMap({ groups: [{ id: 1, name: 'Elsewhere', lat: 60, lng: 10 }] })

      await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap({ bounds: L.latLngBounds([50, -1], [52, 1]) }))

      const emitted = wrapper.emitted('update:groupIdsInBounds')
      expect(emitted[emitted.length - 1]).toEqual([[]])
    })

    it('re-reports on moveend/zoomend/dragend', async () => {
      const wrapper = mountMap({ groups: [{ id: 1, name: 'A', lat: 51.0, lng: 0.0 }] })
      const map = fakeLeafletMap({ bounds: L.latLngBounds([50, -1], [52, 1]) })
      await wrapper.findComponent(LMapStub).vm.$emit('ready', map)

      const before = wrapper.emitted('update:groupIdsInBounds').length

      map.getBounds = () => L.latLngBounds([80, 80], [81, 81])
      await wrapper.findComponent(LMapStub).vm.$emit('moveend')

      const after = wrapper.emitted('update:groupIdsInBounds')
      expect(after.length).toBeGreaterThan(before)
      expect(after[after.length - 1]).toEqual([[]])
    })
  })

  describe('geocoder search control', () => {
    it('is added to the map with translated placeholder/error strings', async () => {
      const wrapper = mountMap()
      const map = fakeLeafletMap()

      await wrapper.findComponent(LMapStub).vm.$emit('ready', map)

      expect(geocoderInstances).toHaveLength(1)
      expect(geocoderInstances[0].options.placeholder).toBe(en.groups.search_place)
      expect(geocoderInstances[0].options.errorMessage).toBe(en.groups.search_nothing_found)
      expect(geocoderInstances[0].options.collapsed).toBe(false)
      expect(geocoderInstances[0].addTo).toHaveBeenCalledWith(map)
    })

    it('flies to the selected result\'s bounding box and clears the query', async () => {
      const wrapper = mountMap()
      const map = fakeLeafletMap()
      await wrapper.findComponent(LMapStub).vm.$emit('ready', map)

      const control = geocoderInstances[0]
      const bbox = L.latLngBounds([1, 1], [2, 2])
      control.fire('markgeocode', { geocode: { bbox } })

      expect(control.setQuery).toHaveBeenCalledWith('')
      expect(map.flyToBounds).toHaveBeenCalledWith(bbox)
    })
  })

  describe('hover linking', () => {
    it('emits update:hoveredId when a marker is hovered', async () => {
      const wrapper = mountMap({ groups: [{ id: 7, name: 'Hover Me', lat: 51.0, lng: 0.0 }] })
      await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap())

      const marker = fakeClusterGroup.addLayers.mock.calls[0][0][0]
      marker.fire('mouseover')
      expect(wrapper.emitted('update:hoveredId').at(-1)).toEqual([7])

      marker.fire('mouseout')
      expect(wrapper.emitted('update:hoveredId').at(-1)).toEqual([null])
    })

    // PR 887: a marker click opens the group-info modal. The imperative
    // Leaflet marker bridges to Vue by emitting `select` with the group id,
    // which the page turns into a GroupInfoModal.
    it('emits select with the group id when a marker is clicked', async () => {
      const wrapper = mountMap({ groups: [{ id: 7, name: 'Click Me', lat: 51.0, lng: 0.0 }] })
      await wrapper.findComponent(LMapStub).vm.$emit('ready', fakeLeafletMap())

      const marker = fakeClusterGroup.addLayers.mock.calls[0][0][0]
      marker.fire('click')
      expect(wrapper.emitted('select').at(-1)).toEqual([7])
    })
  })
})
