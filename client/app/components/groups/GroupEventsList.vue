<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// GET /api/v2/groups/{id}/events (already implemented server-side -
// API\GroupController::getEventsForGroupv2, returns EventSummary[]
// unfiltered). Functional spec: GroupEvents.vue + GroupEventsTab.vue's
// upcoming/past tabs - split done client-side here since the endpoint
// doesn't distinguish (no `finished`/`upcoming`/`inprogress` flags like the
// old Vuex-normalised event objects).
const props = defineProps({
  events: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const { t, locale } = useI18n()

const activeTab = ref('upcoming')

const upcoming = computed(() =>
  props.events
    .filter((e) => new Date(e.end || e.start) >= new Date())
    .sort((a, b) => new Date(a.start) - new Date(b.start))
)

const past = computed(() =>
  props.events
    .filter((e) => new Date(e.end || e.start) < new Date())
    .sort((a, b) => new Date(b.start) - new Date(a.start))
)

function dateLabel(iso) {
  return new Date(iso).toLocaleDateString(locale.value, {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}
</script>

<template>
  <div data-testid="group-events-list">
    <div v-if="loading" data-testid="group-events-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <template v-else>
      <ul class="nav nav-tabs">
        <li class="nav-item">
          <button
            type="button"
            class="nav-link"
            :class="{ active: activeTab === 'upcoming' }"
            data-testid="group-events-tab-upcoming"
            @click="activeTab = 'upcoming'"
          >
            {{ t('groups.upcoming_active') }} ({{ upcoming.length }})
          </button>
        </li>
        <li class="nav-item">
          <button
            type="button"
            class="nav-link"
            :class="{ active: activeTab === 'past' }"
            data-testid="group-events-tab-past"
            @click="activeTab = 'past'"
          >
            {{ t('groups.past') }} ({{ past.length }})
          </button>
        </li>
      </ul>

      <div v-if="activeTab === 'upcoming'" data-testid="group-events-panel-upcoming" class="pt-3">
        <p v-if="!upcoming.length" data-testid="group-events-empty-upcoming">
          {{ t('groups.no_upcoming_events') }}
        </p>
        <ul v-else class="list-unstyled">
          <li
            v-for="event in upcoming"
            :key="event.id"
            class="py-2 border-bottom"
            :data-testid="`group-event-${event.id}`"
          >
            <NuxtLink :to="`/party/view/${event.id}`" :data-testid="`group-event-link-${event.id}`">
              {{ event.title }}
            </NuxtLink>
            <div class="small text-muted">
              {{ dateLabel(event.start) }}
              <span v-if="event.location">- {{ event.location }}</span>
            </div>
          </li>
        </ul>
      </div>

      <div v-else data-testid="group-events-panel-past" class="pt-3">
        <p v-if="!past.length" data-testid="group-events-empty-past">
          {{ t('groups.no_past_events') }}
        </p>
        <ul v-else class="list-unstyled">
          <li
            v-for="event in past"
            :key="event.id"
            class="py-2 border-bottom"
            :data-testid="`group-event-${event.id}`"
          >
            <NuxtLink :to="`/party/view/${event.id}`" :data-testid="`group-event-link-${event.id}`">
              {{ event.title }}
            </NuxtLink>
            <div class="small text-muted">
              {{ dateLabel(event.start) }}
              <span v-if="event.location">- {{ event.location }}</span>
            </div>
          </li>
        </ul>
      </div>
    </template>
  </div>
</template>
