<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '../../stores/groups.js'

// GET /api/v2/groups/{id}/volunteers (already implemented server-side -
// API\GroupController::getVolunteersForGroupv2). Functional spec:
// GroupVolunteers.vue + GroupVolunteer.vue (grid of volunteer rows with
// host badges; manage actions - make host / remove host role / remove
// volunteer - visible when canedit/candemote, wired to the already-live
// PATCH/DELETE .../volunteers/{iduser} endpoints).
const props = defineProps({
  groupId: {
    type: Number,
    required: true,
  },
  volunteers: {
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
  candemote: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['invite'])

const { t } = useI18n()
const groupsStore = useGroupsStore()

// user id currently in the "remove this volunteer?" confirm state.
const confirmingRemove = ref(null)

function skillCount(volunteer) {
  return volunteer.skills ? volunteer.skills.length : 0
}

function skillNames(volunteer) {
  return (volunteer.skills || []).map((s) => s.name).join(', ')
}

async function makeHost(volunteer) {
  await groupsStore.setVolunteerHost(props.groupId, volunteer.user, true).catch(() => {})
}

async function removeHostRole(volunteer) {
  await groupsStore.setVolunteerHost(props.groupId, volunteer.user, false).catch(() => {})
}

function askRemove(volunteer) {
  confirmingRemove.value = volunteer.user
}

function cancelRemove() {
  confirmingRemove.value = null
}

async function confirmRemove(volunteer) {
  confirmingRemove.value = null
  await groupsStore.removeVolunteer(props.groupId, volunteer.user).catch(() => {})
}
</script>

<template>
  <div data-testid="group-volunteers">
    <h2>
      {{ t('groups.volunteers') }}
      <span v-if="volunteers.length" class="fw-normal">({{ volunteers.length }})</span>
    </h2>

    <div v-if="loading" data-testid="group-volunteers-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <p v-else-if="!volunteers.length" data-testid="group-volunteers-empty">
      {{ t('groups.no_volunteers') }}.
    </p>

    <ul v-else class="list-unstyled" data-testid="group-volunteers-list">
      <li
        v-for="volunteer in volunteers"
        :key="volunteer.id"
        class="d-flex align-items-center justify-content-between py-2 border-bottom"
        :data-testid="`group-volunteer-${volunteer.user}`"
      >
        <div class="d-flex align-items-center">
          <img
            :src="volunteer.image || '/images/placeholder-avatar.webp'"
            alt=""
            width="40"
            height="40"
            class="rounded-circle me-2"
          >
          <div>
            <NuxtLink :to="`/profile/${volunteer.user}`" class="fw-bold">{{ volunteer.name }}</NuxtLink>
            <BBadge
              v-if="volunteer.host"
              variant="primary"
              class="ms-1"
              :data-testid="`group-volunteer-host-badge-${volunteer.user}`"
            >
              {{ t('partials.host') }}
            </BBadge>
            <div class="small text-muted" :title="skillNames(volunteer)">
              {{ skillCount(volunteer) }} {{ t('partials.skills', skillCount(volunteer)) }}
            </div>
          </div>
        </div>

        <div v-if="canedit" class="text-nowrap">
          <template v-if="confirmingRemove === volunteer.user">
            <span class="small me-2">{{ t('partials.are_you_sure') }}</span>
            <BButton
              size="sm"
              variant="danger"
              :data-testid="`group-volunteer-remove-confirm-${volunteer.user}`"
              @click="confirmRemove(volunteer)"
            >
              {{ t('partials.yes') }}
            </BButton>
            <BButton size="sm" variant="link" @click="cancelRemove">
              {{ t('partials.cancel') }}
            </BButton>
          </template>
          <template v-else>
            <BButton
              v-if="volunteer.host && candemote"
              size="sm"
              variant="outline-secondary"
              class="me-1"
              :data-testid="`group-volunteer-remove-host-${volunteer.user}`"
              @click="removeHostRole(volunteer)"
            >
              {{ t('groups.remove_host_role') }}
            </BButton>
            <BButton
              v-if="!volunteer.host"
              size="sm"
              variant="outline-secondary"
              class="me-1"
              :data-testid="`group-volunteer-make-host-${volunteer.user}`"
              @click="makeHost(volunteer)"
            >
              {{ t('groups.make_host') }}
            </BButton>
            <BButton
              size="sm"
              variant="outline-danger"
              :data-testid="`group-volunteer-remove-${volunteer.user}`"
              @click="askRemove(volunteer)"
            >
              {{ t('groups.remove_volunteer') }}
            </BButton>
          </template>
        </div>
      </li>
    </ul>

    <div v-if="!loading" class="text-end">
      <a href="#" data-testid="group-volunteers-invite-link" @click.prevent="emit('invite')">
        {{ t('groups.invite_to_group') }}
      </a>
    </div>
  </div>
</template>
