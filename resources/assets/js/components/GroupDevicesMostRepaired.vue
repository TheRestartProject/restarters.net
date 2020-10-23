<template>
  <div class="d-flex flex-column h-100">
    <h2 class="mt-2 mb-4">{{ translatedMostRepairedDevices }}</h2>
    <div class="podia">
      <GroupDeviceRepairPodium v-if="devices.length >= 2" :position="2" :device="devices[1]" />
      <div />
      <GroupDeviceRepairPodium v-if="devices.length >= 1" :position="1" :device="devices[0]" />
      <div />
      <GroupDeviceRepairPodium v-if="devices.length >= 3" :position="3" :device="devices[2]" />
    </div>
  </div>
</template>
<script>
import GroupDeviceRepairPodium from './GroupDeviceRepairPodium'
export default {
  components: {GroupDeviceRepairPodium},
  props: {
    devices: {
      type: Array,
      required: true
    }
  },
  computed: {
    translatedMostRepairedDevices() {
      return this.$lang.get('groups.most_repaired_devices')
    }
  }
}
</script>
<style scoped lang="scss">
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.podia {
  display: grid;
  grid-template-columns: 1fr 20px 1fr 20px 1fr;
  flex-grow: 1;
}
</style>