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

// The highlight/hover states used marker-icon-green.png / marker-icon-red.png,
// which are not shipped in public/images/vendor/leaflet/dist — the pins for
// groups you follow rendered as broken images (404).
describe('GroupMarker icon', () => {
  test('always uses the stock marker image that actually exists', () => {
    expect(mountMarker().vm.icon.options.iconUrl).toBe('/images/vendor/leaflet/dist/marker-icon.png')
    expect(mountMarker({ highlight: true }).vm.icon.options.iconUrl).toBe('/images/vendor/leaflet/dist/marker-icon.png')
    expect(mountMarker({ hover: true }).vm.icon.options.iconUrl).toBe('/images/vendor/leaflet/dist/marker-icon.png')
  })

  // Neil's PR feedback: pins looked mispositioned - a group in Ulverston appeared
  // to be out in Morecambe Bay.  With no iconSize/iconAnchor, Leaflet puts the
  // image's top-left corner on the coordinate rather than the tip of the pin.
  test('anchors the tip of the pin to the coordinate, not its top-left corner', () => {
    const options = mountMarker().vm.icon.options
    expect(options.iconSize).toEqual([25, 41])
    expect(options.iconAnchor).toEqual([12, 41])
  })

  test('recolours via a CSS class: green for groups you follow, red on hover', () => {
    expect(mountMarker().vm.icon.options.className).toBe('')
    expect(mountMarker({ highlight: true }).vm.icon.options.className).toBe('group-marker-yours')
    // Hover wins over highlight, matching the previous priority.
    expect(mountMarker({ hover: true, highlight: true }).vm.icon.options.className).toBe('group-marker-hover')
  })
})

// Neil's PR feedback: the group name took too long to appear on hover.  It was
// the native `title` attribute, whose delay is the browser's and can't be tuned;
// a Leaflet tooltip shows as soon as the pointer arrives.
describe('GroupMarker tooltip', () => {
  test('renders the name in a Leaflet tooltip rather than a native title', () => {
    const wrapper = mountMarker()

    const tooltip = wrapper.find('l-tooltip')
    expect(tooltip.exists()).toBe(true)
    expect(tooltip.text()).toContain('Test Group')
  })
})

// Neil's PR feedback: hovering a pin should highlight the matching list row
// (the reverse of row-hover → red pin).
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
    expect(wrapper.vm.icon.options.className).toBe('group-marker-hover')
  })
})
