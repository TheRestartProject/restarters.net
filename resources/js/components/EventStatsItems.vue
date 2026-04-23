<template>
  <div>
    <h2 class="mt-2 mb-2">{{ __('events.items_fixed') }}</h2>
    <div class="items-container">
      <StatsValue :count="stats.fixed_devices" icon="fixed" size="sm" variant="primary" class="event-stat-fixed" />
      <div />
      <StatsValue :count="stats.fixed_powered" icon="powered" size="md" title="devices.powered_items" class="event-stat-powered" />
      <div />
      <StatsValue :count="stats.fixed_unpowered" icon="unpowered" size="md" title="devices.unpowered_items" class="event-stat-unpowered" />
    </div>
  </div>
</template>
<script>
import StatsValue from './StatsValue.vue'

export default {
  components: {StatsValue},
  props: {
    stats: {
      required: true,
      type: Object
    }
  },
}
</script>
<style scoped lang="scss">
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.items-container {
  display: grid;
  grid-template-columns: 1fr 20px 1fr;
  grid-template-rows: 80px 180px;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 2fr 20px 2fr;
    grid-template-rows: 1fr
  }
}

.event-stat-fixed {
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 1;
  grid-column-end: 4;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 1;
    grid-column-end: 2;
  }
}

.event-stat-powered {
  grid-row-start: 2;
  grid-row-end: 3;
  grid-column-start: 1;
  grid-column-end: 2;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 3;
    grid-column-end: 4;
  }
}

.event-stat-unpowered {
  grid-row-start: 2;
  grid-row-end: 3;
  grid-column-start: 3;
  grid-column-end: 4;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 5;
    grid-column-end: 6;
  }
}
</style>
