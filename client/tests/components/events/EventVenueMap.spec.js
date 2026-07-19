import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import EventVenueMap from '../../../app/components/events/EventVenueMap.vue'

// The real @vue-leaflet/vue-leaflet package does its own dynamic import()s of
// Leaflet's marker PNGs to patch L.Icon.Default - a fix that needs a
// bundler/browser, not Node's module loader (Vitest runs component tests
// under Node). Mock the whole package rather than mount it for real, same
// approach GroupMap.spec.js takes. vi.mock() factories are hoisted above
// other top-level code, so the stubs are defined via vi.hoisted().
const { LMapStub, LTileLayerStub, LMarkerStub } = vi.hoisted(() => ({
  LMapStub: {
    name: 'LMap',
    props: ['zoom', 'center', 'options', 'useGlobalLeaflet'],
    template: '<div class="stub-lmap"><slot /></div>',
  },
  LTileLayerStub: {
    name: 'LTileLayer',
    props: ['url', 'attribution'],
    template: '<div class="stub-ltilelayer" />',
  },
  LMarkerStub: {
    name: 'LMarker',
    props: ['latLng', 'icon', 'interactive'],
    template: '<div class="stub-lmarker" />',
  },
}))

vi.mock('@vue-leaflet/vue-leaflet', () => ({ LMap: LMapStub, LTileLayer: LTileLayerStub, LMarker: LMarkerStub }))

function mountMap(props = {}) {
  return mount(EventVenueMap, { props: { lat: 51.5, lng: -0.1, ...props } })
}

describe('components/events/EventVenueMap', () => {
  it('renders the map container', () => {
    const wrapper = mountMap()
    expect(wrapper.find('[data-testid="event-venue-map"]').exists()).toBe(true)
  })

  it('centres the map on the given lat/lng', () => {
    const wrapper = mountMap({ lat: 51.5073509, lng: -0.1277583 })
    const lmap = wrapper.findComponent(LMapStub)
    expect(lmap.props('center')).toEqual([51.5073509, -0.1277583])
  })

  it('places a non-interactive marker at the same coordinates', () => {
    const wrapper = mountMap({ lat: 51.5, lng: -0.1 })
    const marker = wrapper.findComponent(LMarkerStub)
    expect(marker.props('latLng')).toEqual([51.5, -0.1])
    expect(marker.props('interactive')).toBe(false)
  })
})
