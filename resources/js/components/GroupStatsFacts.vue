<template>
  <div>
    <h2 class="mt-2 mb-2">{{ __('groups.group_facts') }}</h2>
    <div class="items-container">
      <StatsValue :count="stats.parties" icon="coffee" size="md" variant="primary" title="groups.events" class="group-stat-events" />
      <div />
      <StatsValue :count="stats.participants" icon="participants" size="md" title="groups.participants" class="group-stat-participants" />
      <div />
      <StatsValue :count="stats.hours_volunteered" icon="clock" size="md" title="groups.hours_volunteered" class="group-stat-hours-volunteered" />
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
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.items-container {
  display: grid;
  grid-template-columns: 1fr 20px 1fr;
  grid-template-rows: 80px 180px;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr 20px 1fr;
    grid-template-rows: 1fr
  }
}

.group-stat-events {
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

.group-stat-participants {
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

.group-stat-hours-volunteered {
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
