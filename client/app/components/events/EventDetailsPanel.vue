<script setup>
import { useI18n } from 'vue-i18n'
import { useEventComputed } from '~/composables/useEventComputed.js'
import { useCalendarLinks } from '~/composables/useCalendarLinks.js'
import EventVenueMap from './EventVenueMap.vue'

// Gap 9: develop's EventDetails.vue renders a bordered, icon-led row list
// (date [+ add-to-calendar dropdown], time, hosts, link, location [+ map])
// - the Nuxt view page used to render this same information as plain
// unadorned text lines inside the header block. This is that same icon+
// border-row list, pulled out into its own component and placed in the
// page's left column (gap 4's 2-col layout) below the header, matching
// EventPage.vue's structure (EventHeading, then EventDetails+EventDescription
// side-by-side with EventAttendance).
//
// The discourse "Talk thread" row (EventDetails.vue's talk_ico.svg link,
// gap 11) is NOT reproduced - the v2 Event resource carries no
// discourse_thread field at all (app/Http/Resources/Party.php), unlike the
// Blade controller which injected it from Party::$discourse_thread
// server-side. Backend gap: GET /api/v2/events/{id} needs a
// discourse_thread URL field (only populated when the caller is attending,
// mirroring view.blade.php's $discourseThread gate) before this row can be
// added without fabricating data.
const props = defineProps({
  event: {
    type: Object,
    required: true,
  },
  // Confirmed attendees with role === EVENT_ROLE_HOST (gap 10) - raw
  // attendee objects from useEventAttendance(id).hosts, itself a filter
  // over the attendee list the page already fetches (GET /api/v2/events/
  // {id}/attendees). Real data, not a new endpoint.
  hosts: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()
const { upcoming, date, start, end, timezone } = useEventComputed(() => props.event)
const calendarLinks = useCalendarLinks(() => props.event)
</script>

<template>
  <div data-testid="event-details-panel">
    <h2>{{ t('events.event_details') }}</h2>

    <div class="detail-row detail-row--thick d-flex justify-content-between flex-wrap pt-1 pb-1">
      <div class="d-flex">
        <img src="/icons/date_ico.svg" alt="" class="detail-icon me-2">
        <div data-testid="event-details-date">{{ date }}</div>
      </div>
      <div v-if="upcoming && calendarLinks" class="dropdown" data-testid="event-view-calendar-dropdown">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
          {{ t('calendars.add_to_calendar') }}
        </button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" target="_blank" rel="noopener" :href="calendarLinks.google" data-testid="event-view-calendar-google">{{ t('events.calendar_google') }}</a></li>
          <li><a class="dropdown-item" target="_blank" rel="noopener" :href="calendarLinks.webOutlook" data-testid="event-view-calendar-outlook">{{ t('events.calendar_outlook') }}</a></li>
          <li><a class="dropdown-item" target="_blank" rel="noopener" :href="calendarLinks.ics" data-testid="event-view-calendar-ics">{{ t('events.calendar_ical') }}</a></li>
          <li><a class="dropdown-item" target="_blank" rel="noopener" :href="calendarLinks.yahoo" data-testid="event-view-calendar-yahoo">{{ t('events.calendar_yahoo') }}</a></li>
        </ul>
      </div>
    </div>

    <div class="detail-row d-flex pt-1 pb-1">
      <img src="/icons/time_ico.svg" alt="" class="detail-icon me-2">
      <div data-testid="event-details-time">
        {{ start }}-{{ end }} <span class="text-muted small">{{ timezone }}</span>
      </div>
    </div>

    <div v-if="hosts.length" class="detail-row d-flex pt-1 pb-1" data-testid="event-details-hosts">
      <img src="/icons/host_ico.svg" alt="" class="detail-icon me-2">
      <div>
        <div v-for="host in hosts" :key="host.id">{{ host.volunteer ? host.volunteer.name : host.fullName }}</div>
      </div>
    </div>

    <div v-if="event.link" class="detail-row d-flex pt-1 pb-1" data-testid="event-view-link">
      <img src="/icons/link_ico.svg" alt="" class="detail-icon me-2">
      <a :href="event.link" target="_blank" rel="noopener" class="text-truncate" data-testid="event-view-link-anchor">{{ event.link }}</a>
    </div>

    <div v-if="!event.online && event.location" class="detail-row d-flex justify-content-between flex-wrap pt-1 pb-1" data-testid="event-view-venue">
      <div class="d-flex">
        <img src="/icons/map_marker_ico.svg" alt="" class="detail-icon me-2">
        <div>{{ event.location }}</div>
      </div>
      <a
        v-if="event.lat && event.lng"
        :href="`https://www.openstreetmap.org/?mlat=${event.lat}&mlon=${event.lng}#map=20/${event.lat}/${event.lng}`"
        target="_blank"
        rel="noopener"
        class="text-nowrap"
      >
        {{ t('events.view_map') }}
      </a>
    </div>

    <EventVenueMap
      v-if="!event.online && event.lat && event.lng"
      :lat="event.lat"
      :lng="event.lng"
      class="mt-2"
    />
  </div>
</template>

<style scoped>
.detail-row {
  border-top: 1px solid #222;
}

.detail-row--thick {
  border-top-width: 2px;
}

.detail-icon {
  width: 24px;
  height: 24px;
}
</style>
