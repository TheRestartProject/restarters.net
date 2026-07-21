<template>
  <div>
    <l-map
        ref="map"
        :min-zoom="minZoom"
        :max-zoom="maxZoom"
        :bounds.sync="bounds"
        :style="'width: 100%; height: 400px'"
        :options="mapOptions"
        @ready="ready"
        @update:bounds="idle"
        @zoomend="idle"
        @moveend="idle"
        @dragend="dragEnd"
    >
      <template v-for="entry in clusters">
        <l-marker
            v-if="entry.properties.cluster"
            :key="'cluster-' + entry.id"
            :lat-lng="[entry.geometry.coordinates[1], entry.geometry.coordinates[0]]"
            :icon="clusterIcon(entry)"
            @click="clusterClick(entry)"
        />
        <GroupMarker
            v-else
            :key="'marker-' + entry.properties.groupId"
            :id="entry.properties.groupId"
            :highlight="yourGroup(entry.properties.groupId)"
            :hover="entry.properties.groupId === hover"
            @update:hover="$emit('update:hover', $event)"
        />
      </template>
      <l-tile-layer :url="tiles" :attribution="attribution" />
    </l-map>
  </div>
</template>
<script>
import map from '../mixins/map'
import { Geocoder } from 'leaflet-control-geocoder/src/control'
import { Photon } from 'leaflet-control-geocoder/src/geocoders/photon'
// The control builds its "nothing found" element at init; this stylesheet is
// what keeps it hidden until a search actually fails (and styles the button).
import 'leaflet-control-geocoder/dist/Control.Geocoder.css'
import GroupMarker from './GroupMarker.vue'
// The prebuilt bundle rather than the package root: the root resolves to the
// ESM source in the browser build but to the UMD bundle under Jest, so tests
// would exercise different code from production. Freegle imports it this way
// for the same reason.
import Supercluster from 'supercluster/dist/supercluster'

