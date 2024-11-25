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
      zoom: props.minZoom,
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
        dragging: process.client && window?.L?.Browser?.mobile,
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
  },
  created() {
    this.bounds = this.initialBounds
  },
  beforeUnmount() {
    this.destroyed = true
  },
  watch: {
    allGroups: {
      handler(newVal, oldVal) {
        if (!oldVal.length && newVal.length) {
          this.zoomToGroups()
        }
      },
      deep: true,
      immediate: true,
    }
  },
  methods: {
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
        if (!this.zoomedToGroups && this.mapObject && this.allGroups.length) {
          const center = this.mapObject.getCenter()

          this.zoomedToGroups = true

          // Find the smallest box which contains 5 groups around the center.
          const groups = this.allGroups

          // Find the 5 closest groups.
          const closest = groups
              .map((group) => {
                const lat = group.location.lat || group.lat
                const lng = group.location.lng || group.lng
                const distance = Math.sqrt((lat - center.lat) ** 2 + (lng - center.lng) ** 2)
                return { group, distance }
              })
              .sort((a, b) => a.distance - b.distance)
              .slice(0, 5)
              .map((a) => a.group)

          // Get the bounding box containing these groups.
          const bounds = new L.LatLngBounds()
          closest.forEach((group) => {
            const lat = group.location.lat || group.lat
            const lng = group.location.lng || group.lng
            bounds.extend(new L.LatLng(lat, lng))
          })

          this.bounds = bounds.pad(0.1)
          this.mapObject.flyToBounds(this.bounds)
        }
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
