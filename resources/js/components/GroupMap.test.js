import Vue from "vue"
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { shallowMount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupMap from './GroupMap.vue'

const localVue = createLocalVue()
localVue.use(Vuex)
localVue.mixin(LangMixin)

function makeStore() {
  return new Vuex.Store({
    modules: {
      groups: {
        namespaced: true,
        getters: { list: () => [] },
      },
    },
  })
}

function fakeMap() {
  return {
    invalidateSize: jest.fn(),
    getBounds: () => ({}),
    getZoom: () => 5,
    getCenter: () => ({ lat: 0, lng: 0 }),
  }
}

describe('GroupMap visibility handling', () => {
  // Regression (grey map): when the map is created inside a hidden tab its
  // Leaflet container is 0x0. When the tab becomes visible nothing tells
  // Leaflet to re-measure, so tiles never fill the now-visible area and most
  // of the map shows as grey. The component must observe size changes and call
  // invalidateSize().
  test('calls invalidateSize when its container is resized (becomes visible)', () => {
    let resizeCb = null
    let observed = false
    global.ResizeObserver = class {
      constructor(cb) { resizeCb = cb }
      observe() { observed = true }
      unobserve() {}
      disconnect() {}
    }

    const wrapper = shallowMount(GroupMap, {
      localVue,
      store: makeStore(),
      propsData: { initialBounds: [] },
    })

    const map = fakeMap()
    wrapper.vm.mapObject = map
    wrapper.vm.$refs.map = { mapObject: map }

    expect(observed).toBe(true)
    expect(typeof resizeCb).toBe('function')

    resizeCb([{ contentRect: { width: 688, height: 400 } }])

    expect(map.invalidateSize).toHaveBeenCalled()
  })
})
