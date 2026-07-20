<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// Nested inside DashboardYourGroups.vue's single "Your Groups" panel (legacy
// dyg-layout's right-hand column) as an <h3> sub-section - not a standalone
// panel/<h2> of its own.
const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
  // Gates the "Add" (event) button - true when the user is Host on at least
  // one of their dashboard groups (see DashboardYourGroups' amAHost).
  amAHost: {
    type: Boolean,
    default: false,
  },
})

const { t, locale } = useI18n()

const sortedEvents = computed(() =>
  [...props.events].sort((a, b) => new Date(a.start) - new Date(b.start))
)

function dayOfMonth(iso) {
  return new Date(iso).getDate()
}

function month(iso) {
  return new Date(iso).toLocaleDateString(locale.value, { month: 'short' })
}

function dateLabel(iso) {
  return new Date(iso).toLocaleDateString(locale.value, {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function timeLabel(iso) {
  return new Date(iso).toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div data-testid="dashboard-upcoming-events">
    <div class="d-flex justify-content-between">
      <div>
        <h3>{{ t('dashboard.upcoming_events_title') }}</h3>
        <p v-if="sortedEvents.length">{{ t('dashboard.upcoming_events_subtitle') }}</p>
        <p v-else data-testid="upcoming-events-empty">{{ t('events.no_upcoming_for_your_groups') }}.</p>
      </div>
      <div>
        <BButton
          v-if="amAHost"
          variant="primary"
          to="/party/create"
          class="text-nowrap"
          data-testid="upcoming-events-add"
        >
          {{ t('dashboard.add_event') }}
        </BButton>
      </div>
    </div>

    <ul v-if="sortedEvents.length" class="list-unstyled" data-testid="upcoming-events-list">
      <li
        v-for="event in sortedEvents"
        :key="event.id"
        class="d-flex align-items-center py-2 border-bottom"
        :data-testid="`upcoming-event-${event.id}`"
      >
        <div class="datebox text-center fw-bold me-2">
          <div class="day">{{ dayOfMonth(event.start) }}</div>
          <div class="month">{{ month(event.start) }}</div>
        </div>

        <div class="flex-grow-1">
          <NuxtLink :to="`/party/view/${event.id}`" :data-testid="`upcoming-event-link-${event.id}`">
            {{ event.title }}
          </NuxtLink>
          <BBadge
            v-if="event.online"
            variant="primary"
            pill
            class="ms-1 nounderline"
            :data-testid="`upcoming-event-online-${event.id}`"
          >
            {{ t('events.online') }}
          </BBadge>
          <div class="small">
            {{ dateLabel(event.start) }} {{ timeLabel(event.start) }}
            <span v-if="event.end" class="d-none d-md-inline">- {{ timeLabel(event.end) }}</span>
          </div>
        </div>

        <img
          :src="event.group.image_url || '/images/placeholder-avatar.webp'"
          alt=""
          width="48"
          height="48"
          class="group-avatar ms-2"
        >
      </li>
    </ul>

    <div class="d-flex justify-content-end">
      <NuxtLink to="/party" data-testid="upcoming-events-see-all">
        {{ t('partials.see_all_events') }}
      </NuxtLink>
    </div>
  </div>
</template>

<style scoped lang="scss">
h3 {
  font-size: 1rem;
  font-weight: bold;
}

.datebox {
  width: 48px;

  .day {
    font-size: 1.5rem;
    line-height: 1.5rem;
  }
}

.group-avatar {
  border: 1px solid #222;
}

.nounderline {
  text-decoration: none !important;
}
</style>
