<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import { useEventComputed } from '~/composables/useEventComputed.js'

// Single-event summary card for EventsList.vue (/party, /party/all,
// /party/all-past - api-contracts-phase-c.md C2). Styling precedent:
// GroupCard.vue (card form factor) crossed with
// GroupEventsScrollTableDateShort.vue's day/month date block
// (resources/js/mixins/event.js's `dayofmonth`/`month` computeds, now
// ported via useEventComputed - see that module's doc comment).
const props = defineProps({
  event: {
    type: Object,
    required: true,
  },
  // Whether the current user hosts the group running this event. Not on
  // the event resource (api-contracts-phase-c.md C1a has no per-event role
  // field) - the caller derives it, e.g. from dashboardStore's
  // your_groups role, and passes it down.
  hosting: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const eventsStore = useEventsStore()
const pending = ref(false)

const { attending, dayOfMonth, month } = useEventComputed(() => props.event)

async function onToggleAttendance() {
  pending.value = true

  try {
    if (attending.value) {
      await eventsStore.unattend(props.event.id)
    } else {
      await eventsStore.attend(props.event.id)
    }
  } catch {
    // Store already reverted the optimistic state and pushed a toast.
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <div class="event-card d-flex align-items-center py-2 border-bottom" :data-testid="`event-card-${event.id}`">
    <div class="datebox text-center fw-bold me-3" :data-testid="`event-card-date-${event.id}`">
      <div class="day">{{ dayOfMonth }}</div>
      <div class="month">{{ month }}</div>
    </div>

    <div class="flex-grow-1">
      <NuxtLink :to="`/party/view/${event.id}`" :data-testid="`event-card-link-${event.id}`">
        {{ event.title }}
      </NuxtLink>

      <BBadge
        v-if="attending"
        variant="success"
        class="ms-2"
        :data-testid="`event-card-attending-${event.id}`"
      >
        {{ t('client.dashboard.attending') }}
      </BBadge>
      <BBadge
        v-if="hosting"
        variant="primary"
        class="ms-2"
        :data-testid="`event-card-hosting-${event.id}`"
      >
        {{ t('client.events.hosting') }}
      </BBadge>

      <div class="small text-muted">
        <NuxtLink v-if="event.group" :to="`/group/view/${event.group.id}`" :data-testid="`event-card-group-${event.id}`">
          {{ event.group.name }}
        </NuxtLink>
        <span v-if="event.online" class="ms-1" :data-testid="`event-card-online-${event.id}`">
          {{ t('events.online_event') }}
        </span>
        <span v-else-if="event.location" class="ms-1" :data-testid="`event-card-venue-${event.id}`">
          - {{ event.location }}
        </span>
      </div>
    </div>

    <BButton
      variant="outline-primary"
      size="sm"
      class="text-nowrap ms-2"
      :disabled="pending"
      :data-testid="attending ? `event-unattend-${event.id}` : `event-attend-${event.id}`"
      @click="onToggleAttendance"
    >
      {{ attending ? t('events.rsvp_button') : t('events.RSVP') }}
    </BButton>
  </div>
</template>

<style scoped>
.datebox {
  width: 48px;
}

.datebox .day {
  font-size: 1.5rem;
  line-height: 1.5rem;
}
</style>
