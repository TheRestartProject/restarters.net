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

    <!-- develop's GroupVolunteers.vue .maxheight: cap the roster so a large
         group's volunteer list scrolls internally instead of pushing the
         rest of the page down. -->
    <div v-else class="maxheight" data-testid="group-volunteers-scroll">
      <ul class="list-unstyled mb-0" data-testid="group-volunteers-list">
        <li
          v-for="volunteer in volunteers"
          :key="volunteer.id"
          class="d-flex align-items-center justify-content-between py-2 border-bottom"
          :data-testid="`group-volunteer-${volunteer.user}`"
        >
          <div class="d-flex align-items-center">
            <NuxtLink :to="`/profile/${volunteer.user}`" :data-testid="`group-volunteer-avatar-link-${volunteer.user}`">
              <img
                :src="volunteer.image || '/images/placeholder-avatar.webp'"
                alt=""
                width="40"
                height="40"
                class="rounded-circle me-2 volunteer-avatar"
              >
            </NuxtLink>
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
              <div class="small text-muted d-flex align-items-center" :title="skillNames(volunteer)">
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 11 11"
                  xmlns="http://www.w3.org/2000/svg"
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  aria-hidden="true"
                  class="skill-star me-1"
                  :class="{ 'skill-star--faded': !skillCount(volunteer) }"
                >
                  <path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z" />
                </svg>
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
    </div>

    <div v-if="!loading" class="text-end">
      <a href="#" data-testid="group-volunteers-invite-link" @click.prevent="emit('invite')">
        {{ t('groups.invite_to_group') }}
      </a>
    </div>
  </div>
</template>

<style scoped>
/* develop's GroupVolunteers.vue .maxheight: a tall roster scrolls inside
   this box rather than growing the page. */
.maxheight {
  max-height: 240px;
  overflow-y: auto;
  overflow-x: hidden;
}

/* develop's GroupVolunteer.vue .profile: 40x40 avatar with a thin border. */
.volunteer-avatar {
  border: 1px solid #000;
}

/* develop's GroupVolunteer.vue .star/.faded. */
.skill-star {
  color: #6c757d;
}

.skill-star--faded {
  opacity: 0.5;
}
</style>
