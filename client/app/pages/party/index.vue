<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import { useDashboardStore } from '~/stores/dashboard.js'
import { useProfileStore } from '~/stores/profile.js'
import { useEventPermissions } from '~/composables/useEventPermissions.js'
import { useAuth } from '~/composables/useAuth.js'
import { useClipboard } from '~/composables/useClipboard.js'
import EventsList from '~/components/events/EventsList.vue'
import ModerationQueue from '~/components/moderation/ModerationQueue.vue'
import AlertsBanner from '~/components/alerts/AlertsBanner.vue'
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
// "Add event" CTA gate - mirrors legacy events/index.blade.php's
// userCanCreateEvents check (Root/Admin/NetworkCoordinator or host of any
// group). Without this the SPA had no way to reach /party/create at all.
const { canCreateEvents, ensureLoaded: ensureEventPerms } = useEventPermissions()

// Events-requiring-moderation queue, shown to Administrators and
// NetworkCoordinators above the event lists (legacy events/index.blade.php
// rendered <EventsRequiringModeration> for those roles).
const { hasRole, user } = useAuth()
const showModeration = computed(() => hasRole('Administrator') || hasRole('NetworkCoordinator'))

// Personal iCal calendar link (RES gap-closure pass): a compact
// "copy calendar link" affordance beside the heading, matching legacy
// events/index.blade.php + resources/js/components/GroupEvents.vue's
// calendar-icon button -> CalendarAddModal.vue. Reuses stores/profile.js's
// fetchCalendars() (GET /api/v2/users/me/calendars) - already consumed by
// components/profile/CalendarsTab.vue - which always operates on
// Auth::user(), matching this page's own-events scope.
const profileStore = useProfileStore()
const { copy } = useClipboard()
const showCalendarModal = ref(false)

function openCalendarModal() {
  showCalendarModal.value = true
  if (!profileStore.calendars.data) {
    profileStore.fetchCalendars().catch(() => {})
  }
}

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
  ensureEventPerms().catch(() => {})
}

onMounted(load)
</script>

<template>
  <div class="container py-4" data-testid="party-mine-page">
    <AlertsBanner />

    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <h1 class="mb-0">{{ t('events.your_events') }}</h1>
        <button
          type="button"
          class="btn btn-link p-0 ms-2"
          :aria-label="t('calendars.copy_button_label')"
          data-testid="party-calendar-button"
          @click="openCalendarModal"
        >
          <svg width="24" height="16" viewBox="0 0 46.175 30" aria-hidden="true">
            <g transform="translate(10.49 -52.43)">
              <path d="M16.058,54.462a1.219,1.219,0,0,0,0,1.721A12.363,12.363,0,0,1,19.7,64.976a1.216,1.216,0,0,0,2.432,0,14.775,14.775,0,0,0-4.353-10.514A1.22,1.22,0,0,0,16.058,54.462Z" transform="translate(8.003 0.512)" />
              <path d="M25.387,68.595V65.286a8.613,8.613,0,0,0-7.417-8.548V54.308a1.217,1.217,0,0,0-2.434,0v1.513c-.522-.031-1.024-.05-1.221-.05H-2.657v-1.3a1.305,1.305,0,0,0-1.306-1.306,1.306,1.306,0,0,0-1.306,1.306v1.3H-6.573a3.917,3.917,0,0,0-3.917,3.917V75.355a3.917,3.917,0,0,0,3.917,3.917H12.348a4.769,4.769,0,0,0,9.024-.6h5.173a2.4,2.4,0,0,0,1.55-4.239A7.633,7.633,0,0,1,25.387,68.595ZM-7.879,59.688a1.305,1.305,0,0,1,1.306-1.306H11.571a8.611,8.611,0,0,0-2.31,2.611H-7.879ZM-6.573,76.66a1.306,1.306,0,0,1-1.306-1.306V63.6H8.287a8.7,8.7,0,0,0-.167,1.682v3.308A7.643,7.643,0,0,1,5.4,74.44a2.4,2.4,0,0,0-.841,1.825,2.322,2.322,0,0,0,.04.4Zm23.326,3.135a2.322,2.322,0,0,1-1.573-.627,2.37,2.37,0,0,1-.418-.5h3.982A2.34,2.34,0,0,1,16.753,79.795Zm1.366-3.539-2.87.008-8.267.022a10.052,10.052,0,0,0,3.569-7.691V65.286a6.117,6.117,0,0,1,.261-1.682,6.188,6.188,0,0,1,12.142,1.682v3.308a10,10,0,0,0,3.525,7.64Z" transform="translate(0 0.202)" />
              <path d="M22.03,52.786a1.217,1.217,0,0,0-1.721,1.721A12.355,12.355,0,0,1,23.95,63.3a1.216,1.216,0,1,0,2.432,0A14.766,14.766,0,0,0,22.03,52.786Z" transform="translate(9.303 0)" />
            </g>
          </svg>
        </button>
      </div>
      <NuxtLink
        v-if="canCreateEvents"
        to="/party/create"
        class="btn btn-primary"
        data-testid="party-add-event"
      >
        {{ t('events.add_event') }}
      </NuxtLink>
    </div>

    <ModerationQueue v-if="showModeration" type="events" class="mt-3" />

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

    <BModal
      :model-value="showCalendarModal"
      :title="t('profile.calendars.my_events')"
      no-footer
      data-testid="party-calendar-modal"
      @hide="showCalendarModal = false"
    >
      <BAlert
        v-if="profileStore.calendars.error"
        :model-value="true"
        variant="danger"
        data-testid="party-calendar-error"
      >
        {{ t('client.profile.load_error') }}
      </BAlert>
      <template v-else-if="profileStore.calendars.data">
        <div class="input-group mb-3">
          <input
            type="text"
            class="form-control"
            readonly
            :value="profileStore.calendars.data.user_url"
            data-testid="party-calendar-url"
          >
          <BButton
            variant="primary"
            data-testid="party-calendar-copy"
            @click="copy(profileStore.calendars.data.user_url)"
          >
            {{ t('profile.calendars.copy_link') }}
          </BButton>
        </div>
        <NuxtLink :to="`/profile/edit/${user?.id}`" data-testid="party-calendar-see-all">
          {{ t('calendars.see_all_calendars') }}
        </NuxtLink>
      </template>
    </BModal>
  </div>
</template>
