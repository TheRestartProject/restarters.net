<script setup>
import { computed, onMounted, ref } from 'vue'
import clip from 'text-clipper'
import { useToastStore } from '~/stores/toast.js'
import { useI18n } from 'vue-i18n'
import { useEventsStore } from '~/stores/events.js'
import { useGroupsStore } from '~/stores/groups.js'
import { useDevicesStore } from '~/stores/devices.js'
import { useAuth } from '~/composables/useAuth.js'
import { useEventComputed } from '~/composables/useEventComputed.js'
import { useEventAttendance } from '~/composables/useEventAttendance.js'
import { useCo2Equivalent } from '~/composables/useCo2Equivalent.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'
import EventAttendees from '~/components/events/EventAttendees.vue'
import EventInviteModal from '~/components/events/EventInviteModal.vue'
import EventAddVolunteerModal from '~/components/events/EventAddVolunteerModal.vue'
import EventCollapsibleSection from '~/components/events/EventCollapsibleSection.vue'
import EventDetailsPanel from '~/components/events/EventDetailsPanel.vue'
import EventActionsDropdown from '~/components/events/EventActionsDropdown.vue'
import EventShareStatsModal from '~/components/events/EventShareStatsModal.vue'
import StatsShareImageModal from '~/components/events/StatsShareImageModal.vue'
import EventImagesGallery from '~/components/events/EventImagesGallery.vue'
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
// approximation as the Edit/Duplicate/Delete event actions below - it
// renders read-only rows for everyone else. The create/edit/duplicate
// *event* forms + moderation-approve `.event-approve` select (C4) remain
// separate pages/slices; this page only shows the muted "requires
// moderation" note EventDescription.vue already shows today, no approve
// control.
//
// RES gap-closure pass (parity-v2/events.md, gaps 2/3/4/8/9/10/12/15/21):
// header consolidated into a single EventActionsDropdown (gap 2) with the
// 5px/1px border treatment + vertical divider (gap 8); date/time/hosts/
// link/location pulled into EventDetailsPanel with icons + bordered rows
// (gap 9) including named hosts (gap 10, sourced from the attendee list -
// no new endpoint); page laid out in EventPage.vue's 2-col grid at md+
// (gap 4); description truncated with a Read more/less toggle (gap 12);
// items-fixed/environmental-impact side by side with a CO2 equivalence
// description + info popover + share button (gap 15); a "Share event
// stats" modal (gap 3, embed-code half only - see EventShareStatsModal.vue's
// doc comment for what's deliberately not reproduced).
//
// Gaps 5/11/13/14:
// - gap 11, Discourse "Talk thread": EventDetailsPanel.vue, from
//   session config discourse_url + the resource's discourse_thread.
// - gap 5, event photos: EventImagesGallery.vue, read-only. develop's
//   EventImages/EventImage/EventImageModal carry no delete affordance at
//   all (the "Remove file" dropzone belongs to the separate edit page), so
//   a delete button here would be invention rather than parity.
// - gap 13, inline headcounts: EventAttendanceCount.vue via
//   updateHeadcount() below.
// - gap 14, add volunteer: EventAddVolunteerModal.vue. PUT
//   /api/events/{id}/volunteers sits under the same auth:sanctum,api group
//   as every v2 route this client already calls, so the existing bearer
//   token just works - it being outside the /api/v2 prefix was never the
//   obstacle it was recorded as.

const { t } = useI18n()
const route = useRoute()
const eventsStore = useEventsStore()
const groupsStore = useGroupsStore()
const devicesStore = useDevicesStore()
const { hasRole, loggedIn } = useAuth()

const id = computed(() => Number(route.params.id))

const event = computed(() => eventsStore.current.data)
const { upcoming, finished, dayOfMonth, month } = useEventComputed(event)
const { hosts } = useEventAttendance(id)
const { equivalentConsumer } = useCo2Equivalent()
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

