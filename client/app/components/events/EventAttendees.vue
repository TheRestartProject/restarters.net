<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'
import { useEventsStore } from '../../stores/events.js'
import { EVENT_ROLE_HOST } from '../../composables/useEventAttendance.js'
import EventAttendanceCount from './EventAttendanceCount.vue'

// GET /api/v2/events/{id}/attendees (api-contracts-phase-c.md C1b).
// Functional spec: EventAttendance.vue + EventAttendee.vue (confirmed/
// invited tabs, host badge, skill count, remove-if-canedit) - remove is
// wired to `DELETE .../volunteers/{idevents_users}` (C1d) via
// stores/events.js#removeAttendee.
//
// Email visibility (audit fix): `attendee.volunteer.email` is only present
// in the API response when the caller has edit-party permission (mirrors
// listVolunteers' showEmails gate), so it was never a client-side leak, but
// this component used to render it inline on every confirmed row anyway -
// develop's EventAttendee.vue never shows email there at all; the only
// place develop surfaces it is the add-volunteer modal (gap 14 - now built,
// see EventAddVolunteerModal.vue, wired in below). Rather than keep an
// exposure develop doesn't have, the field is left unrendered here
// entirely.
const props = defineProps({
  eventId: {
    type: Number,
    required: true,
  },
  confirmed: {
    type: Array,
    default: () => [],
  },
  invited: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  canedit: {
    type: Boolean,
    default: false,
  },
  upcoming: {
    type: Boolean,
    default: false,
  },
  approved: {
    type: Boolean,
    default: true,
  },
  // Gap 21: develop's EventAttendance.vue shows total-participants/
  // -volunteers headcounts next to the confirmed/invited tabs for any
  // non-upcoming event (in-progress OR finished, its `!upcoming` grid
  // condition) - `finished` is kept as a prop (still used elsewhere on the
  // page) but no longer gates the headcount row; `upcoming` does.
  finished: {
    type: Boolean,
    default: false,
  },
  participants: {
    type: Number,
    default: null,
  },
  volunteers: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['invite', 'add-volunteer', 'update-participants', 'update-volunteers'])

const { t } = useI18n()
const eventsStore = useEventsStore()
const { uploadedImageUrl } = useUploadedImageUrl()

const activeTab = ref('confirmed')
// Gap 22: a real confirm modal (matching develop's ConfirmModal) rather
// than an inline Yes/Cancel row swap - one shared modal, keyed to whichever
// attendee is pending removal (same pattern as
// components/devices/DeviceRow.vue's delete-confirm modal).
const removeTarget = ref(null)

function displayName(attendee) {
  return attendee.volunteer ? attendee.volunteer.name : attendee.fullName
}

function skillCount(attendee) {
  const skills = attendee.volunteer?.user_skills
  return skills ? skills.length : 0
}

function skillNames(attendee) {
  return (attendee.volunteer?.user_skills || []).map((s) => s.skill_name.skill_name).join(', ')
}

function profileImage(attendee) {
  return uploadedImageUrl(attendee.profilePath) || '/images/placeholder-avatar.webp'
}

function askRemove(attendee) {
  removeTarget.value = attendee
}

function cancelRemove() {
  removeTarget.value = null
}

async function confirmRemove() {
  const attendee = removeTarget.value
  removeTarget.value = null
  if (!attendee) return
  await eventsStore.removeAttendee(props.eventId, attendee.id).catch(() => {})
}
</script>

<template>
  <div data-testid="event-attendees">
    <!-- The count is MOBILE-ONLY. develop passes :count to CollapsibleSection
         without `alwaysShowCount`, and that component renders it `d-md-none`
         (CollapsibleSection.vue:16-17) - the count exists to encourage taps on
         a collapsed mobile section, so at md+ develop's heading is a plain
         "Attendance". This heading is hand-rolled rather than going through
         EventCollapsibleSection (which already gets this right), so it showed
         the count at every width. -->
    <h2>
      {{ t('events.event_attendance') }}
      <span class="fw-normal d-md-none">({{ confirmed.length + invited.length }})</span>
    </h2>

    <div v-if="loading" data-testid="event-attendees-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <template v-else>
      <div
        class="attendance-layout"
        :class="{ 'attendance-layout--upcoming': upcoming }"
      >
        <div v-if="!upcoming && (participants !== null || volunteers !== null)" class="d-flex flex-wrap gap-4 mb-3 mb-md-0" data-testid="event-attendees-headcounts">
          <!-- EventAttendance.vue:15-26: an icon plus a BOLD label ABOVE each
               stepper, not a small muted caption beneath it. -->
          <div v-if="participants !== null" data-testid="event-attendees-participants">
            <b class="attendance-count-label">
              <img src="/icons/group_ico.svg" alt="" class="me-2 attendance-icon">
              {{ t('events.stat-0') }}
            </b>
            <EventAttendanceCount
              class="mt-2 mb-4"
              :count="participants"
              :canedit="canedit"
              testid="event-attendees-participants-count"
              @change="emit('update-participants', $event)"
            />
          </div>
          <div v-if="volunteers !== null" data-testid="event-attendees-volunteers">
            <b class="attendance-count-label">
              <img src="/icons/volunteer_ico.svg" alt="" class="me-2 attendance-icon">
              {{ t('events.stat-2') }}
            </b>
            <EventAttendanceCount
              class="mt-2"
              :count="volunteers"
              :canedit="canedit"
              testid="event-attendees-volunteers-count"
              @change="emit('update-volunteers', $event)"
            />
          </div>
        </div>

        <!-- EventAttendance.vue wraps the tabs in `.ourtabs` (_events.scss:326)
             - a white panel with a 1px black border and a 5px offset black
             shadow. Without it the tabs and the volunteer list float loose on
             the page background, which is how this rendered before. -->
        <div class="ourtabs">
          <ul class="nav nav-tabs">
            <li class="nav-item">
              <button
                type="button"
                class="nav-link"
                :class="{ active: activeTab === 'confirmed' }"
                data-testid="event-attendees-tab-confirmed"
                @click="activeTab = 'confirmed'"
              >
                <b>{{ t('events.confirmed') }}</b> ({{ confirmed.length }})
              </button>
            </li>
            <li class="nav-item">
              <button
                type="button"
                class="nav-link"
                :class="{ active: activeTab === 'invited' }"
                data-testid="event-attendees-tab-invited"
                @click="activeTab = 'invited'"
              >
                <b>{{ t('events.invited') }}</b> ({{ invited.length }})
              </button>
            </li>
          </ul>

          <div v-if="activeTab === 'confirmed'" class="pt-3" data-testid="event-attendees-panel-confirmed">
            <p v-if="!confirmed.length" data-testid="event-attendees-empty-confirmed" class="text-muted">
              {{ t('events.confirmed_none') }}
            </p>
            <ul v-else class="list-unstyled">
              <li
                v-for="attendee in confirmed"
                :key="attendee.id"
                class="attendee-row d-flex align-items-center justify-content-between py-2"
                :data-testid="`event-attendee-${attendee.id}`"
              >
                <div class="d-flex align-items-center">
                  <img :src="profileImage(attendee)" alt="" width="40" height="40" class="rounded-circle me-2 attendee-avatar">
                  <div>
                    <NuxtLink v-if="attendee.user" :to="`/profile/${attendee.user}`" class="fw-bold">
                      {{ displayName(attendee) }}
                    </NuxtLink>
                    <span v-else class="fw-bold">{{ displayName(attendee) }}</span>
                    <span
                      v-if="attendee.role === EVENT_ROLE_HOST"
                      class="host-label ms-1"
                      :data-testid="`event-attendee-host-badge-${attendee.id}`"
                    >
                      {{ t('partials.host') }}
                    </span>
                    <div :id="`event-attendee-skills-${attendee.id}`" class="small text-muted skill-count">
                      {{ skillCount(attendee) }} {{ t('partials.skills', skillCount(attendee)) }}
                    </div>
                    <BPopover
                      v-if="skillCount(attendee)"
                      :target="`event-attendee-skills-${attendee.id}`"
                      triggers="hover focus"
                      :data-testid="`event-attendee-skills-popover-${attendee.id}`"
                    >
                      {{ skillNames(attendee) }}
                    </BPopover>
                  </div>
                </div>

                <button
                  v-if="canedit"
                  type="button"
                  class="attendee-remove-btn"
                  :data-testid="`event-attendee-remove-${attendee.id}`"
                  @click="askRemove(attendee)"
                >
                  <img src="/icons/delete_ico_red.svg" alt="" width="20" height="20">
                </button>
              </li>
            </ul>

            <!-- Gap 14: EventAttendance.vue's "Add volunteer" link, opening
                 EventAddVolunteerModal.vue (page-level, same convention as
                 the invite-modal trigger below). Legacy shows this to any
                 viewer regardless of canedit (the server-side
                 userHasEditPartyPermission gate is the only enforcement) -
                 gated on canedit here too, consistent with the rest of this
                 page's host-only actions, rather than showing an action
                 that would just 403. -->
            <div v-if="!upcoming && canedit" class="text-end">
              <a href="#" data-testid="event-attendees-add-volunteer-link" @click.prevent="emit('add-volunteer')">
                {{ t('events.add_volunteer_modal_heading') }}
              </a>
            </div>
          </div>

          <div v-else class="pt-3" data-testid="event-attendees-panel-invited">
            <p v-if="!invited.length" data-testid="event-attendees-empty-invited" class="text-muted">
              {{ t('events.invited_none') }}
            </p>
            <ul v-else class="list-unstyled">
              <li
                v-for="attendee in invited"
                :key="attendee.id"
                class="d-flex align-items-center py-2 border-bottom"
                :data-testid="`event-attendee-${attendee.id}`"
              >
                <img :src="profileImage(attendee)" alt="" width="40" height="40" class="rounded-circle me-2">
                <span>{{ displayName(attendee) }}</span>
              </li>
            </ul>

            <div v-if="canedit && upcoming && approved" class="text-end">
              <a href="#" data-testid="event-attendees-invite-link" @click.prevent="emit('invite')">
                {{ t('events.invite_to_join') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Gap 22: real confirm modal instead of an inline confirm/cancel row. -->
    <BModal
      :model-value="!!removeTarget"
      :title="t('partials.are_you_sure')"
      no-footer
      data-testid="event-attendee-remove-modal"
      @hide="cancelRemove"
    >
      <div class="d-flex justify-content-end gap-2">
        <BButton variant="outline-secondary" @click="cancelRemove">{{ t('partials.cancel') }}</BButton>
        <BButton
          variant="danger"
          :data-testid="removeTarget ? `event-attendee-remove-confirm-${removeTarget.id}` : 'event-attendee-remove-confirm'"
          @click="confirmRemove"
        >
          {{ t('partials.yes') }}
        </BButton>
      </div>
    </BModal>
  </div>
</template>

<style scoped>
/* Gap 21: headcounts beside the tabs (not stacked above them) at md+,
   matching EventAttendance.vue's .attendance grid; stacked on mobile. */
.attendance-layout {
  display: grid;
  grid-template-columns: 1fr;
}

@media (min-width: 768px) {
  .attendance-layout:not(.attendance-layout--upcoming) {
    grid-template-columns: 1fr 2fr;
    column-gap: 1.5rem;
  }
}

/* Gap 22: black divider (not Bootstrap's default light grey), matching
   EventAttendee.vue's .blackbord. */
.attendee-row {
  border-bottom: 1px solid #000;
}

/* EventAttendance.vue:16 - the label sits above its stepper, as a block, so
   the icon and text share a line and the stepper starts underneath. */
.attendance-count-label {
  display: block;
}

.attendance-icon {
  width: 24px;
  height: 24px;
}

/* Gap 22: bordered avatar, matching EventAttendee.vue's .profile. */
.attendee-avatar {
  border: 1px solid #000;
}

/* Gap 22: plain uppercase "Host" text instead of a badge, matching
   EventAttendee.vue's .host. */
.host-label {
  text-transform: uppercase;
  color: var(--bs-primary, #0394a6);
  font-size: 0.75rem;
  font-weight: bold;
}

.skill-count {
  cursor: default;
}

/* Gap 22: red icon delete button instead of an outline-danger text button. */
.attendee-remove-btn {
  border: 0;
  background: none;
  padding: 0;
}
</style>
