<template>
  <CollapsibleSection>
    <template slot="title">
      {{ __('events.event_details') }}
    </template>
    <template slot="content">
      <div class="border-top-thick d-flex pt-1 pb-1">
        <div class="mr-2">
          <b-img-lazy src="/icons/date_ico.svg" class="icon" />
        </div>
        <div class="d-flex justify-content-between w-100 flex-wrap">
          <div>
            {{ date }}
          </div>
          <div v-if="upcoming">
            <b-dropdown v-if="upcoming && calendarLinks" id="event-calendar-dropdown" :text="__('calendars.add_to_calendar')" variant="white" class="linkdrop" no-caret>
              <b-dropdown-item target="_blank" rel="noopener" :href="calendarLinks.google">{{ __('events.calendar_google') }}</b-dropdown-item>
              <b-dropdown-item target="_blank" rel="noopener" :href="calendarLinks.webOutlook">{{ __('events.calendar_outlook') }}</b-dropdown-item>
              <b-dropdown-item target="_blank" rel="noopener" :href="calendarLinks.ics">{{ __('events.calendar_ical') }}</b-dropdown-item>
              <b-dropdown-item target="_blank" rel="noopener" :href="calendarLinks.yahoo">{{ __('events.calendar_yahoo') }}</b-dropdown-item>
            </b-dropdown>
          </div>
        </div>
      </div>
      <div class="border-top-thin d-flex pt-1 pb-1">
        <div class="mr-2">
          <b-img-lazy src="/icons/time_ico.svg" class="icon" />
        </div>
        <div>
          {{ start }}-{{ end }} <span class="text-muted small">{{ timezone }}</span>
        </div>
      </div>
      <div class="border-top-thin d-flex pt-1 pb-1" v-if="isAttending && discourseThread">
        <div class="mr-2">
          <b-img-lazy src="/icons/talk_ico.svg" class="icon" />
        </div>
        <div>
          <a :href="discourseThread">{{ __('events.talk_thread') }}</a>
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
      <div class="border-top-thin d-flex pt-1 pb-1" v-if="event.link">
        <div class="mr-2">
          <b-img-lazy src="/icons/link_ico.svg" class="icon" />
        </div>
        <div>
          <ExternalLink :href="event.link" target="_blank" rel="noopener noreferrer" class="truncate">{{ event.link }}</ExternalLink>
        </div>
      </div>
      <div class="border-top-thin d-flex pt-1 pb-1" v-if="!event.online && event.location">
        <div class="mr-2">
          <b-img-lazy src="/icons/map_marker_ico.svg" class="icon" />
        </div>
        <div class="justify-content-between w-100 d-flex flex-wrap">
          <div>
            {{ event.location}}
          </div>
          <ExternalLink v-if="event.latitude + event.longitude" :href="'https://www.openstreetmap.org/?mlat=' + event.latitude + '&mlon=' + event.longitude + '#map=20/' + event.latitude + '/' + event.longitude" class="text-nowrap">
            {{ __('events.view_map') }}
          </ExternalLink>
        </div>
      </div>
      <l-map
          ref="map"
          :zoom="16"
          :center="[event.latitude, event.longitude]"
          :style="'width: 100%; height: 200px'"
          v-if="!event.online && event.location && event.latitude + event.longitude"
      >
        <l-tile-layer :url="tiles" :attribution="attribution" />
        <l-marker :lat-lng="[event.latitude, event.longitude]" :interactive="false" />
      </l-map>
    </template>
  </CollapsibleSection>
</template>
<script>
import map from '../mixins/map'
import event from '../mixins/event'
import ExternalLink from './ExternalLink'
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {CollapsibleSection, ExternalLink},
  mixins: [ map, event ],
  props: {
    idevents: {
      type: Number,
      required: true
    },
    hosts: {
      type: Array,
      required: true
    },
    calendarLinks: {
      type: Object,
      required: false
    },
    isAttending: {
      type: Boolean,
      required: false,
      default: false
    },
    discourseThread: {
      type: String,
      required: false,
      default: null
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

h2 {
  font-size: 24px;
  font-weight: bold;
}

::v-deep .linkdrop button[aria-expanded="true"] {
  padding: 5px;
}

.truncate {
  text-overflow: ellipsis;
  width: 400px;
  display: block;
  white-space: nowrap;
  overflow: hidden;
}
</style>