// Environmental impact block (gap D1) - models GroupStats.vue's
// group-stats-impact stat cards for the same look (white box, teal count,
// see this page's <style>), rounded to whole kg the way that component
// rounds waste/co2 for the group page (StatsImpact.vue's fixometer/tonnes
// rounding doesn't apply here). Fields come straight off Party::
// getEventStats() (app/Party.php) via the resource's `stats` object -
// confirmed against a live GET /api/v2/events/{id}: waste_total, co2_total,
// dead_devices, repairable_devices, no_weight_powered, no_weight_unpowered.
function kg(value) {
  return `${Math.round(value ?? 0).toLocaleString()} kg`
}

// "Not counting..." note (StatsImpact.vue's `notincluded`): lists the device
// categories excluded from the two totals above. Unlike develop's version
// (partials.to_be_recycled/to_be_repaired/no_weight with Oxford-comma
// joining), the new lang keys here live under events.php per this task's
// scope, and the categories are joined with plain commas - a simpler
// rendering of the same "not counting N dead / N repairable / N no-weight
// devices" note, not a fabrication of new stats fields.
const notIncludedParts = computed(() => {
  const stats = event.value?.stats
  if (!stats) return []

  const parts = []
  if (stats.dead_devices) {
    parts.push(`${stats.dead_devices} ${t('events.to_be_recycled', { value: stats.dead_devices }, stats.dead_devices)}`)
  }
  if (stats.repairable_devices) {
    parts.push(`${stats.repairable_devices} ${t('events.to_be_repaired', { value: stats.repairable_devices }, stats.repairable_devices)}`)
  }
  const noWeight = (stats.no_weight_powered || 0) + (stats.no_weight_unpowered || 0)
  if (noWeight) {
    parts.push(`${noWeight} ${t('events.no_weight', { value: noWeight }, noWeight)}`)
  }
  return parts
})

const notIncludedNote = computed(() => {
  const parts = notIncludedParts.value
  if (!parts.length) return null
  return `${t('events.not_counting', parts.length)} ${parts.join(', ')}.`
})

// Gap 12: develop truncates the description to 440 characters behind a
// Read more/Read less toggle (EventDescription.vue's ReadMore, max-chars
// 440), using text-clipper for mid-tag-safe HTML clipping (a plain
// substring cut can sever a tag half-open) - a CSS max-height clamp
// approximated this before, but the cutoff point visibly differed from
// develop's. `needsTruncating` is simplified from ReadMore's own version:
// that component compares html-to-text renderings of the original vs
// clipped HTML (to tolerate text-clipper's own serialization not being
// byte-identical even when nothing was cut) - a direct string comparison
// is enough here, since text-clipper returns the *original* string
// unchanged whenever clipping wasn't needed.
const descriptionExpanded = ref(false)
const clippedDescription = computed(() => {
  const html = event.value?.description
  return html ? clip(html, 440, { html: true }) : ''
})
const descriptionNeedsTruncating = computed(() => {
  const html = event.value?.description
  return !!html && clippedDescription.value !== html
})

const showInvite = ref(false)
const showAddVolunteer = ref(false)
const showShareStats = ref(false)
// StatsImpact.vue's CO2-card "Share this" trigger opens a DIFFERENT modal
// from the header dropdown's "Share event stats" (showShareStats above,
// EventShareStatsModal's embed-code iframe) - this one is the canvas-
// painted social-image generator (StatsShareImageModal.vue), matching
// group/view/[id].vue's identical showShareImage/shareImageCount pair for
// its own CO2 card.
const showShareImage = ref(false)
const shareImageCount = computed(() => Math.round(event.value?.stats?.co2_total ?? 0))
const confirmingDelete = ref(false)
const deleting = ref(false)
const attendPending = ref(false)
const requestingReview = ref(false)
const showFollowPrompt = ref(false)
const nowFollowing = ref(false)
const co2InfoButton = ref(null)

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

