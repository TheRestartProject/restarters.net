<template>
  <div>
    <div v-if="device">
      <DeviceUsefulUrl v-for="url in device.urls" :key="'useful-' + url.url" :url="url" class="mb-2" />
<!--      TODO updates-->
    </div>
    <DeviceUsefulUrl :url="null" class="mb-2" />
  </div>
</template>
<script>
import DeviceUsefulUrl from './DeviceUsefulUrl'
export default {
  components: {DeviceUsefulUrl},
  props: {
    device: {
      type: Object,
      required: false,
      default: null
    }
  }
}
</script>