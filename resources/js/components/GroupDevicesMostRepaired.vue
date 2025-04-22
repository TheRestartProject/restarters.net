<template>
  <div class="d-flex flex-column h-100">
    <h2 class="mt-2 mb-4">{{ __('groups.most_repaired_devices') }}</h2>
    <div class=" d-none d-md-block">
      <div class="podia">
        <GroupDeviceRepairPodium v-if="devices.length >= 2" :position="2" :device="devices[1]" />
        <div v-else />
        <div />
        <GroupDeviceRepairPodium v-if="devices.length >= 1" :position="1" :device="devices[0]" />
        <div v-else />
        <div />
        <GroupDeviceRepairPodium v-if="devices.length >= 3" :position="3" :device="devices[2]" />
        <div v-else />
      </div>
    </div>
    <div class="d-block d-md-none">
      <GroupDeviceRepairPodium v-if="devices.length >= 1" :position="1" :device="devices[0]" />
      <GroupDeviceRepairPodium v-if="devices.length >= 2" :position="2" :device="devices[1]" />
      <GroupDeviceRepairPodium v-if="devices.length >= 3" :position="3" :device="devices[2]" />
    </div>
  </div>
</template>
<script>
import GroupDeviceRepairPodium from './GroupDeviceRepairPodium.vue'
export default {
  components: {GroupDeviceRepairPodium},
  props: {
    idgroups: {
      type: Number,
      required: true
    },
    devices: {
      type: Array,
      required: true
    }
  },
}
</script>
<style scoped lang="scss">
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.podia {
  display: grid;
  flex-grow: 1;

  grid-template-columns: 1fr 0px;
  grid-template-rows: 1fr 1fr 1fr;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr 20px 1fr;
    grid-template-rows: 1fr;
  }
}
</style>