<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { LMap, LTileLayer } from '@vue-leaflet/vue-leaflet'
import { Geocoder, geocoders } from 'leaflet-control-geocoder'
// The control builds its "nothing found" element at init; this stylesheet is
// what keeps it hidden until a search actually fails (and styles the
// button) - ported from resources/js/components/GroupMap.vue's own comment.
import 'leaflet-control-geocoder/dist/Control.Geocoder.css'
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
// MarkerCluster.Default.css (the green/yellow/orange discs) is deliberately
// NOT imported: the bubbles use the restart-style .group-cluster classes
// below instead, via clusterIcon.
// Side-effecting: sets window.L before leaflet.markercluster (which reads it
// as a bare global) is imported - see the file's own comment.
import L from '../../utils/leafletGlobal.js'
import 'leaflet.markercluster'
import { LEAFLET_ATTRIBUTION, LEAFLET_TILES, MAP_CLUSTER_RADIUS, MAX_MAP_ZOOM, MIN_MAP_ZOOM } from '../../utils/mapConstants.js'
import { boundingBoxFor, filterMappableGroups, hasLocation as computeHasLocation, idsInBounds, markerClassName, nearestGroups, separateIdenticalLocations } from '../../composables/useGroupMapGeometry.js'

// Leaflet map for /group/map: clustering pins from the names index (drawn
// for every non-archived group up front - see stores/groups.js's B7 doc
// comment) plus a Photon place-search box. resources/js/components/
// GroupMap.vue (+ GroupMarker.vue for the icon/hover rules) is the
// functional spec; see that file and useGroupMapGeometry.js for what's
// ported 1:1 vs simplified. Two deliberate deviations, both because this
// branch has no v2 endpoint yet to source them from (api-gaps.md-style
// gap, not fixed here):
//  - `initialBounds`: legacy's GroupController::nearby() scans the user's
//    own nearby groups server-side for a bounding box. No v2 endpoint
//    exposes that (nor does GET /api/v2/session carry the user's lat/lng),
//    so the caller (pages/group/map.vue) always passes null, which
//    computeHasLocation treats as "no location" - the map frames every
//    group on first paint instead of centring into the user's own area.
//    Panning/zooming/search behave identically once loaded.
//  - Marker click: opens develop's GroupInfoModal (next event + Go to group,
//    PR 887 / RES-1995). Markers here are plain Leaflet layers (imperative,
//    for clustering - vue-leaflet has no cluster-aware child component), so a
//    Vue modal isn't reachable from inside the click handler directly; the
//    handler emits `select` with the group id instead, and the page renders
//    GroupInfoModal for it (components/groups/GroupInfoModal.vue).
const props = defineProps({
  // Names-index entries: {id, name, lat, lng, network_ids, ...}.
  groups: {
    type: Array,
    default: () => [],
  },
  // [[southLat, westLng], [northLat, eastLng]], or null (see doc comment).
  initialBounds: {
    type: Array,
    default: null,
  },
  minZoom: {
    type: Number,
    default: MIN_MAP_ZOOM,
  },
  maxZoom: {
    type: Number,
    default: MAX_MAP_ZOOM,
  },
  network: {
    type: Number,
    default: null,
  },
  // Ids the current user is a member of, for the "your groups" green pin -
  // best-effort, same memberIds gap noted throughout stores/groups.js.
  yourGroupIds: {
    type: Array,
    default: () => [],
  },
  hoveredId: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['update:groupIdsInBounds', 'update:hoveredId', 'select'])

const { t } = useI18n()

const mapOptions = {
  zoomControl: true,
  dragging: true,
  touchZoom: true,
  scrollWheelZoom: false,
  bounceAtZoomLimits: true,
}

const mappableGroups = computed(() => filterMappableGroups(props.groups, props.network))
const hasLocationValue = computed(() => computeHasLocation(props.initialBounds))

const containerEl = ref(null)
let mapObject = null
let clusterGroup = null
let resizeObserver = null
let zoomedToGroups = false
let moved = false
const markersById = new Map()

// The pin shares the cluster bubble's restart style - black border, white
// inner (PR 887 user feedback; it was the stock blue Leaflet teardrop). An
// inline-SVG divIcon rather than an image, so the follow/hover states
// recolour the fill directly instead of hue-rotating a PNG.
function buildIcon(className) {
  return L.divIcon({
    html:
      '<svg viewBox="0 0 30 42" width="30" height="42" aria-hidden="true">' +
      '<path d="M15 1.5C7.8 1.5 2 7.3 2 14.5c0 9.5 13 26 13 26s13-16.5 13-26C28 7.3 22.2 1.5 15 1.5z"/>' +
      '<circle cx="15" cy="14.5" r="4.5"/>' +
      '</svg>',
    // Anchor the tip of the teardrop to the coordinate, not its corner.
    iconSize: [30, 42],
    iconAnchor: [15, 42],
    popupAnchor: [0, -42],
    className,
  })
}

// The bubble indicates how many groups it holds: bigger and more saturated
// for larger clusters (user feedback - a flat wall of identical bubbles
// gave no sense of where the groups actually are). Same tiers and markup
// as PR 887's clusterIcon.
function clusterIcon(cluster) {
  const count = cluster.getChildCount()

  let tier = 'small'
  let size = 36
  if (count >= 100) {
    tier = 'large'
    size = 56
  } else if (count >= 10) {
    tier = 'medium'
    size = 46
  }

  return L.divIcon({
    html: `<div class="group-cluster__count">${count}</div>`,
    className: `group-cluster group-cluster--${tier}`,
    iconSize: [size, size],
    iconAnchor: [size / 2, size / 2],
  })
}

function iconFor(groupId) {
  return buildIcon(markerClassName(groupId, { hoveredId: props.hoveredId, yourGroupIds: props.yourGroupIds }))
}

function rebuildMarkers() {
  if (!clusterGroup) return

  clusterGroup.clearLayers()
  markersById.clear()

  // Nudged copies for display only - bounds reporting and zoom framing
  // (idle/zoomToGroups) stay on the groups' real coordinates.
  const markers = separateIdenticalLocations(mappableGroups.value).map((group) => {
    const marker = L.marker([group.lat, group.lng], {
      title: `${group.name} - ${t('groups.marker_title')}`,
      icon: iconFor(group.id),
    })
    // Imperative Leaflet marker -> Vue: a click emits `select` with the id,
    // which the page turns into a GroupInfoModal (next event + Go to group),
    // replacing develop's marker popup with PR 887's modal (RES-1995).
    marker.on('click', () => emit('select', group.id))
    marker.on('mouseover', () => emit('update:hoveredId', group.id))
    marker.on('mouseout', () => emit('update:hoveredId', null))
    markersById.set(group.id, marker)
    return marker
  })

  clusterGroup.addLayers(markers)
}

function idle() {
  if (!mapObject) return

  const bounds = mapObject.getBounds()
  emit('update:groupIdsInBounds', idsInBounds(mappableGroups.value, bounds))

  zoomToGroups()
}

function zoomToGroups() {
  if (!mapObject) return

  // Only zoom once the map has a real size - see refreshSize()'s comment:
  // a 0x0 container (created in a hidden tab) would frame on null island.
  const mapSized = mapObject.getSize().x > 0
  if (zoomedToGroups || !mapSized || !mappableGroups.value.length) {
    return
  }
  zoomedToGroups = true

  const framed = hasLocationValue.value ? nearestGroups(mappableGroups.value, mapObject.getCenter(), 5) : mappableGroups.value

  const box = boundingBoxFor(framed)
  if (!box) return

  const bounds = L.latLngBounds([box.minLat, box.minLng], [box.maxLat, box.maxLng])
  if (!bounds.isValid()) return

  // fitBounds, not flyToBounds: the animated fly fights a reactive bounds
  // binding and can leave the map mid-animation (ported comment/reasoning
  // from the legacy component, which hit this as a grey-map regression).
  mapObject.fitBounds(bounds.pad(0.1))
}

function refreshSize() {
  if (!mapObject) return

  mapObject.invalidateSize()
  if (!moved) {
    zoomedToGroups = false
    zoomToGroups()
  }
  idle()
}

function onDragEnd() {
  moved = true
  idle()
}

function onReady(mapInstance) {
  mapObject = mapInstance

  if (typeof ResizeObserver !== 'undefined' && containerEl.value) {
    resizeObserver = new ResizeObserver(() => refreshSize())
    resizeObserver.observe(containerEl.value)
  }

  // disableClusteringAtZoom: the identical-location nudge (see
  // separateIdenticalLocations) is only ~tens of px at max zoom, well
  // inside the cluster radius - without this, co-located pins would render
  // as a "2" cluster bubble even at max zoom and never visibly split.
  clusterGroup = L.markerClusterGroup({
    maxClusterRadius: MAP_CLUSTER_RADIUS,
    disableClusteringAtZoom: props.maxZoom,
    iconCreateFunction: clusterIcon,
  })
  mapInstance.addLayer(clusterGroup)
  rebuildMarkers()

  try {
    const control = new Geocoder({
      placeholder: t('groups.search_place'),
      errorMessage: t('groups.search_nothing_found'),
      defaultMarkGeocode: false,
      geocoder: new geocoders.Photon({
        nameProperties: ['name', 'street', 'suburb', 'hamlet', 'town', 'city'],
        serviceUrl: 'https://photon.komoot.io/api/',
      }),
      collapsed: false,
    })

    control.on('markgeocode', (e) => {
      if (e?.geocode?.bbox) {
        // Empty the query box so the dropdown closes.
        control.setQuery('')
        mapInstance.flyToBounds(e.geocode.bbox)
      }
    })

    control.addTo(mapInstance)
  } catch (e) {
    // Matches the legacy component's own defensive catch here - usually
    // caused by Leaflet control DOM quirks, not worth failing the map over.
    console.error('Ignore leaflet geocoder exception', e)
  }

  idle()
}

watch(
  mappableGroups,
  (newVal, oldVal) => {
    rebuildMarkers()

    const hadGroups = oldVal && oldVal.length
    const hasGroups = newVal && newVal.length
    if (!hadGroups && hasGroups) {
      zoomToGroups()
    }
  },
  { immediate: true }
)

watch(
  () => props.hoveredId,
  (newId, oldId) => {
    if (!clusterGroup) return
    if (oldId != null && markersById.has(oldId)) {
      markersById.get(oldId).setIcon(iconFor(oldId))
    }
    if (newId != null && markersById.has(newId)) {
      markersById.get(newId).setIcon(iconFor(newId))
    }
  }
)

onBeforeUnmount(() => {
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
})
</script>

<template>
  <div ref="containerEl" class="group-map" data-testid="group-map">
    <LMap
      :min-zoom="minZoom"
      :max-zoom="maxZoom"
      :bounds="initialBounds || undefined"
      :options="mapOptions"
      use-global-leaflet
      style="width: 100%; height: 400px"
      @ready="onReady"
      @moveend="idle"
      @zoomend="idle"
      @dragend="onDragEnd"
    >
      <LTileLayer :url="LEAFLET_TILES" :attribution="LEAFLET_ATTRIBUTION" />
    </LMap>
  </div>
</template>

<style scoped lang="scss">
.group-map {
  width: 100%;
}

:deep(.leaflet-control-geocoder) {
  right: 30px;
}

// Belt and braces over the plugin CSS: never show the error element unless
// the control has flagged a failed search.
:deep(.leaflet-control-geocoder-form-no-error) {
  display: none;
}

:deep(.leaflet-control-geocoder-error) {
  display: block;
  padding: 0.375rem 1rem 0.5rem;
  font-size: 0.875rem;
  color: #6c757d;
}

// Restart-style pin (black border, white inner), matching the cluster
// bubbles - same markup and palette as PR 887's GroupMarker/_global.scss.
:deep(.group-pin) {
  background: transparent;
  border: none;
}

:deep(.group-pin svg path) {
  fill: #fff;
  stroke: #000;
  stroke-width: 2;
}

:deep(.group-pin svg circle) {
  fill: #000;
}

// Green for groups you follow, brand red (#F45B69) on hover - the same
// signals the old hue-rotated stock pin gave.
:deep(.group-pin--yours svg path) {
  fill: #21a453;
}

:deep(.group-pin--hover svg path) {
  fill: #f45b69;
}

// Cluster bubbles: bigger and more saturated for larger clusters. Sizes are
// set by clusterIcon's iconSize so Leaflet's positioning agrees with them;
// the tint ramps pale yellow -> amber -> brand orange (#F18F01) with the
// black border throughout.
:deep(.group-cluster) {
  border-radius: 50%;
  border: 2px solid #000;
  background-color: #fff;
  color: #000;
  text-align: center;
  font-weight: bold;
  cursor: pointer;
}

:deep(.group-cluster--small) {
  background-color: #fff396;
}

:deep(.group-cluster--medium) {
  background-color: #ffd16a;
}

:deep(.group-cluster--large) {
  background-color: #f18f01;
}

:deep(.group-cluster__count) {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  font-size: 16px;
  line-height: 1;
}
</style>
