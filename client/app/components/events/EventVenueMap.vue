<script setup>
import { computed } from 'vue'
import { LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'
// Side-effecting: sets window.L so <LMap use-global-leaflet> shares the same
// Leaflet instance the manually-built L.icon() below comes from - see
// components/groups/GroupMap.vue's identical import and doc comment.
import L from '../../utils/leafletGlobal.js'
import { LEAFLET_ATTRIBUTION } from '../../utils/mapConstants.js'
import { useLeafletTiles } from '~/composables/useLeafletTiles.js'

const leafletTiles = useLeafletTiles()

// Small, static single-marker map for the event venue (party/view/[id].vue,
// gap D6). Functional spec: resources/js/components/EventDetails.vue's
// <l-map>/<l-marker> pair (zoom 16, 200px tall, non-interactive marker) shown
// under the venue address/"view map" link for in-person events with
// coordinates - this is that same shape, just built on vue-leaflet 0.10
// (LMap/LMarker) rather than the legacy vue2-leaflet package. No clustering,
// geocoder search box, or pan/zoom event wiring needed here (single fixed
// point), so this intentionally doesn't share GroupMap.vue's imperative
// marker-cluster machinery.
const props = defineProps({
  lat: {
    type: Number,
    required: true,
  },
  lng: {
    type: Number,
    required: true,
  },
  // EventDetails.vue:74 shows the venue map at zoom 16; VenueAddress.vue:31
  // (the create/edit form's own preview) uses zoom 11 instead - callers pass
  // their own value, defaulting to the (more common) view-page zoom.
  zoom: {
    type: Number,
    default: 16,
  },
})

const center = computed(() => [props.lat, props.lng])

const mapOptions = {
  zoomControl: true,
  dragging: true,
  touchZoom: true,
  scrollWheelZoom: false,
}

// Leaflet's default marker icon resolves image paths relative to the CSS
// file under a plain <script> include - broken under Vite bundling unless
// pointed at the imported, hashed asset URLs directly (same fix as
// GroupMap.vue's buildIcon()).
const icon = L.icon({
  iconUrl: markerIconUrl,
  iconRetinaUrl: markerIcon2xUrl,
  shadowUrl: markerShadowUrl,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41],
})
</script>

<template>
  <div class="event-venue-map" data-testid="event-venue-map">
    <LMap
      :zoom="zoom"
      :center="center"
      :options="mapOptions"
      use-global-leaflet
      style="width: 100%; height: 200px"
    >
      <LTileLayer :url="leafletTiles" :attribution="LEAFLET_ATTRIBUTION" />
      <LMarker :lat-lng="center" :icon="icon" :interactive="false" />
    </LMap>
  </div>
</template>

<style scoped>
.event-venue-map {
  width: 100%;
}
</style>
