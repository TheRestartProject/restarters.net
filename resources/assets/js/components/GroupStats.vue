<template>
  <div class="stats">
    <GroupStatsFacts :stats="stats" class="statsborder" />
    <div />
    <EventStatsImpact :stats="stats" class="statsborder" />
  </div>
</template>
<script>
import GroupStatsFacts from './GroupStatsFacts'
import EventStatsImpact from './EventStatsImpact'

export default {
  components: {EventStatsImpact, GroupStatsFacts},
  props: {
    stats: {
      required: true,
      type: Object
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.stats {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto 0px auto;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr;
    grid-template-rows: 1fr;
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
