<script setup>
import { computed, onMounted, ref } from 'vue'
import { useToastStore } from '~/stores/toast.js'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import { useGroupsStore } from '~/stores/groups.js'
import { useDevicesStore } from '~/stores/devices.js'
import { useAuth } from '~/composables/useAuth.js'
import { useEventComputed } from '~/composables/useEventComputed.js'
import { useCalendarLinks } from '~/composables/useCalendarLinks.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'
import EventAttendees from '~/components/events/EventAttendees.vue'
import EventInviteModal from '~/components/events/EventInviteModal.vue'
import EventDevicesPanel from '~/components/devices/EventDevicesPanel.vue'

// /party/view/[id] - resources/views/events/view.blade.php +
// resources/js/components/EventPage.vue (+ EventHeading/EventActions/
// EventDetails/EventDescription/EventAttendance/EventAttendee/EventStats/
// EventDevices) are the functional spec (design.md §6.2 section 4;
// api-contracts-phase-c.md C1a-g). Public page (no `auth: true` -
// routes/web.php:92 has no auth middleware on GET /party/view/{id}), same
// as the legacy route which allows anonymous viewing with
// show_login_join_to_anons.
//
// Device add/edit/delete (C5) is wired in via
// components/devices/EventDevicesPanel.vue, gated on the same `canedit`
// approximation as the Edit/Duplicate/Delete event buttons below - it
// renders read-only rows for everyone else. The create/edit/duplicate
// *event* forms + moderation-approve `.event-approve` select (C4) remain
// separate pages/slices; this page only shows the muted "requires
// moderation" note EventDescription.vue already shows today, no approve
// control.

const { t } = useI18n()
const route = useRoute()
const eventsStore = useEventsStore()
const groupsStore = useGroupsStore()
const devicesStore = useDevicesStore()
const { hasRole, loggedIn } = useAuth()

const id = computed(() => Number(route.params.id))

const event = computed(() => eventsStore.current.data)
const { upcoming, finished, dayOfMonth, month, date, start, end } = useEventComputed(event)
const calendarLinks = useCalendarLinks(event)
const { uploadedImageUrl } = useUploadedImageUrl()

const isAttending = computed(() => !!event.value?.attending)
const approved = computed(() => !!event.value?.approved)

// --- Permissions --------------------------------------------------------
// The v2 Event resource carries no canedit/candelete/permissions field
// (unlike Group's - see docs/nuxt-migration/api-gaps.md Phase C), so these
// are a best-effort client approximation of
// Fixometer::userHasEditPartyPermission()/userHasDeletePartyPermission():
// Administrator always qualifies (exact parity); NetworkCoordinator's
// per-network match can't be checked client-side (no network-membership
// data is exposed anywhere in the API today) so is deliberately NOT
// granted for edit/delete here - a safe false-negative (button hidden for
// some legitimate NCs), never a false-positive, and the server remains the
// real enforcement point regardless. Host is approximated from
// GET /api/v2/dashboard's your_groups[].role, the same capped-at-5,
// best-effort source pages/party/index.vue already uses for its "hosting"
// badge.
const isAdmin = computed(() => hasRole('Administrator'))
// Memberships come from GET /api/v2/users/me/groups (uncapped, role per
// group) rather than the dashboard's your_groups (capped at 5 and only
// fresh after visiting /dashboard) - the stale-cap variant hid the device
// panel from hosts of newly created groups.
const hostedGroupIds = computed(() =>
  (groupsStore.memberships || []).filter((g) => g.role === 3).map((g) => g.id)
)
const memberGroupIds = computed(() => (groupsStore.memberships || []).map((g) => g.id))
const isHostOfGroup = computed(() => !!event.value?.group && hostedGroupIds.value.includes(event.value.group.id))
const inGroup = computed(() => !!event.value?.group && memberGroupIds.value.includes(event.value.group.id))

const canedit = computed(() => isAdmin.value || isHostOfGroup.value)
// Party::canDelete() (zero devices) is a client-side-only confirm-dialog
// rule today, not enforced by the DELETE endpoint either
// (api-contracts-phase-c.md C1g judgment call 4) - replicated here to
// match, not added server-side.
const candelete = computed(() => canedit.value && devicesStore.list(id.value).length === 0)

