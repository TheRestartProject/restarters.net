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
import EventFilters from '~/components/events/EventFilters.vue'
import EventCollapsibleSection from '~/components/events/EventCollapsibleSection.vue'
import EventsRequiringModeration from '~/components/networks/NetworkEventsModerationTable.vue'
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
// events/index.blade.php:3-5 - the page title is "Events", not
// "Your events" (which is the heading of one section within it).
useHead({ title: t('general.menu_events') })

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
// Gap 24: develop's CalendarAddModal shows an inline "copied to clipboard"
// confirmation once the copy button has been used (vue-clipboard2's
// copySucceed handler) - useClipboard() doesn't report success/failure, so
// this is the optimistic equivalent: true as soon as a copy is requested.
const calendarCopied = ref(false)

function openCalendarModal() {
  showCalendarModal.value = true
  calendarCopied.value = false
  if (!profileStore.calendars.data) {
    profileStore.fetchCalendars().catch(() => {})
  }
}

function copyCalendarLink(url) {
  copy(url)
  calendarCopied.value = true
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

// "All other events" filter bar (gap 18) - legacy's GroupEventsScrollTableFilters.vue
// (title/country/date-range, filters=true on the "all" GroupEventsTab). The
// otherEvents list is already fully loaded client-side (single GET
// /api/v2/users/me/events, see the file doc comment above), so - like
// pages/party/all-past.vue's title search - this filters in memory rather
// than re-fetching. Country options come from the events' own
// group.country (be-events added it to UserController::shapeMyEvent, the
// same GET /api/v2/users/me/events response this page already consumes),
// deduped/sorted the same way the legacy multiselect's countryOptions
// computed did.
const allSearch = ref('')
const allCountry = ref('')
const allStart = ref('')
const allEnd = ref('')

const otherCountryOptions = computed(() => {
  const countries = new Set()
  for (const e of otherEvents.value) {
    if (e.group?.country) countries.add(e.group.country)
  }
  return [...countries].sort((a, b) => a.localeCompare(b))
})

// Inclusive range check against the event's start instant, mirroring
// legacy's date.isBetween(start, end, undefined, '[]') - approximated in
// the browser's local timezone rather than a moment-timezone comparison,
// which is precise enough for a day-granularity filter.
function inDateRange(event) {
  if (!allStart.value && !allEnd.value) return true
  if (!event.start) return false

  const eventDate = new Date(event.start)
  if (allStart.value && eventDate < new Date(`${allStart.value}T00:00:00`)) return false
  if (allEnd.value && eventDate > new Date(`${allEnd.value}T23:59:59`)) return false
  return true
}

const hasAllFilters = computed(() => !!(allSearch.value || allCountry.value || allStart.value || allEnd.value))

const filteredOtherEvents = computed(() => {
  const term = allSearch.value.trim().toLowerCase()

  return otherEvents.value.filter((e) => {
    if (term) {
      const haystack = [e.title, e.location, e.group?.name].filter(Boolean).join(' ').toLowerCase()
      if (!haystack.includes(term)) return false
    }
    if (allCountry.value && e.group?.country !== allCountry.value) return false
    return inDateRange(e)
  })
})

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
    <!-- Gap 6: page-level 'Events' h1 + doodle, matching develop's
         events/index.blade.php (the distinct 'Your events' heading lives on
         the collapsible section below, not here). -->
    <div class="d-flex align-items-center mb-4">
      <h1 class="mb-0 me-4">{{ t('events.events') }}</h1>
      <div class="me-auto d-none d-md-block" aria-hidden="true">
        <svg width="52" height="76" viewBox="0 0 52 76">
          <g fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" transform="translate(1.03 1)">
            <path d="M12.223 56.957c-3.494-7.434-7.211-14.645-9.367-20.37-2.007-5.278-2.082-11 2.825-10.631C9.324 26.253 14.3 37.4 14.3 37.4a134.293 134.293 0 0 1-.669-20.964C14.3 7.073 13.487 1.2 18.022 1.2c4.312 0 4.312 7.063 4.312 14.274s-.149 14.051.446 13.307c.595-.818.52-7.583 4.758-7.434 4.163.223 3.866 6.914 3.866 6.914s.372-4.684 3.717-4.684c3.42 0 4.535 7.657 4.535 7.657s.372-4.981 4.163-4.386c6.022.892 1.338 22.377-1.784 30.852" transform="translate(-1.332 -1.2)" />
            <path d="M8.847 0H0" transform="translate(12.155 9.07)" />
            <path d="M0 0l8.847 1.636" transform="translate(12.155 11.151)" />
            <path d="M11.918 10.886S4.707.7 1.733 4.418s8.7 8.029 10.185 7.806-3.643 8.624-6.839 6.1c-3.42-2.754 1.338-4.762 6.839-7.438z" transform="translate(-1.25 -1.816)" />
          </g>
        </svg>
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

    <!-- events/index.blade.php:55 renders <EventsRequiringModeration/>, the
         full events table, not the plain name-only list. Same substitution
         already corrected on /group and the network page. -->
    <EventsRequiringModeration v-if="showModeration" class="mb-3" />

    <!-- Gap 20: AlertsBanner sits after the moderation queue, matching
         GroupEvents.vue's slot order (h1/add-event row + moderation queue
         come from the blade wrapper, then AlertBanner is the first thing
         inside GroupEvents.vue itself). -->
    <AlertsBanner />

    <div v-if="eventsStore.myEvents.loading || !eventsStore.myEvents.loaded" data-testid="party-mine-loading">
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
      <!-- Gap 7: mobile-collapsed-by-default section with a count badge next
           to the heading, matching GroupEvents.vue's CollapsibleSection. -->
      <EventCollapsibleSection :count="upcomingMine.length" data-testid="party-mine-content">
        <template #title>
          <h2 class="mb-0 d-flex align-items-center">
            {{ t('events.your_events') }}
            <!-- Gap 16: filled primary icon button next to the section
                 heading (develop's b-btn variant=primary + subs_cal_ico.svg),
                 not the outer page h1. -->
            <button
              type="button"
              class="btn btn-primary btn-sm ms-2 d-flex align-items-center"
              :aria-label="t('calendars.copy_button_label')"
              data-testid="party-calendar-button"
              @click="openCalendarModal"
            >
              <img src="/images/subs_cal_ico.svg" alt="" width="20" height="20">
            </button>
          </h2>
        </template>

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
            :is-admin="hasRole('Administrator')"
            :empty-message="t('groups.no_past_events')"
            past
          />
        </div>
      </EventCollapsibleSection>

      <template v-if="showOther">
        <EventCollapsibleSection :count="nearbyEvents.length + otherEvents.length" class="mt-4" data-testid="party-other-events">
          <template #title>
            <h2 class="mb-0">{{ t('events.other_events') }}</h2>
          </template>

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
            <EventFilters
              v-model:search="allSearch"
              v-model:country="allCountry"
              v-model:start="allStart"
              v-model:end="allEnd"
              :country-options="otherCountryOptions"
              date-range
            />
            <EventsList
              :events="filteredOtherEvents"
              :empty-message="hasAllFilters ? t('client.events.no_search_results') : t('groups.no_other_events')"
            />
          </div>
        </EventCollapsibleSection>
      </template>
    </template>

    <BModal
      :model-value="showCalendarModal"
      :title="t('groups.calendar_copy_title', { group: t('groups.groups_title1').toLowerCase() })"
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
        <!-- Gap 24: title (above) matches GroupEvents.vue's
             translatedCalendarTitle (groups.calendar_copy_title) - that
             key's copy doesn't itself vary by group, but it's the correct
             develop source key, replacing an unrelated static title
             (profile.calendars.my_events - "My events", still used by
             CalendarsTab.vue). The description text below already used
             the right pattern (groups.calendar_copy_description with a
             :group param, falling back to groups.groups_title1 lowercased
             on this no-group "my events" page - develop does the same). -->
        <p data-testid="party-calendar-description">{{ t('groups.calendar_copy_description', { group: t('groups.groups_title1').toLowerCase() }) }}</p>
        <div class="input-group mb-3">
          <input
            type="text"
            class="form-control"
            readonly
            :value="profileStore.calendars.data.user_url"
            :aria-label="t('profile.calendars.my_events')"
            data-testid="party-calendar-url"
          >
          <BButton
            variant="primary"
            data-testid="party-calendar-copy"
            @click="copyCalendarLink(profileStore.calendars.data.user_url)"
          >
            {{ t('profile.calendars.copy_link') }}
          </BButton>
        </div>
        <BAlert
          v-if="calendarCopied"
          :model-value="true"
          variant="info"
          data-testid="party-calendar-copied"
        >
          {{ t('events.copied_to_clipboard') }}
        </BAlert>
        <!-- Gap 24: external "Find out more" help link, matching
             CalendarAddModal.vue's hardcoded Restarters Talk article. -->
        <a
          href="https://talk.restarters.net/t/fixometer-how-to-add-repair-events-to-your-calendar-application/1770"
          target="_blank"
          rel="noopener"
          class="d-block mb-2"
          data-testid="party-calendar-find-out-more"
        >
          {{ t('calendars.find_out_more') }}
        </a>
        <NuxtLink :to="`/profile/edit/${user?.id}`" data-testid="party-calendar-see-all">
          {{ t('calendars.see_all_calendars') }}
        </NuxtLink>
      </template>
    </BModal>
  </div>
</template>
