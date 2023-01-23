<template>
  <div :class="{
        date: true,
        'text-left': true,
        'pl-3': true,
        'w-100': true,
        attending: attending
      }">
    {{ date }}
    <br class="hidecell"/>
    {{ start }} <span class="d-none d-md-inline">- {{ end }}</span>
    <br class="d-block d-md-none"/>
    <div class="text-muted small">
      <b-img class="icon" src="/images/clock.svg" /> {{ timezone }}
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

.date {
  line-height: 1.3rem;
  text-align: center;
  padding-top: 13px;
  width: 150px !important;
  font-size: 15px;
}

.icon {
  width: 10px !important;
  margin-bottom: 2px !important;
}
</style>