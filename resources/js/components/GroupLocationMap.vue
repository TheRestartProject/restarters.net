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
}
</script>