<script setup>
import { computed, ref } from 'vue'
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

const { attending, dayOfMonth, month, finished } = useEventComputed(() => props.event)

// Per-event numbers (RES gap-closure pass, gap 17) - legacy's
// GroupEventScrollTable.vue showed these as icon+number table cells
// (invited/volunteers for upcoming events; participants/volunteers/waste/
// co2/fixed_devices/repairable_devices/dead_devices for finished ones).
// be-events confirmed (2026-07-20) everything lives on a single
// `event.stats` object, always present, same shape GET /api/v2/events/{id}
// already returns and party/view/[id].vue already reads - participants/
// volunteers/invited are populated regardless of event timing, the device/
// waste/co2 counters are naturally 0 before the event starts. EventCard has
// no icon assets for invited/volunteers and is a compact card rather than a
// table, so this renders the same numbers as a small wrapping chip row
// instead, guarded field-by-field so a card whose stats haven't loaded yet
// still renders cleanly (no stats row at all).
const upcomingStats = computed(() => {
  const stats = props.event.stats
  if (finished.value || !stats) return []

  const result = []
  if (stats.invited != null) {
    result.push({ key: 'invited', label: t('events.invited'), value: stats.invited })
  }
  if (stats.volunteers != null) {
    result.push({ key: 'volunteers', label: t('groups.volunteers'), value: stats.volunteers })
  }
  return result
})

// Mirrors legacy's dangerIfZero/dangerIfOne/noDevicesError `cell-danger`
// treatment (resources/js/components/GroupEventScrollTable.vue - be-events
// confirmed these exact thresholds), collapsed per-stat rather than
// per-table-cell: participants flagged at zero, volunteers flagged at one
// or fewer (legacy's dangerIfOne - a lone host with no other volunteers
// still counts as a warning), and the three device counts flagged together
// when a finished event recorded no devices at all.
const pastStats = computed(() => {
  const stats = props.event.stats
  if (!finished.value || !stats) return []

  const deviceFieldsPresent = stats.fixed_devices != null || stats.repairable_devices != null || stats.dead_devices != null
  const noDevices = deviceFieldsPresent && (stats.fixed_devices ?? 0) + (stats.repairable_devices ?? 0) + (stats.dead_devices ?? 0) === 0

  const result = []
  if (stats.participants != null) {
    result.push({ key: 'participants', label: t('groups.participants'), value: stats.participants, danger: stats.participants <= 0 })
  }
  if (stats.volunteers != null) {
    result.push({ key: 'volunteers', label: t('groups.volunteers'), value: stats.volunteers, danger: stats.volunteers <= 1 })
  }
  if (stats.waste_total != null) {
    result.push({ key: 'waste', label: t('partials.waste_prevented'), value: `${Math.round(stats.waste_total)} kg` })
  }
  if (stats.co2_total != null) {
    result.push({ key: 'co2', labelHtml: t('partials.co2'), value: `${Math.round(stats.co2_total)} kg` })
  }
  if (stats.fixed_devices != null) {
    result.push({ key: 'fixed', label: t('partials.fixed'), value: stats.fixed_devices, danger: noDevices })
  }
  if (stats.repairable_devices != null) {
    result.push({ key: 'repairable', label: t('partials.repairable'), value: stats.repairable_devices, danger: noDevices })
  }
  if (stats.dead_devices != null) {
    result.push({ key: 'dead', label: t('partials.end_of_life'), value: stats.dead_devices, danger: noDevices })
  }
  return result
})

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

      <div v-if="upcomingStats.length" class="d-flex flex-wrap gap-1 mt-1" :data-testid="`event-card-upcoming-stats-${event.id}`">
        <span
          v-for="stat in upcomingStats"
          :key="stat.key"
          class="badge bg-light text-dark border fw-normal"
          :data-testid="`event-card-stat-${stat.key}-${event.id}`"
        >
          {{ stat.value }} {{ stat.label }}
        </span>
      </div>

      <div v-if="pastStats.length" class="d-flex flex-wrap gap-1 mt-1" :data-testid="`event-card-past-stats-${event.id}`">
        <span
          v-for="stat in pastStats"
          :key="stat.key"
          class="badge fw-normal"
          :class="stat.danger ? 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' : 'bg-light text-dark border'"
          :data-testid="`event-card-stat-${stat.key}-${event.id}`"
        >
          {{ stat.value }}
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-if="stat.labelHtml" v-html="stat.labelHtml" />
          <template v-else>{{ stat.label }}</template>
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
