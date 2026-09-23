<template>
  <div>
    <l-map
        class="map"
        ref="group-map"
        :zoom="16"
        :center="mapCenter"
        :style="'width: 100%; height: 200px'"
    >
      <l-tile-layer :url="tiles" :attribution="attribution" />
      <l-marker :lat-lng="marker" :draggable="true" @dragend="markerDragged" />
    </l-map>
    <small class="text-muted">{{ __('partials.dragmap') }}</small>
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
      // The marker is the group's location; the map can be panned and zoomed
      // independently without moving it.
      marker: [this.lat, this.lng],
      mapCenter: [this.lat, this.lng]
    }
  },
  watch: {
    lat() {
      this.relocate()
    },
    lng() {
      this.relocate()
    }
  },
  methods: {
    markerDragged(e) {
      const pos = e.target.getLatLng()
      this.marker = [pos.lat, pos.lng]
      this.$emit('update:lat', pos.lat)
      this.$emit('update:lng', pos.lng)
    },
    relocate() {
      // A genuinely new position (e.g. a fresh geocode from the location
      // field) moves the marker and the map; the echo of our own drag does not.
      if (this.lat !== null && this.lng !== null &&
          (Math.abs(this.lat - this.marker[0]) > EPSILON ||
           Math.abs(this.lng - this.marker[1]) > EPSILON)) {
        this.marker = [this.lat, this.lng]
        this.mapCenter = [this.lat, this.lng]
      }
    }
  }
}
</script>
