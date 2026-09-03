<template>
  <div>
    <div class="mb-1">
      {{ __('partials.dragmap') }}
    </div>
    <l-map
        class="map"
        ref="group-map"
        :zoom="11"
        :center="center"
        :style="'width: 100%; height: 200px'"
        @update:center="centerUpdated"
    >
      <l-tile-layer :url="tiles" :attribution="attribution" />
      <l-marker :lat-lng="center" :interactive="false" />
    </l-map>
  </div>
</template>
<script>
import map from '../mixins/map'

// Below this difference two coordinates are "the same place" — stops the
// parent syncing our own drag emission back down and re-centring the map.
const EPSILON = 1e-7

export default {
  mixins: [ map ],
  props: {
    lat: {
      type: Number,
      required: false,
      default: null
    },
    lng: {
      type: Number,
      required: false,
      default: null
    },
  },
  data () {
    return {
      center: [this.lat, this.lng]
    }
  },
  watch: {
    lat() {
      this.recentre()
    },
    lng() {
      this.recentre()
    }
  },
  methods: {
    centerUpdated(newCenter) {
      // Fired by Leaflet when a drag finishes. The marker is pinned to the
      // centre, so tell the parent where the pin now is.
      this.center = [newCenter.lat, newCenter.lng]
      this.$emit('update:lat', newCenter.lat)
      this.$emit('update:lng', newCenter.lng)
    },
    recentre() {
      // A genuinely new position (e.g. a fresh geocode from the location
      // field) moves the map; the echo of our own drag does not.
      if (this.lat !== null && this.lng !== null &&
          (Math.abs(this.lat - this.center[0]) > EPSILON ||
           Math.abs(this.lng - this.center[1]) > EPSILON)) {
        this.center = [this.lat, this.lng]
      }
    }
  }
}
</script>
