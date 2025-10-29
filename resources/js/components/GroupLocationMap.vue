<template>
  <l-map
      class="map"
      ref="group-map"
      :zoom="11"
      :center="[lat, lng]"
      :style="'width: 100%; height: 200px'"
  >
    <l-tile-layer :url="tiles" :attribution="attribution" />
    <l-marker :lat-lng="[lat, lng]" :interactive="false" />
  </l-map>
</template>
<script>
import map from '../mixins/map'

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
  watch: {
    lat(newLat) {
      this.updateMapCenter()
    },
    lng(newLng) {
      this.updateMapCenter()
    }
  },
  methods: {
    updateMapCenter() {
      if (this.lat !== null && this.lng !== null && this.$refs['group-map'] && this.$refs['group-map'].mapObject) {
        this.$refs['group-map'].mapObject.setView([this.lat, this.lng], this.$refs['group-map'].mapObject.getZoom())
      }
    }
  },
  mounted() {
    // Ensure map is centered correctly on initial mount
    this.$nextTick(() => {
      this.updateMapCenter()
    })
  }
}
</script>