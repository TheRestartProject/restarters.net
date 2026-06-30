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
      <GroupMarker :key='"marker-" + group.id' v-for="group in allGroups" :id="group.id" :highlight="yourGroup(group.id)" :hover="group.id === hover" />
      <l-tile-layer :url="tiles" :attribution="attribution" />
    </l-map>
  </div>
</template>
<script>
import map from '../mixins/map'
import { Geocoder } from 'leaflet-control-geocoder/src/control'
import { Photon } from 'leaflet-control-geocoder/src/geocoders/photon'
import GroupMarker from './GroupMarker.vue'

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
    hover: {
      type: Number,
      required: false,
      default: null,
    }
  },
  data() {
    return {
      moved: false,
      mapObject: null,
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
        dragging: !!window?.L?.Browser?.mobile,
        touchZoom: true,
        scrollWheelZoom: false,
        bounceAtZoomLimits: true,
        gestureHandling: true,
      }
    },
    allGroups() {
      const groups = this.$store.getters['groups/list']
      return groups.filter((g) => {
        if (!this.network) {
          return true
        }

        let found = false
        g.networks.forEach((n) => {
          if (n.id === this.network) {
            found = true
          }
        })

        return found
      })
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
          new Geocoder({
            placeholder: 'Search for a place...',
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
        } catch (e) {
          // This is usually caused by leaflet.
          console.log('Ignore leaflet exception', e)
        }
      }

      this.idle()
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
        if (this.hasLocation) {
          // The user has a location, so the map is already centred on their area.
          // Frame the 5 groups closest to the centre.
          const center = this.mapObject.getCenter()
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