// Gap 13: EventAttendanceCount.vue's +/- stepper (EventAttendees.vue,
// wired below). PATCH /api/v2/events/{id} validates the full set of
// required event fields on every call (EventController::validateEventParams,
// same as a normal edit), not just participants/volunteers in isolation -
// so this resends the event's own current field values alongside the
// changed count, mirroring EventForm.vue's own update payload shape.
async function updateHeadcount(field, value) {
  if (!event.value) return

  const payload = {
    start: event.value.start,
    end: event.value.end,
    title: event.value.title,
    description: event.value.description,
    location: event.value.location || null,
    online: event.value.online,
    link: event.value.link || null,
    timezone: event.value.timezone || null,
    network_data: JSON.stringify(event.value.network_data || {}),
    [field]: value,
  }

  try {
    await eventsStore.updateEventCount(id.value, payload, field, value)
  } catch {
    useToastStore().error(t('events.edit_failed'))
  }
}

// Gap 14: EventAttendance.vue's own EventAddVolunteerModal always refetches
// on close (`@hide="fetchVolunteers"`), not just on a confirmed success -
// matched here rather than only refetching from the modal's own success
// path, since the modal has no way to signal "nothing changed" vs
// "cancelled" that would make skipping the refetch worthwhile.
function closeAddVolunteer() {
  showAddVolunteer.value = false
  eventsStore.fetchAttendees(id.value)
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

      <!-- Header (gap 8: 5px top border, 1px bottom border, vertical divider
           between the date/title block and the group/actions block). -->
      <header class="event-header mb-4" data-testid="event-view-header">
        <div class="eh-layout">
          <div class="d-flex eh-left">
            <div class="datebox text-center fw-bold me-3" data-testid="event-view-date">
              <div class="day">{{ dayOfMonth }}</div>
              <div class="month">{{ month }}</div>
            </div>
            <div>
              <h1 data-testid="event-view-title">
                {{ event.title }}
                <BBadge v-if="event.online" variant="primary" pill data-testid="event-view-online">{{ t('events.online') }}</BBadge>
              </h1>
            </div>
          </div>

          <div class="eh-right d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div v-if="event.group" class="d-flex align-items-center mb-1">
              <img :src="groupImage" alt="" width="32" height="32" class="rounded-circle me-2 group-avatar">
              <span>
                {{ t('events.organised_by', { group: '' }) }}
                <NuxtLink :to="`/group/view/${event.group.id}`" class="fw-bold" data-testid="event-view-group">
                  {{ event.group.name }}
                </NuxtLink>
              </span>
            </div>

            <div class="d-flex align-items-start gap-2 flex-wrap">
              <!-- Anonymous-only "log in to RSVP" link. develop has no
                   standalone RSVP entry point for logged-in users - that lives
                   in EventActionsDropdown - and the dropdown doesn't render at
                   all when !loggedIn. -->
              <NuxtLink
                v-if="!isAttending && !finished && !loggedIn"
                to="/login"
                class="btn btn-primary"
                data-testid="event-view-login-to-rsvp"
              >
                {{ t('events.RSVP') }}
              </NuxtLink>

              <!-- Gap 2: every other event action lives in one dropdown. -->
              <EventActionsDropdown
                v-if="loggedIn"
                :event-id="id"
                :canedit="canedit"
                :candelete="candelete"
                :is-admin="isAdmin"
                :is-attending="isAttending"
                :upcoming="upcoming"
                :approved="approved"
                :finished="finished"
                :in-group="inGroup"
                :has-group="!!event.group"
                @rsvp="onRsvp"
                @invite="showInvite = true"
                @request-review="onRequestReview"
                @share-stats="showShareStats = true"
                @follow-group="joinHostingGroup"
                @delete="askDelete"
              />
            </div>
          </div>
        </div>
      </header>

      <!-- Gap 4: 2-column grid at md+ (details+description left, attendance
           right), matching EventPage.vue's .ep-layout. -->
      <div class="ep-layout mb-4">
        <div>
          <EventDetailsPanel :event="event" :hosts="hosts" />

          <section class="mt-4" data-testid="event-view-description">
            <!-- EventDescription.vue:2 - CollapsibleSection, `collapsed`
                 and `hide-title`. This was a bare heading, which meant the
                 mobile collapse toggle was missing: a long description could
                 not be collapsed on a phone, which is the whole point of the
                 component develop uses here. -->
            <EventCollapsibleSection collapsed hide-title>
              <template #title>
                <h2 class="mb-0">{{ t('events.event_description') }}</h2>
              </template>
            <div
              v-if="event.description"
              class="description-content"
              data-testid="event-view-description-content"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div v-html="descriptionNeedsTruncating && !descriptionExpanded ? clippedDescription : event.description" />
            </div>
            <button
              v-if="descriptionNeedsTruncating"
              type="button"
              class="btn btn-link px-0"
              data-testid="event-view-description-toggle"
              @click="descriptionExpanded = !descriptionExpanded"
            >
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span v-html="descriptionExpanded ? t('events.read_less') : t('events.read_more')" />
            </button>
              <p v-if="!approved" class="small text-muted" data-testid="event-view-moderation-note">
                {{ t('partials.event_requires_moderation_by_an_admin') }}.
              </p>
            </EventCollapsibleSection>
          </section>
        </div>

        <div>
          <!-- Attendance -->
          <EventAttendees
            :event-id="id"
            :confirmed="eventsStore.attendees.data.confirmed"
            :invited="eventsStore.attendees.data.invited"
            :loading="eventsStore.attendees.loading"
            :canedit="canedit"
            :upcoming="upcoming"
            :approved="approved"
            :finished="finished"
            :participants="event.stats ? (event.stats.participants ?? 0) : null"
            :volunteers="event.stats ? (event.stats.volunteers ?? 0) : null"
            @invite="showInvite = true"
            @add-volunteer="showAddVolunteer = true"
            @update-participants="updateHeadcount('participants', $event)"
            @update-volunteers="updateHeadcount('volunteers', $event)"
          />
        </div>
      </div>

      <!-- Gap 5: event-photos gallery, unblocked now the resource returns
           `images` - EventPage.vue only renders EventImages.vue when
           images.length, same as the component's own v-if. -->
      <EventImagesGallery :images="event.images || []" class="mb-4" />

      <!-- Stats (event.stats, already on the resource - api-contracts-phase-c.md C1a).
           Gap 15: items-fixed and environmental-impact side by side in a
           2-column grid with a divider, both using the same icon-card
           treatment. -->
      <div v-if="finished && event.stats" class="stats-grid mb-4">
        <section data-testid="event-view-stats">
          <h2 class="mt-2 mb-2">{{ t('events.items_fixed') }}</h2>
          <div class="d-flex flex-wrap gap-3">
            <!-- EventStatsItems.vue:5 - the fixed-devices card is
                 `variant="primary"` with NO `title` prop, i.e. a $brand-light
                 filled card showing the count alone. The other two are plain
                 white and DO carry a title. This one was rendering white with
                 a "Fixed" label, so it was indistinguishable from them. -->
            <div class="stat-card stat-card--primary" data-testid="event-view-stats-fixed">
              <img src="/images/fixed.svg" alt="" class="stat-card__icon">
              <div class="stat-card__count">{{ event.stats.fixed_devices ?? 0 }}</div>
            </div>
            <div class="stat-card" data-testid="event-view-stats-powered">
              <img src="/images/powered.svg" alt="" class="stat-card__icon">
              <div class="stat-card__count">{{ event.stats.fixed_powered ?? 0 }}</div>
              <div class="stat-card__label">{{ t('devices.powered_items') }}</div>
            </div>
            <div class="stat-card" data-testid="event-view-stats-unpowered">
              <img src="/images/unpowered.svg" alt="" class="stat-card__icon">
              <div class="stat-card__count">{{ event.stats.fixed_unpowered ?? 0 }}</div>
              <div class="stat-card__label">{{ t('devices.unpowered_items') }}</div>
            </div>
          </div>
        </section>

        <section data-testid="event-view-impact">
          <h2>
            {{ t('events.environmental_impact') }}
            <button ref="co2InfoButton" type="button" class="info-toggle" data-testid="event-view-impact-info">
              <img src="/icons/info_ico_green.svg" alt="" width="20" height="20">
            </button>
            <BPopover :target="co2InfoButton" triggers="hover focus click" html data-testid="event-view-impact-popover">
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div v-html="t('events.impact_calculation')" />
            </BPopover>
          </h2>
          <div class="d-flex flex-wrap gap-3">
            <div class="stat-card" data-testid="event-view-impact-waste">
              <img src="/images/trash.svg" alt="" class="stat-card__icon">
              <div class="stat-card__count">{{ kg(event.stats.waste_total) }}</div>
              <div class="stat-card__label">{{ t('partials.waste_prevented') }}</div>
            </div>
            <div class="stat-card" data-testid="event-view-impact-co2">
              <img src="/images/cloud-empty.svg" alt="" class="stat-card__icon">
              <div class="stat-card__count">{{ kg(event.stats.co2_total) }}</div>
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div class="stat-card__label" v-html="t('partials.co2')" />
              <!-- eslint-disable-next-line vue/no-v-html -->
              <!-- StatsValue.vue:18 gates the equivalence description on
                   `count > 0`, so develop shows nothing here for an event
                   with no CO2 recorded. We rendered it unconditionally,
                   producing "that's like growing 0 tree seedlings for 10
                   years" on every zero-impact event. -->
              <div v-if="event.stats.co2_total > 0" class="small" data-testid="event-view-impact-equivalent" v-html="equivalentConsumer(event.stats.co2_total)" />
              <!-- StatsValue.vue's "Share this" - the CO2-card trigger for
                   the social-image modal, distinct from the header
                   dropdown's "Share event stats" embed-code modal above. -->
              <button
                type="button"
                class="btn btn-link p-0 small"
                data-testid="event-view-impact-share"
                @click="showShareImage = true"
              >
                {{ t('partials.share_this') }}
              </button>
            </div>
          </div>
          <p v-if="notIncludedNote" class="small text-muted mt-2 mb-0" data-testid="event-view-impact-notincluded">
            {{ notIncludedNote }}
          </p>
        </section>
      </div>

      <!-- Devices (C5: add/edit/delete for canedit viewers, read-only rows for everyone else) -->
      <EventDevicesPanel
        :event-id="id"
        :devices="devicesStore.list(id)"
        :loading="devicesStore.listLoading(id)"
        :stats="event.stats"
        :canedit="canedit"
      />

      <EventInviteModal
        :show="showInvite"
        :event-id="id"
        :group-id="event?.group?.id ?? null"
        :group-name="event?.group?.name || ''"
        :shareable-link="event?.shareable_link || ''"
        @close="showInvite = false"
      />

      <EventAddVolunteerModal
        :show="showAddVolunteer"
        :event-id="id"
        :group-id="event.group ? event.group.id : null"
        @close="closeAddVolunteer"
      />

      <EventShareStatsModal
        :show="showShareStats"
        :event-id="id"
        :event-date="event.start"
        :event-name="event.title"
        :fixed-devices="event.stats ? (event.stats.fixed_devices ?? 0) : 0"
        @close="showShareStats = false"
      />

      <StatsShareImageModal
        :show="showShareImage"
        :count="shareImageCount"
        @close="showShareImage = false"
      />

      <BModal
        :model-value="confirmingDelete"
        :title="t('events.delete_event')"
        no-footer
        data-testid="event-view-delete-modal"
        @hide="cancelDelete"
      >
        <p>{{ t('events.confirm_delete') }}</p>
        <div class="d-flex justify-content-end gap-2">
          <BButton variant="outline-secondary" @click="cancelDelete">
            {{ t('partials.cancel') }}
          </BButton>
          <BButton variant="danger" :disabled="deleting" data-testid="event-view-delete-confirm" @click="confirmDelete">
            {{ t('partials.yes') }}
          </BButton>
        </div>
      </BModal>
    </template>
  </div>
</template>

<style scoped>
/* Exact 70x70 square (gap D7), matching develop's EventHeading.vue .datebox
   (min/max-width/height all pinned to 70px, not just a min-width floor). */
.datebox {
  color: white;
  background-color: #000;
  width: 70px;
  height: 70px;
  padding-top: 8px;
}

.datebox .day {
  font-size: 1.5rem;
  line-height: 1.5rem;
}

/* develop's EventHeading.vue .groupImage crops non-square uploads instead of
   squashing them (gap D7). */
.group-avatar {
  object-fit: cover;
}

/* Gap 8: 5px top border / 1px bottom border on the whole header, with a
   vertical divider (border-right on the left column) between the date/
   title block and the group/actions block at md+ - matches
   EventHeading.vue's .border-top-very-thick/.border-bottom-thin/.bord. */
.event-header {
  border-top: 5px solid #000;
  border-bottom: 1px solid #000;
  padding: 1rem 0;
}

.eh-layout {
  display: grid;
  grid-template-columns: 1fr;
  row-gap: 0.75rem;
}

@media (min-width: 768px) {
  .eh-layout {
    grid-template-columns: 1fr 1fr;
  }

  .eh-left {
    border-right: 1px solid #000;
    padding-right: 1rem;
  }

  .eh-right {
    padding-left: 1rem;
  }
}

/* Gap 4: 2-column grid at md+ (details+description left, attendance
   right), matching EventPage.vue's .ep-layout. */
.ep-layout {
  display: grid;
  grid-template-columns: 1fr;
  row-gap: 1.5rem;
}

@media (min-width: 768px) {
  .ep-layout {
    grid-template-columns: 1fr 1fr;
    column-gap: 2rem;
  }
}

/* Gap 15: items-fixed/environmental-impact side by side with a divider. */
.stats-grid {
  display: grid;
  grid-template-columns: 1fr;
  row-gap: 1.5rem;
}

@media (min-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr 1fr;
    column-gap: 2rem;
  }

  .stats-grid > section:last-child {
    border-left: 1px solid #000;
    padding-left: 2rem;
  }
}

