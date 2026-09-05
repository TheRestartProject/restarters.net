import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { shallowMount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import L from 'leaflet'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupMarker from './GroupMarker.vue'

// GroupMarker uses the global `L` for L.icon.
global.L = L

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

function makeStore() {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: {
          get: () => () => ({ id: 1, name: 'Test Group', location: { lat: 51.5, lng: -0.1 } }),
        },
      },
    },
  })
}

function mountMarker(props = {}) {
  return shallowMount(GroupMarker, {
    localVue,
    store: makeStore(),
    propsData: { id: 1, ...props },
  })
}

// User feedback: the pin should share the cluster bubble's restart style -
// black border, white inner - rather than the stock blue Leaflet teardrop.
// An inline-SVG divIcon rather than an image, so the states can recolour the
// fill directly instead of hue-rotating a blue PNG.
describe('GroupMarker icon', () => {
  test('draws the restart-style pin: a black-bordered white teardrop SVG', () => {
    const options = mountMarker().vm.icon.options
    expect(options.html).toContain('<svg')
    expect(options.className).toBe('group-pin')
  })

  // With no iconSize/iconAnchor, Leaflet puts the icon's top-left corner on the
  // coordinate rather than the tip of the pin, so the marker points somewhere
  // other than the place it marks.
  test('anchors the tip of the pin to the coordinate, not its top-left corner', () => {
    const options = mountMarker().vm.icon.options
    expect(options.iconSize).toEqual([30, 42])
    expect(options.iconAnchor).toEqual([15, 42])
    // Measured from the anchor (the tip), so the tooltip clears the pin head.
    expect(options.tooltipAnchor).toEqual([0, -42])
  })

  test('recolours via a CSS class: green for groups you follow, red on hover', () => {
    expect(mountMarker().vm.icon.options.className).toBe('group-pin')
    expect(mountMarker({ highlight: true }).vm.icon.options.className).toBe('group-pin group-pin--yours')
    // Hover wins over highlight, matching the previous priority.
    expect(mountMarker({ hover: true, highlight: true }).vm.icon.options.className).toBe('group-pin group-pin--hover')
  })
})

// Groups at the exact same venue are separated by a tiny display-only nudge
// (GroupMap's clusterPoints). The marker resolves its position from the
// store, so the nudged position must be passable from outside - otherwise
// both pins would still draw on the same spot.
describe('GroupMarker position override', () => {
  test('draws at the store position by default', () => {
    const vm = mountMarker().vm
    expect([vm.lat, vm.lng]).toEqual([51.5, -0.1])
  })

  test('draws at the passed lat-lng when one is given', () => {
    const vm = mountMarker({ latLng: [51.50015, -0.09985] }).vm
    expect([vm.lat, vm.lng]).toEqual([51.50015, -0.09985])
  })
})

// The name must come from a Leaflet tooltip rather than the native `title`
// attribute: `title` appears only after the browser's own delay, which can't be
// tuned, whereas a tooltip shows as soon as the pointer arrives.
describe('GroupMarker tooltip', () => {
  test('renders the name in a Leaflet tooltip rather than a native title', () => {
    const wrapper = mountMarker()

    const tooltip = wrapper.find('l-tooltip')
    expect(tooltip.exists()).toBe(true)
    expect(tooltip.text()).toContain('Test Group')
  })
})

// Hovering a pin highlights the matching list row - the reverse of the
// row-hover → red pin direction.
describe('GroupMarker hover emission', () => {
  test('mouseover emits update:hover with the group id, mouseout clears it', async () => {
    const wrapper = mountMarker()

    wrapper.vm.markerHover(true)
    expect(wrapper.emitted('update:hover').pop()).toEqual([1])

    wrapper.vm.markerHover(false)
    expect(wrapper.emitted('update:hover').pop()).toEqual([null])
  })

  test('marker hover also turns its own pin red', () => {
    const wrapper = mountMarker()
    wrapper.vm.markerHover(true)
    expect(wrapper.vm.icon.options.className).toBe('group-pin group-pin--hover')
  })
})
