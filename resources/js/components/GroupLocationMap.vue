<template>
  <div>
    {{ __('partials.dragmap') }}
    <div ref="mapcontainer" :style="'width: 100%; height: ' + mapHeight + 'px'">
      <DraggableMap
          v-if="showMap && (lat || lng)"
          :initial-lat="lat"
          :initial-lng="lng"
          :initial-zoom="11"
          @lat-changed="$emit('update:lat', $event)"
          @lng-changed="$emit('update:lng', $event)"
      />
    </div>
  </div>
</template>
<script>
import DraggableMap from './DraggableMap'

export default {
  components: {DraggableMap},
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
      mapHeight: null,
      showMap: false,
    }
  },
  mounted () {
    this.mapHeight = this.$refs.mapcontainer.clientWidth
    this.$nextTick(() => {
      this.showMap = true
    })
  }
}
</script>
