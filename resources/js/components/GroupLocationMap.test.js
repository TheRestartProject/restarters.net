import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupLocationMap from './GroupLocationMap.vue'

const LMapStub = {
  name: 'l-map',
  props: ['center', 'zoom'],
  template: '<div class="lmap-stub"><slot /></div>'
}

const LMarkerStub = {
  name: 'l-marker',
  props: ['latLng', 'draggable'],
  template: '<div class="lmarker-stub" />'
}

function mountMap (props = {}) {
  return mount(GroupLocationMap, {
    mixins: [LangMixin],
    propsData: { lat: 51.5, lng: -0.12, ...props },
    stubs: {
      'l-map': LMapStub,
      'l-tile-layer': true,
      'l-marker': LMarkerStub
    }
  })
}

// What Leaflet passes to a marker's dragend handler.
function dragMarkerTo (wrapper, lat, lng) {
  wrapper.findComponent(LMarkerStub).vm.$emit('dragend', { target: { getLatLng: () => ({ lat, lng }) } })
}

test('shows the drag hint', () => {
  const wrapper = mountMap()
  expect(wrapper.text()).toContain('partials.dragmap')
})

test('the marker is draggable and starts zoomed in on the location', () => {
  const wrapper = mountMap()
  expect(wrapper.findComponent(LMarkerStub).props('draggable')).toBe(true)
  expect(wrapper.findComponent(LMapStub).props('zoom')).toBeGreaterThanOrEqual(15)
  expect(wrapper.findComponent(LMapStub).props('center')).toEqual([51.5, -0.12])
})

test('dragging the marker emits updated lat/lng', async () => {
  const wrapper = mountMap()

  dragMarkerTo(wrapper, 52.1, 1.3)
  await wrapper.vm.$nextTick()

  expect(wrapper.emitted('update:lat').pop()).toEqual([52.1])
  expect(wrapper.emitted('update:lng').pop()).toEqual([1.3])
  expect(wrapper.findComponent(LMarkerStub).props('latLng')).toEqual([52.1, 1.3])
})

test('panning or zooming the map does not move the marker', async () => {
  const wrapper = mountMap()

  wrapper.findComponent(LMapStub).vm.$emit('update:center', { lat: 48.85, lng: 2.35 })
  wrapper.findComponent(LMapStub).vm.$emit('update:zoom', 12)
  await wrapper.vm.$nextTick()

  expect(wrapper.emitted('update:lat')).toBeUndefined()
  expect(wrapper.findComponent(LMarkerStub).props('latLng')).toEqual([51.5, -0.12])
})

test('an external lat/lng change (new geocode) moves the marker and the map', async () => {
  const wrapper = mountMap()

  await wrapper.setProps({ lat: 40.7, lng: -74.0 })

  expect(wrapper.findComponent(LMarkerStub).props('latLng')).toEqual([40.7, -74.0])
  expect(wrapper.findComponent(LMapStub).props('center')).toEqual([40.7, -74.0])
})

test('prop echo of a drag does not recentre the map', async () => {
  const wrapper = mountMap()

  dragMarkerTo(wrapper, 52.1, 1.3)
  await wrapper.vm.$nextTick()

  // Parent syncs the emitted values straight back down.
  await wrapper.setProps({ lat: 52.1, lng: 1.3 })

  expect(wrapper.findComponent(LMapStub).props('center')).toEqual([51.5, -0.12])
})
