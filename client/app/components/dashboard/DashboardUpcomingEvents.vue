<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  events: {
    type: Array,
    default: () => [],
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
    <h2>{{ t('dashboard.upcoming_events_title') }}</h2>

    <div v-if="!sortedEvents.length" data-testid="upcoming-events-empty">
      <p>{{ t('events.no_upcoming_for_your_groups') }}.</p>
    </div>

    <template v-else>
      <p>{{ t('dashboard.upcoming_events_subtitle') }}</p>

      <ul class="list-unstyled" data-testid="upcoming-events-list">
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
              v-if="event.attending"
              variant="success"
              class="ms-2"
              :data-testid="`upcoming-event-attending-${event.id}`"
            >
              {{ t('client.dashboard.attending') }}
            </BBadge>
            <div class="small">
              {{ dateLabel(event.start) }} {{ timeLabel(event.start) }}
              <NuxtLink :to="`/group/view/${event.group.id}`">{{ event.group.name }}</NuxtLink>
            </div>
          </div>
        </li>
      </ul>

      <div class="d-flex justify-content-end">
        <NuxtLink to="/party" data-testid="upcoming-events-see-all">
          {{ t('partials.see_all_events') }}
        </NuxtLink>
      </div>
    </template>
  </div>
</template>

<style scoped lang="scss">
h2 {
  font-size: 1.1rem;
  font-weight: bold;
}

.datebox {
  width: 48px;

  .day {
    font-size: 1.5rem;
    line-height: 1.5rem;
  }
}
</style>
