<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'
import { useEventsStore } from '../../stores/events.js'
import { EVENT_ROLE_HOST } from '../../composables/useEventAttendance.js'

// GET /api/v2/events/{id}/attendees (api-contracts-phase-c.md C1b).
// Functional spec: EventAttendance.vue + EventAttendee.vue (confirmed/
// invited tabs, host badge, skill count, remove-if-canedit) - remove is
// wired to `DELETE .../volunteers/{idevents_users}` (C1d) via
// stores/events.js#removeAttendee.
//
// Email visibility: per the contract, `attendee.volunteer.email` is only
// present in the API response when the caller has edit-party permission
// (mirrors listVolunteers' showEmails gate) - so this component just
// renders it when present, with no separate client-side permission check.
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
})

const emit = defineEmits(['invite'])

const { t } = useI18n()
const eventsStore = useEventsStore()
const { uploadedImageUrl } = useUploadedImageUrl()

const activeTab = ref('confirmed')
const confirmingRemove = ref(null)

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
  return uploadedImageUrl(attendee.profilePath) || '/images/placeholder-avatar.png'
}

function askRemove(attendee) {
  confirmingRemove.value = attendee.id
}

function cancelRemove() {
  confirmingRemove.value = null
}

async function confirmRemove(attendee) {
  confirmingRemove.value = null
  await eventsStore.removeAttendee(props.eventId, attendee.id).catch(() => {})
}
</script>

<template>
  <div data-testid="event-attendees">
    <h2>
      {{ t('events.event_attendance') }}
      <span class="fw-normal">({{ confirmed.length + invited.length }})</span>
    </h2>

    <div v-if="loading" data-testid="event-attendees-loading">
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
            class="d-flex align-items-center justify-content-between py-2 border-bottom"
            :data-testid="`event-attendee-${attendee.id}`"
          >
            <div class="d-flex align-items-center">
              <img :src="profileImage(attendee)" alt="" width="40" height="40" class="rounded-circle me-2">
              <div>
                <NuxtLink v-if="attendee.user" :to="`/profile/${attendee.user}`" class="fw-bold">
                  {{ displayName(attendee) }}
                </NuxtLink>
                <span v-else class="fw-bold">{{ displayName(attendee) }}</span>
                <BBadge
                  v-if="attendee.role === EVENT_ROLE_HOST"
                  variant="primary"
                  class="ms-1"
                  :data-testid="`event-attendee-host-badge-${attendee.id}`"
                >
                  {{ t('partials.host') }}
                </BBadge>
                <div class="small text-muted" :title="skillNames(attendee)">
                  {{ skillCount(attendee) }} {{ t('partials.skills', skillCount(attendee)) }}
                </div>
                <div
                  v-if="attendee.volunteer && attendee.volunteer.email"
                  class="small text-muted"
                  :data-testid="`event-attendee-email-${attendee.id}`"
                >
                  {{ attendee.volunteer.email }}
                </div>
              </div>
            </div>

            <div v-if="canedit" class="text-nowrap">
              <template v-if="confirmingRemove === attendee.id">
                <span class="small me-2">{{ t('partials.are_you_sure') }}</span>
                <BButton
                  size="sm"
                  variant="danger"
                  :data-testid="`event-attendee-remove-confirm-${attendee.id}`"
                  @click="confirmRemove(attendee)"
                >
                  {{ t('partials.yes') }}
                </BButton>
                <BButton size="sm" variant="link" @click="cancelRemove">{{ t('partials.cancel') }}</BButton>
              </template>
              <BButton
                v-else
                size="sm"
                variant="outline-danger"
                :data-testid="`event-attendee-remove-${attendee.id}`"
                @click="askRemove(attendee)"
              >
                {{ t('partials.remove') }}
              </BButton>
            </div>
          </li>
        </ul>
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
    </template>
  </div>
</template>
