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
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

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

.attending {
  background-color: $brand-grey;

  &.datecell {
    padding-top: 9px;
    padding-bottom: 9px;
    padding-left: 9px;
    padding-right: 9px;
    text-align: center;
    background-color: $black;
    width: 76px !important;

    .datebox {
      background-color: $black;
      color: $white;
    }
  }
}
</style>