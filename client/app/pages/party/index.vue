<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import { useDashboardStore } from '~/stores/dashboard.js'
import EventsList from '~/components/events/EventsList.vue'
import {
  eventIsFinished,
  eventIsInProgress,
  eventIsUpcoming,
} from '~/composables/useEventComputed.js'

// /party (mine) - resources/views/events/index.blade.php (no $group) +
// resources/js/components/GroupEvents.vue (add-group-name, showOther) is
// the functional spec (design.md §6.2 section 4, api-contracts-phase-c.md
// C2). Backed by the single GET /api/v2/users/me/events list
// (stores/events.js), bucketed here exactly as GroupEvents.vue buckets its
// store getter: mine (no nearby/all tag) split upcoming/past, plus an
// "other events" section (nearby/all tagged) when present.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('events.your_events') })

const eventsStore = useEventsStore()
const dashboardStore = useDashboardStore()

const mineTab = ref('upcoming')
const otherTab = ref('nearby')

const allEvents = computed(() => eventsStore.myEvents.data)

const mineEvents = computed(() => allEvents.value.filter((e) => !e.nearby && !e.all))
const nearbyEvents = computed(() => allEvents.value.filter((e) => e.nearby))
const otherEvents = computed(() => allEvents.value.filter((e) => e.all))

const upcomingMine = computed(() =>
  mineEvents.value
    .filter((e) => eventIsUpcoming(e) || eventIsInProgress(e))
    .sort((a, b) => new Date(a.start) - new Date(b.start))
)

const pastMine = computed(() =>
  mineEvents.value
    .filter((e) => eventIsFinished(e))
    .sort((a, b) => new Date(b.start) - new Date(a.start))
)

const showOther = computed(() => nearbyEvents.value.length > 0 || otherEvents.value.length > 0)

// GET /api/v2/users/me/events carries no per-event "am I host of this
// group" field (api-contracts-phase-c.md C1a has no per-event role field
// at all) - best-effort sourced from GET /api/v2/dashboard's your_groups
// role (api-contracts-phase-b.md B1), same pattern stores/groups.js already
// uses for "mine" membership.
const hostedGroupIds = computed(() =>
  (dashboardStore.data?.your_groups || []).filter((g) => g.role === 3).map((g) => g.id)
)

// Mirrors GroupEvents.vue's nearbyNoneMessage: nudge to set a location when
// there isn't one, otherwise say there's nothing nearby.
const hasLocation = computed(() => dashboardStore.data?.has_location !== false)
const nearbyEmptyMessage = computed(() =>
  hasLocation.value ? t('groups.no_other_nearby_events') : t('events.no_location')
)

function retry() {
  load()
}

function load() {
  eventsStore.fetchMyEvents()
  dashboardStore.fetch().catch(() => {})
}

onMounted(load)
</script>

<template>
  <div class="container py-4" data-testid="party-mine-page">
    <h1>{{ t('events.your_events') }}</h1>

    <div v-if="eventsStore.myEvents.loading" data-testid="party-mine-loading">
      <div class="placeholder-glow mb-3">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="eventsStore.myEvents.error"
      :model-value="true"
      variant="danger"
      data-testid="party-mine-error"
    >
      <p>{{ t('client.events.load_error') }}</p>
      <BButton variant="danger" data-testid="party-mine-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else>
      <div data-testid="party-mine-content">
        <ul class="nav nav-tabs">
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: mineTab === 'upcoming' }"
              data-testid="party-mine-tab-upcoming"
              @click="mineTab = 'upcoming'"
            >
              {{ t('groups.upcoming_active') }} ({{ upcomingMine.length }})
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: mineTab === 'past' }"
              data-testid="party-mine-tab-past"
              @click="mineTab = 'past'"
            >
              {{ t('groups.past') }} ({{ pastMine.length }})
            </button>
          </li>
        </ul>

        <div v-if="mineTab === 'upcoming'" class="pt-3" data-testid="party-mine-panel-upcoming">
          <EventsList
            :events="upcomingMine"
            :hosted-group-ids="hostedGroupIds"
            :empty-message="t('groups.no_upcoming_events')"
          />
        </div>
        <div v-else class="pt-3" data-testid="party-mine-panel-past">
          <EventsList
            :events="pastMine"
            :hosted-group-ids="hostedGroupIds"
            :empty-message="t('groups.no_past_events')"
          />
        </div>
      </div>

      <template v-if="showOther">
        <hr>

        <div data-testid="party-other-events">
          <h2>{{ t('events.other_events') }}</h2>

          <ul class="nav nav-tabs">
            <li class="nav-item">
              <button
                type="button"
                class="nav-link"
                :class="{ active: otherTab === 'nearby' }"
                data-testid="party-other-tab-nearby"
                @click="otherTab = 'nearby'"
              >
                {{ t('groups.nearby') }} ({{ nearbyEvents.length }})
              </button>
            </li>
            <li class="nav-item">
              <button
                type="button"
                class="nav-link"
                :class="{ active: otherTab === 'all' }"
                data-testid="party-other-tab-all"
                @click="otherTab = 'all'"
              >
                {{ t('groups.all') }} ({{ otherEvents.length }})
              </button>
            </li>
          </ul>

          <div v-if="otherTab === 'nearby'" class="pt-3" data-testid="party-other-panel-nearby">
            <EventsList :events="nearbyEvents" :empty-message="nearbyEmptyMessage" />
          </div>
          <div v-else class="pt-3" data-testid="party-other-panel-all">
            <EventsList :events="otherEvents" :empty-message="t('groups.no_other_events')" />
          </div>
        </div>
      </template>
    </template>
  </div>
</template>