const groupImage = computed(() => uploadedImageUrl(event.value?.group?.image) || '/images/placeholder-avatar.webp')

const showInvite = ref(false)
const confirmingDelete = ref(false)
const deleting = ref(false)
const attendPending = ref(false)
const requestingReview = ref(false)
const showFollowPrompt = ref(false)
const nowFollowing = ref(false)

useHead({ title: computed(() => event.value?.title || t('events.events')) })

function load() {
  eventsStore.fetchEvent(id.value)
  eventsStore.fetchAttendees(id.value)
  // The device list lives in stores/devices.js (C5), not
  // stores/events.js#devices/fetchDevices (C3) - see stores/devices.js's
  // doc comment for why that field was left in place rather than removed.
  devicesStore.fetchForEvent(id.value)
  if (loggedIn.value) {
    groupsStore.fetchMemberships().catch(() => {})
  }
}

function retry() {
  load()
}

onMounted(() => {
  load()

  // Email accept-invite redirector query flags (see group/view/[id].vue).
  // The legacy party flow only flashed the invalid case; the joined toast
  // is a small consistency addition (client.events.invite_confirmed).
  if (route.query.joined === '1') {
    useToastStore().success(t('client.events.invite_confirmed'))
  } else if (route.query.invite === 'invalid') {
    useToastStore().error(t('events.invite_invalid'))
  }
  if (route.query.joined || route.query.invite) {
    const { joined, invite, ...rest } = route.query
    navigateTo({ query: rest }, { replace: true })
  }
})

async function onRsvp() {
  attendPending.value = true

  try {
    const result = await eventsStore.attend(id.value)
    if (result?.prompt_follow_group) {
      showFollowPrompt.value = true
    }
  } catch {
    // Store already reverted the optimistic state and pushed a toast.
  } finally {
    attendPending.value = false
  }
}

async function onCancelRsvp() {
  attendPending.value = true

  try {
    await eventsStore.unattend(id.value)
  } catch {
    // Store already reverted the optimistic state and pushed a toast.
  } finally {
    attendPending.value = false
  }
}

// "Request reviews" — emails confirmed attendees asking them to review the
// event's repairs (the old event-request-review modal / getContributions).
async function onRequestReview() {
  requestingReview.value = true
  try {
    await eventsStore.requestReview(id.value)
    useToastStore().success(t('events.review_requested'))
  } catch {
    useToastStore().error(t('events.review_requested_permissions'))
  } finally {
    requestingReview.value = false
  }
}

// Join the hosting group from the event page (the old /group/join/{id} link,
// which is a dead web route now — join is POST /api/v2/groups/{id}/members/me).
async function joinHostingGroup() {
  if (!event.value?.group) return
  await followGroup()
}

// Replaces the Blade session flash pair 'prompt-follow-group' ->
// 'now-following-group' (view.blade.php:42-58): shown after a successful
// RSVP when the caller isn't already in the hosting group
// (api-contracts-phase-c.md C1c's `prompt_follow_group` flag).
async function followGroup() {
  if (!event.value?.group) return
  await groupsStore.join(event.value.group.id).catch(() => {})
  showFollowPrompt.value = false
  nowFollowing.value = true
}

function askDelete() {
  confirmingDelete.value = true
}

function cancelDelete() {
  confirmingDelete.value = false
}