/* Neo-brutalist stat card - same look as components/groups/GroupStats.vue's
   .stat-card (white box, near-black border + offset shadow, teal value), for
   visual consistency across the group/event environmental-impact sections. */
.stat-card {
  flex: 1 1 0;
  min-width: 90px;
  padding: 1rem 0.5rem;
  text-align: center;
  background: #fff;
  border: 1px solid #222;
  box-shadow: 4px 4px 0 #222;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}

.stat-card__icon {
  height: 40px;
}

.stat-card__count {
  font-size: 1.75rem;
  font-weight: bold;
  color: var(--bs-primary, #0394a6);
  line-height: 1;
}

/* StatsValue.vue's `.impact-stat-primary` / `.impact-stat-count-primary`:
   $brand-light ground with white text; only the fixed-devices card uses it.
   Deliberately declared AFTER `.stat-card` and doubled up on the class:
   `.stat-card` sets `background: #fff` at the same specificity, so a
   single-class rule placed earlier lost the cascade and rendered a white
   card with white-on-white text - i.e. no visible count at all. */
.stat-card.stat-card--primary {
  background: #4aaebc;
  color: #fff;
}

.stat-card.stat-card--primary .stat-card__count {
  color: #fff;
}

.stat-card__label {
  line-height: 1.1;
}

/* StatsImpact.vue's info-icon popover trigger: an inline, borderless icon
   button next to the "Environmental impact" heading. */
.info-toggle {
  border: 0;
  background: none;
  padding: 0;
  margin-left: 0.35rem;
  vertical-align: middle;
}
</style>
