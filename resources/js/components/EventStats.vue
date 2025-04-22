<template>
  <div class="stats">
    <EventStatsItems :stats="stats" class="statsborder" />
    <div />
    <StatsImpact :stats="stats" statsEntity="event" class="statsborder" />
  </div>
</template>
<script>
import EventStatsItems from './EventStatsItems.vue'
import StatsImpact from './StatsImpact.vue'

export default {
  components: {StatsImpact, EventStatsItems},
  props: {
    idevents: {
      type: Number,
      required: true
    }
  },
  computed: {
    stats() {
      return this.$store.getters['events/getStats'](this.idevents)
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