async function confirmDelete() {
  confirmingDelete.value = false
  deleting.value = true

  try {
    await eventsStore.deleteEvent(id.value)
    await navigateTo('/party')
  } catch {
    // Store already toasted the error.
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div class="container py-4" data-testid="event-view-page">
    <div v-if="eventsStore.current.loading" data-testid="event-view-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="eventsStore.current.error"
      :model-value="true"
      variant="danger"
      data-testid="event-view-error"
    >
      <p>{{ t('client.events.view_load_error') }}</p>
      <BButton variant="danger" data-testid="event-view-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else-if="event">
      <!-- RSVP status banners (view.blade.php:17-58) -->
      <BAlert v-if="isAttending && !finished" :model-value="true" variant="success" data-testid="event-view-rsvp-banner">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <span>{{ t('events.rsvp_message') }}</span>
          <BButton variant="secondary" :disabled="attendPending" data-testid="event-view-unattend" @click="onCancelRsvp">
            {{ t('events.rsvp_button') }}
          </BButton>
        </div>
      </BAlert>

      <BAlert v-if="showFollowPrompt" :model-value="true" variant="info" data-testid="event-view-follow-prompt">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <span>{{ t('events.follow_hosting_group', { group: event.group ? event.group.name : '' }) }}</span>
          <BButton variant="info" data-testid="event-view-follow-group-prompt" @click="followGroup">
            {{ t('groups.join_group_button') }}
          </BButton>
        </div>
      </BAlert>

      <BAlert v-if="nowFollowing" :model-value="true" variant="success" data-testid="event-view-now-following">
        {{ t('client.events.now_following_group') }}
      </BAlert>

      <!-- Header -->
      <header class="d-flex flex-wrap justify-content-between mb-4" data-testid="event-view-header">
        <div class="d-flex">
          <div class="datebox text-center fw-bold me-3" data-testid="event-view-date">
            <div class="day">{{ dayOfMonth }}</div>
            <div class="month">{{ month }}</div>
          </div>
          <div>
            <h1 data-testid="event-view-title">{{ event.title }}</h1>

            <div v-if="event.group" class="d-flex align-items-center mb-1">
              <img :src="groupImage" alt="" width="32" height="32" class="rounded-circle me-2">
              <span>
                {{ t('events.organised_by', { group: '' }) }}
                <NuxtLink :to="`/group/view/${event.group.id}`" class="fw-bold" data-testid="event-view-group">
                  {{ event.group.name }}
                </NuxtLink>
              </span>
            </div>

            <div class="small text-muted" data-testid="event-view-datetime">
              {{ date }} &middot; {{ start }}-{{ end }} ({{ event.timezone }})
            </div>

            <div v-if="event.online" class="small" data-testid="event-view-online">
              {{ t('events.online_event') }}
            </div>
            <div v-else-if="event.location" class="small" data-testid="event-view-venue">
              {{ event.location }}
              <a
                v-if="event.lat && event.lng"
                :href="`https://www.openstreetmap.org/?mlat=${event.lat}&mlon=${event.lng}#map=20/${event.lat}/${event.lng}`"
                target="_blank"
                rel="noopener"
                class="ms-1"
              >
                {{ t('events.view_map') }}
              </a>
            </div>

            <div v-if="calendarLinks && upcoming" class="dropdown mt-2" data-testid="event-view-calendar-dropdown">
              <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                {{ t('calendars.add_to_calendar') }}
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" target="_blank" rel="noopener" :href="calendarLinks.google" data-testid="event-view-calendar-google">{{ t('events.calendar_google') }}</a></li>
                <li><a class="dropdown-item" target="_blank" rel="noopener" :href="calendarLinks.webOutlook" data-testid="event-view-calendar-outlook">{{ t('events.calendar_outlook') }}</a></li>
                <li><a class="dropdown-item" target="_blank" rel="noopener" :href="calendarLinks.ics" data-testid="event-view-calendar-ics">{{ t('events.calendar_ical') }}</a></li>
                <li><a class="dropdown-item" target="_blank" rel="noopener" :href="calendarLinks.yahoo" data-testid="event-view-calendar-yahoo">{{ t('events.calendar_yahoo') }}</a></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-start gap-2 flex-wrap">
          <BButton
            v-if="!isAttending && !finished && loggedIn"
            variant="primary"
            :disabled="attendPending"
            data-testid="event-attend"
            @click="onRsvp"
          >
            {{ t('events.RSVP') }}
          </BButton>
          <NuxtLink
            v-else-if="!isAttending && !finished && !loggedIn"
            to="/login"
            class="btn btn-primary"
            data-testid="event-view-login-to-rsvp"
          >
            {{ t('events.RSVP') }}
          </NuxtLink>

          <!-- api-contracts-phase-c.md C1d gates POST .../invites to
               host/NC/admin, stricter than the legacy button's
               isAttending-only visibility (EventActions.vue) - gated by
               `canedit` here so the button isn't shown to attendees who
               would just get a 403 (docs/nuxt-migration/api-gaps.md). -->
          <BButton
            v-if="canedit && upcoming && approved"
            variant="outline-primary"
            data-testid="event-view-invite"
            @click="showInvite = true"
          >
            {{ t('events.invite_volunteers') }}
          </BButton>

          <BButton
            v-if="!inGroup && event.group"
            variant="outline-info"
            data-testid="event-view-follow-group"
            @click="joinHostingGroup"
          >
            {{ t('groups.join_group_button') }}
          </BButton>

          <NuxtLink v-if="canedit" :to="`/party/edit/${id}`" class="btn btn-outline-primary" data-testid="event-view-edit">
            {{ t('events.edit_event') }}
          </NuxtLink>
          <NuxtLink v-if="canedit" :to="`/party/duplicate/${id}`" class="btn btn-outline-secondary" data-testid="event-view-duplicate">
            {{ t('events.duplicate_event') }}
          </NuxtLink>

          <BButton
            v-if="canedit && finished"
            variant="outline-secondary"
            :disabled="requestingReview"
            data-testid="event-view-request-review"
            @click="onRequestReview"
          >
            {{ t('client.events.request_review') }}
          </BButton>

          <a v-if="finished && canedit" :href="`/export/devices/event/${id}`" class="btn btn-outline-secondary" data-testid="event-view-export">
            {{ t('devices.export_event_data') }}
          </a>

          <template v-if="canedit">
            <template v-if="confirmingDelete">
              <span class="small align-self-center">{{ t('events.confirm_delete') }}</span>
              <BButton variant="danger" :disabled="deleting" data-testid="event-view-delete-confirm" @click="confirmDelete">
                {{ t('partials.yes') }}
              </BButton>
              <BButton variant="link" @click="cancelDelete">{{ t('partials.cancel') }}</BButton>
            </template>
            <BButton
              v-else
              variant="outline-danger"
              :disabled="!candelete"
              data-testid="event-view-delete"
              @click="askDelete"
            >
              {{ t('events.delete_event') }}
            </BButton>
          </template>
        </div>
      </header>

      <!-- Description -->
      <section class="mb-4" data-testid="event-view-description">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div v-if="event.description" v-html="event.description" />
        <p v-if="!approved" class="small text-muted" data-testid="event-view-moderation-note">
          {{ t('partials.event_requires_moderation_by_an_admin') }}.
        </p>
      </section>

      <!-- Attendance -->
      <EventAttendees
        :event-id="id"
        :confirmed="eventsStore.attendees.data.confirmed"
        :invited="eventsStore.attendees.data.invited"
        :loading="eventsStore.attendees.loading"
        :canedit="canedit"
        :upcoming="upcoming"
        :approved="approved"
        class="mb-4"
        @invite="showInvite = true"
      />

      <!-- Stats (event.stats, already on the resource - api-contracts-phase-c.md C1a) -->
      <section v-if="finished && event.stats" class="mb-4" data-testid="event-view-stats">
        <h2>{{ t('events.items_fixed') }}</h2>
        <div class="d-flex flex-wrap gap-4">
          <div data-testid="event-view-stats-fixed">
            <div class="h3 mb-0">{{ event.stats.fixed_devices ?? 0 }}</div>
            <div class="small text-muted">{{ t('partials.fixed') }}</div>
          </div>
          <div data-testid="event-view-stats-powered">
            <div class="h3 mb-0">{{ event.stats.fixed_powered ?? 0 }}</div>
            <div class="small text-muted">{{ t('devices.powered_items') }}</div>
          </div>
          <div data-testid="event-view-stats-unpowered">
            <div class="h3 mb-0">{{ event.stats.fixed_unpowered ?? 0 }}</div>
            <div class="small text-muted">{{ t('devices.unpowered_items') }}</div>
          </div>
        </div>
      </section>

      <!-- Devices (C5: add/edit/delete for canedit viewers, read-only rows for everyone else) -->
      <EventDevicesPanel
        :event-id="id"
        :devices="devicesStore.list(id)"
        :loading="devicesStore.listLoading(id)"
        :stats="event.stats"
        :canedit="canedit"
      />

      <EventInviteModal :show="showInvite" :event-id="id" @close="showInvite = false" />
    </template>
  </div>
</template>

<style scoped>
.datebox {
  color: white;
  background-color: #000;
  min-width: 60px;
  min-height: 60px;
  padding-top: 6px;
}

.datebox .day {
  font-size: 1.5rem;
  line-height: 1.5rem;
}
</style>
