<template>
  <div>
    <div :class="['stats', 'stats-' + arrangement]">
      <GroupStatsFacts v-if="showHeadline" :stats="stats" class="statsborder stats-facts" />
      <StatsImpact v-if="showHeadline" :stats="stats" statsEntity="group" :show-not-included="!compact" class="statsborder stats-impact" />
      <GroupDevicesWorkedOn v-if="showItems" :idgroups="idgroups" :stats="deviceStats" class="statsborder stats-worked-on" />
      <GroupDevicesMostRepaired v-if="showItems" :idgroups="idgroups" :devices="topDevices" class="statsborder stats-most-repaired" />
    </div>
    <p v-if="reportingUrl && showItems" class="mt-3 mb-0 text-right">
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
    part: {
      // 'all', or just the 'headline' figures (achievements and impact), or just the 'items' (worked on and most
      // repaired) - for a page that puts the two in different places.
      type: String,
      required: false,
      default: 'all'
    },
    compact: {
      // Achievements above impact on the left, items worked on above most repaired on the right.
      type: Boolean,
      required: false,
      default: false
    },
  },
  computed: {
    showHeadline() {
      return this.part !== 'items'
    },
    showItems() {
      return this.part !== 'headline'
    },
    arrangement() {
      if (this.part !== 'all') {
        return 'single'
      }

      return this.compact ? 'compact' : 'rows'
    }
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

  @include media-breakpoint-up(md) {
    grid-column-gap: 20px;
    grid-template-columns: 1fr 1fr;
  }
}

@include media-breakpoint-up(md) {
  .stats-rows {
    // Achievements and impact, then items worked on and most repaired.
    grid-template-areas: "facts impact" "worked-on most-repaired";
  }

  .stats-compact {
    grid-template-areas: "facts worked-on" "impact most-repaired";
  }

  .stats-single {
    grid-template-areas: "facts impact" "worked-on most-repaired";
    grid-template-rows: auto;
  }

  .stats-facts { grid-area: facts; }
  .stats-impact { grid-area: impact; }
  .stats-worked-on { grid-area: worked-on; }
  .stats-most-repaired { grid-area: most-repaired; }
}

.statsborder {
  border-top: none;
  margin-top: 20px;

  @include media-breakpoint-up(md) {
    border-top: 1px solid $black;
  }
}
</style>
