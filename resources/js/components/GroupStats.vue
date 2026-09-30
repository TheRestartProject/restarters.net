<template>
  <div>
    <div class="stats">
      <GroupStatsFacts :stats="stats" class="statsborder" />
      <div />
      <StatsImpact :stats="stats" statsEntity="group" class="statsborder" />
      <GroupDevicesWorkedOn :idgroups="idgroups" :stats="deviceStats" class="statsborder" />
      <div />
      <GroupDevicesMostRepaired :idgroups="idgroups" :devices="topDevices" class="statsborder" />
    </div>
    <p v-if="reportingUrl" class="mt-3 mb-0 text-right">
      <a :href="reportingUrl" target="_blank" rel="noopener" class="group-reporting-link">
        {{ __('groups.see_group_reports') }}
      </a>
    </p>
  </div>
</template>
<script>
import GroupStatsFacts from './GroupStatsFacts.vue'
import StatsImpact from './StatsImpact.vue'
import GroupDevicesWorkedOn from './GroupDevicesWorkedOn.vue'
import GroupDevicesMostRepaired from './GroupDevicesMostRepaired.vue'
import group from '../mixins/group'

export default {
  components: { GroupStatsFacts, StatsImpact, GroupDevicesWorkedOn, GroupDevicesMostRepaired },
  mixins: [ group ],
  props: {
    idgroups: {
      type: Number,
      required: true
    },
    stats: {
      required: true,
      type: Object
    },
    deviceStats: {
      required: true,
      type: Object
    },
    topDevices: {
      required: true,
      type: Array
    },
    reportingUrl: {
      // The group's reporting dashboard, if the viewer can see it.
      type: String,
      required: false,
      default: null
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.stats {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto 0px auto auto 0px auto;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr;
    grid-template-rows: auto auto;
  }
}

.statsborder {
  border-top: none;
  margin-top: 20px;

  @include media-breakpoint-up(md) {
    border-top: 1px solid $black;
  }
}
</style>
