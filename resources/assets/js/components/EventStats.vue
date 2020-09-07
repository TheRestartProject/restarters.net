<template>
  <div class="stats">
    <EventStatsItems :stats="stats" class="statsborder" />
    <div />
    <EventStatsImpact :stats="stats" class="statsborder" />
  </div>
</template>
<script>
import EventStatsItems from './EventStatsItems'
import EventStatsImpact from './EventStatsImpact'

export default {
  components: {EventStatsImpact, EventStatsItems},
  props: {
    stats: {
      required: true,
      type: Object
    }
  }
}
// TODO Info button
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.stats {
  display: grid;
  grid-template-columns: auto 40px auto;
}

.statsborder {
  border-top: 1px solid $black;
}
</style>