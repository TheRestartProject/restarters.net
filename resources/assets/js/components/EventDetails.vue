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
        {{ start }}-{{ end }}
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
        <ExternalLink :href="'https://www.openstreetmap.org/#map=20/' + event.latitude + '/' + event.longitude">
          {{ translatedViewMap }}
        </ExternalLink>
      </div>
    </div>
    <l-map
        ref="map"
        :zoom="16"
        :center="[event.latitude, event.longitude]"
        :style="'width: 100%; height: 200px'"
        v-if="!event.online"
    >
      <l-tile-layer :url="tiles" :attribution="attribution" />
      <l-marker :lat-lng="[event.latitude, event.longitude]" :interactive="false" />
    </l-map>
    TODO Event photos
  </div>
</template>
<script>
import { DATE_FORMAT } from '../constants'
import moment from 'moment'
import map from '../mixins/map'
import ExternalLink from './ExternalLink'

export default {
  components: {ExternalLink},
  mixins: [ map ],
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
    start() {
      return this.event.start.substring(0, 5)
    },
    end() {
      return this.event.end.substring(0, 5)
    },
    date() {
      return new moment(this.event.event_date).format(DATE_FORMAT)
    },
    translatedEventDetails() {
      return this.$lang.get('events.event_details')
    },
    translatedViewMap() {
      return this.$lang.get('events.view_map')
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