export default {
  components: {
    GroupMarker,
  },
  mixins: [map],
  props: {
    initialBounds: {
      type: Array,
      required: true,
    },
    minZoom: {
      type: Number,
      required: false,
      default: 5,
    },
    maxZoom: {
      type: Number,
      required: false,
      default: 15,
    },
    network: {
      type: Number,
      required: false,
      default: null,
    },
    yourGroups: {
      type: Array,
      required: false,
      default: () => [],
    },
    // The groups the map should draw. Null means "everything we know about";
    // the filter bar narrows it, so searching moves the map too.
    groupids: {
      type: Array,
      required: false,
      default: null,
    },
    hover: {
      type: Number,
      required: false,
      default: null,
    },
    yourArea: {
      type: String,
      required: false,
      default: '',
    },
    yourLat: {
      type: Number,
      required: false,
      default: null,
    },
    yourLng: {
      type: Number,
      required: false,
      default: null,
    },
    // Bumped by the parent when the user changes a filter, to ask the map to
    // frame what it is now showing. Watching the group list instead would move
    // the map every time rows are hydrated, yanking it away from wherever the
    // user had panned to.
    frameRequest: {
      type: Number,
      required: false,
      default: 0,
    },
    // Below this many groups, draw them all rather than clustering. Supercluster
    // can quietly return fewer points than it was given, which doesn't matter
    // among thousands but is obvious when there are only a handful.
    minCluster: {
      type: Number,
      required: false,
      default: 10,
    }
  },
  data() {
    return {
      moved: false,
      mapObject: null,
      geocoder: null,
      zoom: this.minZoom,
      destroyed: false,
      mapIdle: 0,
      center: null,
      bounds: null,
      zoomedToGroups: false
    }
  },
  computed: {
    mapOptions() {
      return {
        zoomControl: true,
        dragging: true,
        touchZoom: true,
        scrollWheelZoom: false,
        bounceAtZoomLimits: true,
      }
    },
    allGroups() {
      let groups = this.$store.getters['groups/list']

      if (this.groupids !== null) {
        groups = groups.filter((g) => this.groupids.includes(g.id || g.idgroups))
      }

      if (!this.network) {
        return groups
      }

      // Networks may be summary objects ([{id}]) or plain ids (the names index).
      return groups.filter((g) => (g.networks || []).some((n) => (n && n.id !== undefined ? n.id : n) === this.network))
    },
    mappableGroups() {
      // A group with no geocode would put a marker at null island (0,0) —
      // L.marker coerces null coordinates to 0. Leave those off the map.
      return this.allGroups.filter((g) => {
        const lat = g.location && g.location.lat != null ? g.location.lat : g.lat
        const lng = g.location && g.location.lng != null ? g.location.lng : g.lng
        return lat != null && lng != null && !isNaN(+lat) && !isNaN(+lng)
      })
    },
    clusterPoints() {
      return this.mappableGroups.map((g) => ({
        type: 'Feature',
        id: g.id,
        properties: { groupId: g.id, cluster: false },
        geometry: {
          type: 'Point',
          coordinates: [
            +(g.location && g.location.lng != null ? g.location.lng : g.lng),
            +(g.location && g.location.lat != null ? g.location.lat : g.lat),
          ],
        },
      }))
    },
    clusterIndex() {
      // The index is immutable, so it has to be rebuilt whenever the points
      // change rather than updated in place.
      const index = new Supercluster({
        radius: 60,
        maxZoom: this.maxZoom,
        minZoom: this.minZoom,
      })

      index.load(this.clusterPoints)

      return index
    },
    clusters() {
      // Which clusters exist depends on the zoom and what's in view, but Leaflet
      // reports both imperatively, so reading mapIdle here is what makes this
      // recompute as the map moves.
      this.mapIdle

      if (!this.mapObject || !this.clusterPoints.length) {
        return []
      }

      if (this.clusterPoints.length < this.minCluster) {
        return this.clusterPoints
      }

      try {
        const bounds = this.mapObject.getBounds()

        if (!bounds) {
          return this.clusterPoints
        }

        return this.clusterIndex.getClusters([
          bounds.getWest(),
          bounds.getSouth(),
          bounds.getEast(),
          bounds.getNorth(),
        ], Math.round(this.mapObject.getZoom()))
      } catch (e) {
        // Map state races (no bounds mid-transition) shouldn't lose the markers.
        console.error('Error clustering groups', e)
        return this.clusterPoints
      }
    },
    hasLocation() {
      // The groups page sends the inverted world box [[90,180],[-90,-180]] when
      // the user has no location set; a real bounding box always has
      // min_lat <= max_lat. Without a location there's no meaningful "centre" to
      // find the nearest groups around, so we frame all groups instead.
      const b = this.initialBounds
      if (!Array.isArray(b) || b.length < 2 || !Array.isArray(b[0]) || !Array.isArray(b[1])) {
        return false
      }
      return +b[0][0] <= +b[1][0]
    },
    hasUserPoint() {
      // A location of the user's own, as opposed to a box round their country.
      // Only this justifies zooming in to the groups nearest them.
      return this.yourLat !== null && this.yourLng !== null &&
          !isNaN(+this.yourLat) && !isNaN(+this.yourLng)
    },
  },
  created() {
    this.bounds = this.initialBounds
  },
  mounted() {
    // The map may be created inside a hidden tab, where its container is 0x0.
    // When the tab becomes visible the container resizes; watch for that and
    // tell Leaflet to re-measure, otherwise tiles never fill the now-visible
    // area and most of the map shows as grey.
    if (typeof ResizeObserver !== 'undefined') {
      this.resizeObserver = new ResizeObserver(() => this.refreshSize())
      this.resizeObserver.observe(this.$el)
    }
  },
  beforeDestroy() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect()
      this.resizeObserver = null
    }
  },
  beforeUnmount() {
    this.destroyed = true
  },
  watch: {
    frameRequest() {
      this.frameShownGroups()
    },
    allGroups: {
      handler(newVal, oldVal) {
        // oldVal is undefined on the first (immediate) run.
        const hadGroups = oldVal && oldVal.length
        const hasGroups = newVal && newVal.length
        if (!hadGroups && hasGroups) {
          this.zoomToGroups()
        }
      },
      deep: true,
      immediate: true,
    }
  },
  methods: {
    refreshSize() {
      // The ResizeObserver can fire before @ready has set mapObject, so resolve
      // it from the l-map ref if needed.
      if (!this.mapObject && this.$refs.map) {
        this.mapObject = this.$refs.map.mapObject
      }
      if (!this.mapObject) {
        return
      }
      // Leaflet caches the container size; without invalidateSize() it still
      // thinks it's 0x0 (created in a hidden tab) and tiles don't fill the
      // visible area (grey map). Re-measure first so getSize() is correct.
      this.mapObject.invalidateSize()
      // If we couldn't frame the groups earlier (map was 0x0, so zoomToGroups
      // skipped or centred on null island), do it now that we have a real size,
      // unless the user has since moved the map.
      if (!this.moved) {
        this.zoomedToGroups = false
        this.zoomToGroups()
      }
      this.idle()
    },
    async ready() {
      const self = this

      this.$emit('update:ready', true)
      this.mapObject = this.$refs.map.mapObject

      if (this.mapObject) {
        try {
          this.geocoder = new Geocoder({
            placeholder: this.__('groups.search_place'),
            errorMessage: this.__('groups.search_nothing_found'),
            defaultMarkGeocode: false,
            geocoder: new Photon({
              nameProperties: [
                'name',
                'street',
                'suburb',
                'hamlet',
                'town',
                'city',
              ],
              serviceUrl: 'https://photon.komoot.io/api'
            }),
            collapsed: false,
          })
              .on('markgeocode', async function (e) {
                if (e && e.geocode && e.geocode.bbox) {
                  // Empty out the query box so that the dropdown closes.  Note that "this" is the control object,
                  // which is why this isn't in a separate method.
                  this.moved = true
                  this.setQuery('')

                  self.mapObject.flyToBounds(e.geocode.bbox)

                  self.$emit('searched')
                }
              })
              .addTo(this.mapObject)

          this.presetSearch()
        } catch (e) {
          // This is usually caused by leaflet.
          console.log('Ignore leaflet exception', e)
        }
      }

      this.idle()
    },
    presetSearch() {
      // We've already centred the map on the user's area, so show that area in
      // the search box too - a hint that the map has been searched for them,
      // rather than an empty box that looks like nothing has happened.
      if (this.geocoder && this.yourArea) {
        this.geocoder.setQuery(this.yourArea)
      }
    },
    idle() {
      this.mapObject = this.$refs.map.mapObject
      this.mapIdle++
      this.zoomToGroups()

      try {
        if (this.mapObject) {
          const bounds = this.mapObject.getBounds()
          this.bounds = bounds
          let groupsInBounds = []

          if (this.bounds) {
            groupsInBounds = this.allGroups.filter(function (group) {
              // We might either have the group names format (lat/lng at the top level) or the group summary format
              // (lat/lng in location).
              if (group.location) {
                return (group.location.lat || group.location.lng) &&
                    bounds.contains(new L.LatLng(group.location.lat, group.location.lng))
              } else {
                return (group.lat || group.lng) &&
                    bounds.contains(new L.LatLng(group.lat, group.lng))
              }
            })
          }

          this.$emit(
              'groups',
              groupsInBounds.map((g) => g.id)
          )

          this.$emit('update:bounds', this.mapObject.getBounds())
          this.$emit('update:zoom', this.mapObject.getZoom())
          this.$emit('update:centre', this.mapObject.getCenter())
        }
      } catch (e) {
        console.error('Error in map idle', e)
      }
    },
    toJSON(bounds) {
      return [
        [bounds.getSouthWest().lat, bounds.getSouthWest().lng],
        [bounds.getNorthEast().lat, bounds.getNorthEast().lng],
      ]
    },
    dragEnd(e) {
      this.moved = true
      this.$emit('update:moved', true)
      this.idle()
    },
    clusterIcon(cluster) {
      const count = cluster.properties.point_count
      const wide = count >= 1000 ? ' group-cluster__count--wide' : ''

      return L.divIcon({
        html: '<div class="group-cluster__count' + wide + '">' + count + '</div>',
        // Replaces Leaflet's .leaflet-div-icon, which would otherwise draw a
        // white box with a grey border behind the circle.
        className: 'group-cluster',
        iconSize: [46, 46],
        iconAnchor: [23, 23],
      })
    },
    clusterClick(cluster) {
      // Zoom to where this cluster breaks apart, so a click always reveals
      // something rather than appearing to do nothing.
      const zoom = Math.min(
          this.clusterIndex.getClusterExpansionZoom(cluster.properties.cluster_id),
          this.maxZoom
      )

      this.moved = true
      this.mapObject.flyTo(
          [cluster.geometry.coordinates[1], cluster.geometry.coordinates[0]],
          zoom
      )
    },
    frameShownGroups() {
      // A filter is an explicit request to see something, so take the map there
      // even if the user has panned somewhere else.
      const bounds = this.boundsOf(this.mappableGroups)

      if (this.mapObject && bounds) {
        this.bounds = bounds
        this.mapObject.fitBounds(bounds)
      }
    },
    boundsOf(groups) {
      const bounds = new L.LatLngBounds()

      groups.forEach((group) => {
        const lat = +(group.location && group.location.lat != null ? group.location.lat : group.lat)
        const lng = +(group.location && group.location.lng != null ? group.location.lng : group.lng)

        if (!isNaN(lat) && !isNaN(lng)) {
          bounds.extend(new L.LatLng(lat, lng))
        }
      })

      return bounds.isValid() ? bounds.pad(0.1) : null
    },
    zoomToGroups() {
      try {
        // Only zoom once the map has a real size. If it's still 0x0 (created in a
        // hidden/off-screen tab, or mid tab-transition) getCenter() is (0,0) and
        // we'd fly to null island, leaving a grey map. Skipping here (without
        // setting zoomedToGroups) means refreshSize()/idle() will retry once the
        // container becomes visible.
        const mapSized = this.mapObject && this.mapObject.getSize().x > 0
        if (this.zoomedToGroups || !mapSized || !this.allGroups.length) {
          return
        }

        this.zoomedToGroups = true

        const latOf = (g) => +(g.location && g.location.lat != null ? g.location.lat : g.lat)
        const lngOf = (g) => +(g.location && g.location.lng != null ? g.location.lng : g.lng)

        let framed
        if (!this.hasUserPoint && this.hasLocation) {
          // A box round the user's country: show the country as we were given it.
          // Zooming to the groups nearest its centre would land somewhere
          // arbitrary in the middle of the country, near nobody in particular.
          this.bounds = this.initialBounds
          this.mapObject.fitBounds(this.initialBounds)
          return
        }

        if (this.hasUserPoint) {
          // Frame the 5 groups closest to the user themselves.  Using the centre
          // of the bounding box instead would drift away from where they are.
          const center = new L.LatLng(+this.yourLat, +this.yourLng)
          framed = this.allGroups
              .map((group) => {
                const distance = Math.sqrt((latOf(group) - center.lat) ** 2 + (lngOf(group) - center.lng) ** 2)
                return { group, distance }
              })
              .sort((a, b) => a.distance - b.distance)
              .slice(0, 5)
              .map((a) => a.group)
        } else {
          // No location to centre on: frame all the groups instead, so the map
          // shows them rather than defaulting to the whole world / null island.
          framed = this.allGroups
        }

        // Get the bounding box containing the framed groups.
        const bounds = new L.LatLngBounds()
        framed.forEach((group) => {
          const lat = latOf(group)
          const lng = lngOf(group)
          if (!isNaN(lat) && !isNaN(lng)) {
            bounds.extend(new L.LatLng(lat, lng))
          }
        })

        if (!bounds.isValid()) {
          return
        }

        this.bounds = bounds.pad(0.1)
        // Use fitBounds rather than flyToBounds for the initial framing: the
        // animated fly fights the :bounds.sync binding and can leave the map
        // mid-animation so tiles for the final view never settle (grey map).
        this.mapObject.fitBounds(this.bounds)
      } catch (e) {
        console.error('Zoom to groups error', e)
      }
    },
    yourGroup(id) {
      return this.yourGroups.includes(id)
    }
  },
}
</script>
<style scoped lang="scss">
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.mapbox {
  width: 100%;
  top: 0px;
  left: 0;
  border: 1px solid grey;
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

@media screen and (max-width: 360px) {
  :deep(.leaflet-control-geocoder-form input) {
    max-width: 200px;
  }
}

@include media-breakpoint-up(md) {
  :deep(.leaflet-control-geocoder-form input) {
    height: calc(1.25em + 1rem + 2px);
    padding: 0.5rem 1rem;
    font-size: 1rem !important;
    line-height: 1.25;
    border-radius: 0.3rem;
  }
}

:deep(.top) {
  z-index: 1000 !important;
}

</style>
