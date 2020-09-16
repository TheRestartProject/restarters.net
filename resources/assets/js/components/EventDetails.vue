<template>
  <div>
    <h2>{{ translatedEventDetails }}</h2>
    <div class="border-top-thick d-flex">
      <div class="mr-2 pt-1 pb-1">
        <b-img-lazy src="/icons/time_ico.svg" class="icon" />
      </div>
      <div>
        {{ date }} TODO calendar
      </div>
    </div>
    <div class="border-top-thin d-flex pt-1 pb-1">
      <div class="mr-2">
        <b-img-lazy src="/icons/time_ico.svg" class="icon" />
      </div>
      <div>
        Time
      </div>
    </div>
    <div class="border-top-thin d-flex pt-1 pb-1">
      <div class="mr-2">
        <b-img-lazy src="/icons/host_ico.svg" class="icon" />
      </div>
      <div>
        Hosts
      </div>
    </div>
    TODO map only if !event->online
<!--    {{ event }}-->
  </div>
</template>
<script>
import { DATE_FORMAT } from '../constants'

export default {
  props: {
    eventId: {
      type: Number,
      required: true
    },
    event: {
      type: Object,
      required: true
    }
  },
  computed: {
    upcoming() {
      const now = new Date().getTime()
      const date = new Date(this.event.event_date)
      return date > now
    },
    date() {
      return new moment(this.event.event_date).format(DATE_FORMAT)
    },
    translatedEventDetails() {
      return this.$lang.get('events.event_details')
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.border-top-thin {
  border-top: 2px solid $black;
}

.border-top-thick {
  border-top: 2px solid $black;
}
</style>