<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import { useEventComputed } from '~/composables/useEventComputed.js'

// One <tr> of EventsList.vue's table (/party, /party/all, /party/all-past -
// api-contracts-phase-c.md C2). Gap 1: develop renders this bucket as a
// scrollable data TABLE (GroupEventScrollTable.vue, b-table + fields()),
// not a card list - this component now renders a single table row to match,
// with EventsList.vue supplying the <table>/<thead> shell. Column set/icons
// mirror GroupEventScrollTable.vue's fields(): date box, title (+group name,
// venue/online), then either invited/volunteers (upcoming) or participants/
// volunteers/waste/co2/fixed/repairable/dead (past, `past` prop), plus an
// actions column (RSVP button).
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
  // Audit fix: GroupEventScrollTable.vue's noDevices() gates the "no
  // devices added" CTA on `this.canedit` too, not just zero devices - a
  // read-only visitor shouldn't get an "add a device" link they can't use.
  // Not derivable from `hosting` alone (an Administrator can edit a group
  // they don't host) - EventsList.vue combines its own isAdmin prop with
  // the per-row `hosting` it already computes for this.
  canedit: {
    type: Boolean,
    default: false,
  },
  // Column set to render, matching GroupEventScrollTable.vue's `past` prop:
  // false renders invited/volunteers (upcoming/nearby/all buckets), true
  // renders participants/volunteers/waste/co2/fixed/repairable/dead
  // (finished buckets). EventsList.vue passes this through unchanged for
  // every row in a given table, since a single table only ever shows one
  // bucket at a time.
  past: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const eventsStore = useEventsStore()
const pending = ref(false)

const { attending, dayOfMonth, month, date, start, end, timezone } = useEventComputed(() => props.event)

// Per-event numbers (RES gap-closure pass, gap 1/17) - legacy's
// GroupEventScrollTable.vue showed these as icon+number table cells
// (invited/volunteers for upcoming events; participants/volunteers/waste/
// co2/fixed_devices/repairable_devices/dead_devices for finished ones).
// be-events confirmed (2026-07-20) everything lives on a single
// `event.stats` object, always present, same shape GET /api/v2/events/{id}
// already returns - guarded field-by-field so a row whose stats haven't
// loaded yet still renders cleanly (no stat cells at all, matching
// GroupEventsScrollTableNumber's `v-if="value !== null"`).
const stats = computed(() => props.event.stats || null)

const invited = computed(() => stats.value?.invited ?? null)
const volunteers = computed(() => stats.value?.volunteers ?? null)

const participants = computed(() => stats.value?.participants ?? null)
const pastVolunteers = computed(() => stats.value?.volunteers ?? null)
const waste = computed(() => stats.value?.waste_total ?? null)
const co2 = computed(() => stats.value?.co2_total ?? null)
const fixedDevices = computed(() => stats.value?.fixed_devices ?? null)
const repairableDevices = computed(() => stats.value?.repairable_devices ?? null)
const deadDevices = computed(() => stats.value?.dead_devices ?? null)

const participantsDanger = computed(() => participants.value !== null && participants.value <= 0)
const volunteersDanger = computed(() => pastVolunteers.value !== null && pastVolunteers.value <= 1)

