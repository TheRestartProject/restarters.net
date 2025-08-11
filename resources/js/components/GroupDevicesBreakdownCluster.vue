<template>
  <div>
    <div class="items-container pb-2">
      <StatsValue :count="stats.fixed" icon="fixed" size="md" :percent="pc(stats.fixed)" class="group-stat-fixed" :border="false" />
      <div />
      <StatsValue :count="stats.repairable" icon="repairable" size="md" :percent="pc(stats.repairable)" class="group-stat-repairable" :border="false" />
      <div />
      <StatsValue :count="stats.dead" icon="dead" size="md" :percent="pc(stats.dead)" class="group-stat-dead" :border="false" />
      <div />
      <div class="divider" />
      <div />
      <StatsValue :count="stats.most_seen.count" icon="most-seen_ico" size="md" :subtitle="translate(stats.most_seen.name)" class="group-stat-most-seen" :border="false" :translate="false" />
      <div />
      <StatsValue :count="stats.most_repaired.count" icon="most-repaired_ico" size="md" :subtitle="translate(stats.most_repaired.name)" class="group-stat-most-repaired" :border="false" :translate="false" />
      <div />
      <StatsValue :count="stats.least_repaired.count" icon="least-repaired_ico" size="md" :subtitle="translate(stats.least_repaired.name)" class="group-stat-least-repaired" :border="false" :translate="false" />
    </div>
  </div>
</template>
<script>
import StatsValue from './StatsValue.vue'
export default {
  components: {StatsValue},
  props: {
    stats: {
      type: Object,
      required: true
    }
  },
  computed: {
    total() {
      return this.stats.fixed + this.stats.repairable + this.stats.dead
    },
  },
  methods: {
    pc(val) {
      return this.total ? (Math.round(10000 * val / this.total) / 100) : 0
    },
    translate(category) {
      // Need to translate categories.  Might be null if there were no items.
      if (category === null) {
        return ''
      } else {
        return this.$lang.get('strings.' + category)
      }
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.items-container {
  display: grid;
  grid-template-columns: 1fr 20px 1fr 20px 1fr;
  grid-template-rows: 1fr 18px 1px 8px 1fr;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 20px 1fr 20px 1fr 5px 1px 5px 1fr 20px 1fr 20px 1fr;
    grid-template-rows: 1fr;
  }
}

.group-stat-fixed {
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 1;
  grid-column-end: 2;
}

.group-stat-repairable {
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 3;
  grid-column-end: 4;
}

.group-stat-dead {
  grid-row-start: 1;
  grid-row-end: 2;
  grid-column-start: 5;
  grid-column-end: 6;
}

.divider {
  border-bottom: 1px solid $black;
  grid-row-start: 2;
  grid-row-end: 3;
  grid-column-start: 1;
  grid-column-end: 6;

  @include media-breakpoint-up(md) {
    border-right: 1px solid $brand-light;
    border-bottom: 0;
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 7;
    grid-column-end: 8;
  }
}

.group-stat-most-seen {
  grid-row-start: 5;
  grid-row-end: 6;
  grid-column-start: 1;
  grid-column-end: 2;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 9;
    grid-column-end: 10;
  }
}

.group-stat-most-repaired {
  grid-row-start: 5;
  grid-row-end: 6;
  grid-column-start: 3;
  grid-column-end: 4;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 11;
    grid-column-end: 12;
  }
}

.group-stat-least-repaired {
  grid-row-start: 5;
  grid-row-end: 6;
  grid-column-start: 5;
  grid-column-end: 6;

  @include media-breakpoint-up(md) {
    grid-row-start: 1;
    grid-row-end: 2;
    grid-column-start: 13;
    grid-column-end: 15;
  }
}

</style>