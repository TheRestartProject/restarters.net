<template>
  <l-map
      ref="map"
      :zoom="14"
      :center.sync="center"
  >
    <l-tile-layer :url="tiles" :attribution="attribution" />
    <l-marker :lat-lng="center" :interactive="false" />
  </l-map>
</template>
<script>
import map from '../mixins/map.js'
import L from 'leaflet'

export default {
  components: {},
  mixins: [map],
  props: {
    initialZoom: {
      type: Number,
      required: false,
      default: 5
    },
    initialLat: {
      type: Number,
      required: true,
    },
    initialLng: {
      type: Number,
      required: true,
    },
  },
  data: function() {
    return {
      mapObject: null,
      center: null,
    }
  },
  watch: {
    center: function(newVal) {
      // This only gets updated when the drag has finished.  But even if we listen on the drag event and update
      // the center position, leaflet still doesn't re-render the center marker until the drag is complete.  So
      // the center marker will move out of position and then snap back - which isn't ideal, but we can live with it.
      this.$emit('lat-changed', newVal.lat)
      this.$emit('lng-changed', newVal.lng)
    }
  },
  created() {
    this.zoom = this.initialZoom
    this.lat = this.initialLat
    this.lng = this.initialLng
    this.center = new L.LatLng(this.lat, this.lng)
  },
}
</script>