// Gap 18: when a finished event recorded zero devices, develop shows
// explanatory text + a direct "add a device" link instead of the device/
// waste/co2 stat numbers (GroupEventScrollTable.vue's noDevices()/
// noDevicesError()). A plain <table> (unlike b-table) can colspan, so this
// replaces the whole stats block in one cell rather than legacy's
// single-cell squeeze workaround.
const noDevices = computed(() => {
  if (!props.past || !stats.value || !props.canedit) return false
  return (fixedDevices.value ?? 0) + (repairableDevices.value ?? 0) + (deadDevices.value ?? 0) === 0
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
  <tr
    class="event-card"
    :class="{ attending }"
    :data-testid="`event-card-${event.id}`"
  >
    <td class="datecell text-center fw-bold" :data-testid="`event-card-date-${event.id}`">
      <div class="datebox mx-auto">
        <div class="day">{{ dayOfMonth }}</div>
        <div class="month">{{ month }}</div>
      </div>
    </td>

    <!-- Gap 1: GroupEventsScrollTableDateLong.vue's long-date column (full
         date, start-end time - end hidden below md, matching its own
         `d-none d-md-inline` - plus a small clock-icon/timezone line).
         useEventComputed.js already derives all four fields; this column
         was never wired up to render them. -->
    <td class="text-start small" :data-testid="`event-card-datelong-${event.id}`">
      <div>{{ date }}</div>
      <div>{{ start }} <span class="d-none d-md-inline">- {{ end }}</span></div>
      <div class="text-muted">
        <img :src="'/images/clock.svg'" alt="" class="datelong-icon">
        {{ timezone }}
      </div>
    </td>

    <td>
      <NuxtLink :to="`/party/view/${event.id}`" :data-testid="`event-card-link-${event.id}`">
        <b>{{ event.title }}</b>
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
    </td>

    <template v-if="!past">
      <td class="text-center" :data-testid="`event-card-stat-invited-${event.id}`">
        {{ invited }}
      </td>
      <td class="text-center" :data-testid="`event-card-stat-volunteers-${event.id}`">
        {{ volunteers }}
      </td>
    </template>

    <template v-else-if="noDevices">
      <!-- 7 stat columns in the past-bucket field set (participants/
           volunteers/waste/co2/fixed/repairable/dead) - was colspan="5",
           squeezing this row into the wrong width. -->
      <td colspan="7" class="text-center small" data-testid="event-card-no-devices">
        {{ t('events.no_devices_added') }}
        <NuxtLink :to="`/party/view/${event.id}`">{{ t('events.add_a_device') }}</NuxtLink>
      </td>
    </template>
    <template v-else-if="past">
      <td
        class="text-center"
        :class="{ 'bg-danger-subtle text-danger-emphasis': participantsDanger }"
        :data-testid="`event-card-stat-participants-${event.id}`"
      >
        {{ participants }}
      </td>
      <td
        class="text-center"
        :class="{ 'bg-danger-subtle text-danger-emphasis': volunteersDanger }"
        :data-testid="`event-card-stat-volunteers-${event.id}`"
      >
        {{ pastVolunteers }}
      </td>
      <td class="text-center" :data-testid="`event-card-stat-waste-${event.id}`">
        {{ waste !== null ? `${Math.round(waste)} kg` : null }}
      </td>
      <td class="text-center" :data-testid="`event-card-stat-co2-${event.id}`">
        {{ co2 !== null ? `${Math.round(co2)} kg` : null }}
      </td>
      <td class="text-center" :data-testid="`event-card-stat-fixed-${event.id}`">
        {{ fixedDevices }}
      </td>
      <td class="text-center" :data-testid="`event-card-stat-repairable-${event.id}`">
        {{ repairableDevices }}
      </td>
      <td class="text-center" :data-testid="`event-card-stat-dead-${event.id}`">
        {{ deadDevices }}
      </td>
    </template>

    <td class="text-end text-nowrap">
      <BButton
        variant="outline-primary"
        size="sm"
        :disabled="pending"
        :data-testid="attending ? `event-unattend-${event.id}` : `event-attend-${event.id}`"
        @click="onToggleAttendance"
      >
        {{ attending ? t('events.rsvp_button') : t('events.RSVP') }}
      </BButton>
    </td>
  </tr>
</template>

<style scoped>
/* Gap 17: full-row highlight for events you're attending (develop's
   GroupEventScrollTable.vue rowClass()/.attending, incl. a black date
   cell) - in addition to the badge, not instead of it. */
.event-card.attending {
  background-color: var(--bs-secondary-bg, #eee);
}

.event-card.attending .datecell {
  background-color: #000;
  color: #fff;
}

.datebox {
  width: 48px;
}

.datebox .day {
  font-size: 1.5rem;
  line-height: 1.5rem;
}

.datelong-icon {
  width: 10px;
  margin-bottom: 2px;
}
</style>
