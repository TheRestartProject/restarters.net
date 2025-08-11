<template>
  <div class="stats">
    <GroupStatsFacts :stats="stats" class="statsborder" />
    <div />
    <StatsImpact :stats="stats" statsEntity="group" class="statsborder" />
  </div>
</template>
<script>
import GroupStatsFacts from './GroupStatsFacts.vue'
import StatsImpact from './StatsImpact.vue'
import group from '../mixins/group'

export default {
  components: { GroupStatsFacts, StatsImpact },
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
