<template>
  <div>
    <h2 class="mt-2 mb-2">{{ __('groups.total_devices') }}</h2>
    <div class="items-container text-lowercase">
      <StatsValue :count="stats.fixed + stats.repairable + stats.dead" icon="drill" :icon-width="50" size="md" variant="brand" title="partials.total" class="group-stat-total height" />
      <div />
      <StatsValue :count="stats.fixed" icon="fixed" size="md" title="partials.fixed" class="group-stat-fixed height" />
      <div />
      <StatsValue :count="stats.repairable" icon="repairable" size="md" title="partials.repairable" class="group-stat-repairable height" />
      <div />
      <StatsValue :count="stats.dead" icon="dead" size="md" title="partials.end_of_life" class="group-stat-dead height" />
    </div>
  </div>
</template>
<script>
import StatsValue from './StatsValue.vue'

export default {
  components: {StatsValue},
  props: {
    idgroups: {
      type: Number,
      required: true
    },
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
  grid-template-rows: 180px 180px;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr 20px 1fr 20px 1fr;
    grid-template-rows: 1fr
  }
}

.group-stat-total {
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 1;
  grid-column-end: 2;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 1;
    grid-column-end: 2;
  }
}

.group-stat-fixed {
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 3;
  grid-column-end: 4;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 3;
    grid-column-end: 4;
  }
}

.group-stat-repairable {
  grid-row-start: 2;
  grid-row-end: 3;
  grid-column-start: 1;
  grid-column-end: 2;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 5;
    grid-column-end: 6;
  }
}

.group-stat-dead {
  grid-row-start: 2;
  grid-row-end: 3;
  grid-column-start: 3;
  grid-column-end: 4;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 7;
    grid-column-end: 8;
  }
}

.height {
  height: 152px;
}
</style>
