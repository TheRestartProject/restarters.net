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

function mountMap(initialBounds, groups) {
  return shallowMount(GroupMap, {
    localVue,
    store: makeStore(groups),
    propsData: { initialBounds },
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
})
