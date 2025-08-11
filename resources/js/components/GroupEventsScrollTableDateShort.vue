<template>
  <div :class="{
          datecell: true,
          attending: attending
        }">
    <div class="datebox d-flex flex-column">
      <span class="day align-top">{{ dayofmonth }}</span>
      <span class="month">
        {{ month }}
      </span>
    </div>
  </div>
</template>
<script>
import event from '../mixins/event'

export default {
  mixins: [event],
  props: {
    idevents: {
      type: Number,
      required: true
    },
  },
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import 'bootstrap/scss/functions';
@import 'bootstrap/scss/variables';
@import 'bootstrap/scss/mixins/_breakpoints';

.datebox {
  text-align: center;
  padding-top: 8px;
  font-weight: bold;

  .day {
    font-size: 1.7rem;
    line-height: 1.7rem;
  }

  .month {
    line-height: 1rem;
  }
}
</style>