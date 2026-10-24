<template>
  <b-button-group :aria-label="__('networks.stats.period_toggle')" class="period-toggle">
    <b-button class="period-all" size="sm" variant="primary" :pressed="value === 'all'" @click="$emit('input', 'all')">
      {{ __('networks.stats.all_time') }}
    </b-button>
    <b-button class="period-last12" size="sm" variant="primary" :pressed="value === 'last_12_months'" @click="$emit('input', 'last_12_months')">
      {{ __('networks.stats.last_12_months') }}
    </b-button>
  </b-button-group>
</template>

<script>
// "All time" / "Last 12 months" switch for headline stats.  Used with v-model; the value is the `period` name that
// the stats APIs take.  Last 12 months means the last 12 completed calendar months.
export default {
  props: {
    value: {
      type: String,
      required: false,
      default: 'all'
    }
  }
}
</script>

<style scoped lang="scss">
// Same look as the target toggle in StatsShareModal: the theme's .btn-primary.active is white on white.
::v-deep .btn {
  color: black !important;
  background-color: white !important;

  &.active {
    color: white !important;
    background-color: black !important;
  }
}
</style>
