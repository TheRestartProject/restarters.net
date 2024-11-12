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
import {Geocoder, Photon} from 'leaflet-control-geocoder/dist/Control.Geocoder.js'
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
  setup(props) {
    const miscStore = useMiscStore()
    const groupStore = useGroupStore()
    const messageStore = useMessageStore()
    const isochroneStore = useIsochroneStore()

    return {
      miscStore,
      groupStore,
      messageStore,
      isochroneStore,
      Wkt,
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
              // TODO Set geocoder bounding box from initialBounds.
              nameProperties: [
                'name',
                'street',
                'suburb',
                'hamlet',
                'town',
                'city',
              ],
              serviceUrl: 'https://geocode.ilovefreegle.org/api' // TODO Probably not.,
            }),
            collapsed: false,
          })
              .on('markgeocode', async function (e) {
                if (e && e.geocode && e.geocode.bbox) {
                  // Empty out the query box so that the dropdown closes.  Note that "this" is the control object,
                  // which is why this isn't in a separate method.
                  console.log('Search for place', e)
                  this.moved = true
                  this.setQuery('')

                  // If we don't find anything at this location we will want to zoom out.
                  // TODO Make this work a la Freegle.
                  self.shownMany = false

                  // For some reason we need to take a copy of the latlng bounds in the event before passing it to
                  // flyToBounds.
                  const flyTo = e.geocode.bbox
                  const L = await import('leaflet/dist/leaflet-src.esm')
                  const newBounds = new L.LatLngBounds(
                      new L.LatLng(
                          flyTo.getSouthWest().lat,
                          flyTo.getSouthWest().lng
                      ),
                      new L.LatLng(
                          flyTo.getNorthEast().lat,
                          flyTo.getNorthEast().lng
                      )
                  )
                  // Move the map to the location we've found.
                  self.$refs.map.mapObject.flyToBounds(newBounds)
                  self.$emit('searched')
                }
              })
              .addTo(this.mapObject)
          console.log('Added geocoder')
        } catch (e) {
          // This is usually caused by leaflet.
          console.log('Ignore leaflet exception', e)
        }
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
    zoomToGroups() {
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

        this.bounds = bounds
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
