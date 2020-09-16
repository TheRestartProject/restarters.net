<template>
  <div class="lineheight">
    <h2>{{ translatedEventDetails }}</h2>
    <div class="border-top-thick d-flex pt-1 pb-1">
      <div class="mr-2">
        <b-img-lazy src="/icons/date_ico.svg" class="icon" />
      </div>
      <div class="d-flex justify-content-between w-100">
        <div>
          {{ date }}
        </div>
        <div>
          TODO add calendar
        </div>
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
        <div v-for="host in hosts">
           {{ host.volunteer.name }}
        </div>
      </div>
    </div>
    <div class="border-top-thin d-flex pt-1 pb-1" v-if="!event.online">
      <div class="mr-2">
        <b-img-lazy src="/icons/map_marker_ico.svg" class="icon" />
      </div>
      <div class="d-flex justify-content-between w-100">
        <div>
          {{ event.location}}
        </div>
        <div>
          TODO view on map
        </div>
      </div>
    </div>
    TODO map only if !event->online
    TODO Event photos
<!--    {{ event }}-->
  </div>
</template>
<script>
import { DATE_FORMAT } from '../constants'
import moment from 'moment'

export default {
  props: {
    eventId: {
      type: Number,
      required: true
    },
    event: {
      type: Object,
      required: true
    },
    hosts: {
      type: Array,
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
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.border-top-thin {
  border-top: 1px solid $black;
}

.border-top-thick {
  border-top: 2px solid $black;
}

.lineheight {
  line-height: 2;
}

h2 {
  font-size: 24px;
  font-weight: bold;
}
</style>