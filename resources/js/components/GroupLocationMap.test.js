import Vue from 'vue'
import { BootstrapVue } from 'bootstrap-vue'
Vue.use(BootstrapVue)

import { mount } from '@vue/test-utils'
import LangMixin from 'resources/js/mixins/lang.js'
import GroupLocationMap from './GroupLocationMap.vue'

const LMapStub = {
  name: 'l-map',
  template: '<div class="lmap-stub"><slot /></div>'
}

const LMarkerStub = {
  name: 'l-marker',
  props: ['latLng'],
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

test('shows the drag hint', () => {
  const wrapper = mountMap()
  expect(wrapper.text()).toContain('partials.dragmap')
})

test('dragging the map emits updated lat/lng', async () => {
  const wrapper = mountMap()

  wrapper.findComponent(LMapStub).vm.$emit('update:center', { lat: 52.1, lng: 1.3 })
  await wrapper.vm.$nextTick()

  expect(wrapper.emitted('update:lat').pop()).toEqual([52.1])
  expect(wrapper.emitted('update:lng').pop()).toEqual([1.3])
})

test('marker follows a dragged center', async () => {
  const wrapper = mountMap()

  wrapper.findComponent(LMapStub).vm.$emit('update:center', { lat: 48.85, lng: 2.35 })
  await wrapper.vm.$nextTick()

  expect(wrapper.findComponent(LMarkerStub).props('latLng')).toEqual([48.85, 2.35])
})

test('an external lat/lng change (new geocode) recentres the map', async () => {
  const wrapper = mountMap()

  await wrapper.setProps({ lat: 40.7, lng: -74.0 })

  expect(wrapper.findComponent(LMarkerStub).props('latLng')).toEqual([40.7, -74.0])
})

test('prop echo of a drag does not re-emit', async () => {
  const wrapper = mountMap()

  wrapper.findComponent(LMapStub).vm.$emit('update:center', { lat: 52.1, lng: 1.3 })
  await wrapper.vm.$nextTick()
  const emitsAfterDrag = wrapper.emitted('update:lat').length

  // Parent syncs the emitted values straight back down.
  await wrapper.setProps({ lat: 52.1, lng: 1.3 })

  expect(wrapper.emitted('update:lat').length).toBe(emitsAfterDrag)
})
