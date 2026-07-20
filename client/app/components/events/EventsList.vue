<script setup>
import { useI18n } from 'vue-i18n'
import EventCard from './EventCard.vue'

// Renders a bucket of events (already filtered/sorted by the caller - see
// pages/party/{index,all,all-past}.vue, which use the pure helpers in
// composables/useEventComputed.js to bucket the single myEvents list) as a
// data table, with loading/empty states. Gap 1: develop renders this as a
// b-table (GroupEventScrollTable.vue) with icon-labelled stat columns, not a
// card list - this is that table, one <tr> per event via EventCard.vue.
// Kept deliberately dumb otherwise: no tabs, no fetching - GroupEventsList
// .vue owns that for the group-view tab, this owns just the list rendering
// shared across the three /party pages.
defineProps({
  events: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  emptyMessage: {
    type: String,
    default: '',
  },
  hostedGroupIds: {
    type: Array,
    default: () => [],
  },
  // Gap 18: EventCard.vue's no-devices CTA needs to know canedit, which is
  // isAdmin OR hosting-that-row's-group - hosting is already computed
  // per-row below from hostedGroupIds, so this only needs the admin half.
  isAdmin: {
    type: Boolean,
    default: false,
  },
  // Column set: false = invited/volunteers (upcoming/nearby/all buckets),
  // true = participants/volunteers/waste/co2/fixed/repairable/dead
  // (finished buckets) - see GroupEventScrollTable.vue's identically-named
  // prop. A single table only ever renders one bucket, so this is set once
  // per EventsList instance rather than per row.
  past: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
</script>

<template>
  <div data-testid="events-list">
    <div v-if="loading" data-testid="events-list-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <p v-else-if="!events.length" data-testid="events-list-empty">
      {{ emptyMessage }}
    </p>

    <div v-else class="table-responsive">
      <table class="table align-middle" data-testid="events-list-items">
        <thead>
          <tr>
            <th scope="col" />
            <!-- Gap 1: date_long column header - GroupEventScrollTable.vue's
                 head(date_long) shows a clock icon rather than text. -->
            <th scope="col">
              <img src="/images/clock.svg" alt="" class="col-icon-sm">
            </th>
            <th scope="col" />
            <template v-if="!past">
              <th scope="col" class="text-center">
                <img src="/images/mail_ico.svg" alt="" class="col-icon" :title="t('events.invited')">
              </th>
              <th scope="col" class="text-center">
                <img src="/images/participants.svg" alt="" class="col-icon" :title="t('groups.volunteers')">
              </th>
            </template>
            <template v-else>
              <th scope="col" class="text-center">
                <img src="/images/participants.svg" alt="" class="col-icon" :title="t('groups.participants')">
              </th>
              <th scope="col" class="text-center">
                <img src="/icons/volunteer_ico.svg" alt="" class="col-icon" :title="t('groups.volunteers')">
              </th>
              <th scope="col" class="text-center">
                <img src="/images/trash.svg" alt="" class="col-icon" :title="t('events.stat-7')">
              </th>
              <th scope="col" class="text-center">
                <img src="/images/cloud-empty.svg" alt="" class="col-icon" :title="t('events.stat-6')">
              </th>
              <th scope="col" class="text-center">
                <img src="/images/fixed.svg" alt="" class="col-icon" :title="t('partials.fixed')">
              </th>
              <th scope="col" class="text-center">
                <img src="/images/repairable_ico.svg" alt="" class="col-icon" :title="t('partials.repairable')">
              </th>
              <th scope="col" class="text-center">
                <img src="/images/dead_ico.svg" alt="" class="col-icon" :title="t('partials.end_of_life')">
              </th>
            </template>
            <th scope="col" />
          </tr>
        </thead>
        <tbody>
          <EventCard
            v-for="event in events"
            :key="event.id"
            :event="event"
            :past="past"
            :hosting="!!event.group && hostedGroupIds.includes(event.group.id)"
            :canedit="isAdmin || (!!event.group && hostedGroupIds.includes(event.group.id))"
          />
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.col-icon {
  width: 24px;
  height: 24px;
}

.col-icon-sm {
  width: 12px;
}
</style>
