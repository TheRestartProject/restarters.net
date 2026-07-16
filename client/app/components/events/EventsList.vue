<script setup>
import EventCard from './EventCard.vue'

// Renders a bucket of events (already filtered/sorted by the caller - see
// pages/party/{index,all,all-past}.vue, which use the pure helpers in
// composables/useEventComputed.js to bucket the single myEvents list) as
// EventCard rows, with loading/empty states. Kept deliberately dumb: no
// tabs, no fetching - GroupEventsList.vue owns that for the group-view
// tab, this owns just the list rendering shared across the three /party
// pages.
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
})
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

    <div v-else data-testid="events-list-items">
      <EventCard
        v-for="event in events"
        :key="event.id"
        :event="event"
        :hosting="!!event.group && hostedGroupIds.includes(event.group.id)"
      />
    </div>
  </div>
</template>
