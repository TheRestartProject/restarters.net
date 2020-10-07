<template>
  <div>
    <DeviceUsefulUrl v-for="url in urls" :key="'useful-' + url.url" :url="url" class="mb-2" />
    <DeviceUsefulUrl :url="null" class="mb-2" />
    // TODO Model for add/remove
    // TODO Validation
  </div>
</template>
<script>
import DeviceUsefulUrl from './DeviceUsefulUrl'
export default {
  components: {DeviceUsefulUrl},
  props: {
    urls: {
      type: Array,
      required: false,
      default: null
    }
  }
}
</script>