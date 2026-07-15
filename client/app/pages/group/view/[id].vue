<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'
import GroupJoinButton from '~/components/groups/GroupJoinButton.vue'
import GroupStats from '~/components/groups/GroupStats.vue'
import GroupVolunteers from '~/components/groups/GroupVolunteers.vue'
import GroupEventsList from '~/components/groups/GroupEventsList.vue'
import GroupInviteModal from '~/components/groups/GroupInviteModal.vue'

// /group/view/[id] - resources/views/group/view.blade.php +
// resources/js/components/GroupPage.vue (+ GroupHeading/GroupActions/
// GroupDescription/GroupVolunteers/GroupStats/GroupEvents, folded PR #892
// version) are the functional spec (design.md §6.2 B5 task brief).
definePageMeta({ auth: true })

const { t } = useI18n()
const route = useRoute()
const groupsStore = useGroupsStore()

const id = computed(() => Number(route.params.id))

const group = computed(() => groupsStore.current.data)
const permissions = computed(() => group.value?.permissions || {})
const canedit = computed(() => !!permissions.value.can_edit)
const candemote = computed(() => !!permissions.value.can_demote)
const canSeeDelete = computed(() => !!permissions.value.can_see_delete)
const canPerformDelete = computed(() => !!permissions.value.can_perform_delete)
const isMember = computed(() => groupsStore.isMember(id.value))
const canInvite = computed(() => canedit.value || isMember.value)

const { uploadedImageUrl } = useUploadedImageUrl()
const groupImage = computed(() => uploadedImageUrl(group.value?.image) || '/images/placeholder-avatar.png')
const location = computed(() => {
  const loc = group.value?.location
  if (!loc) return null
  return typeof loc === 'string' ? loc : loc.location
})

const showInvite = ref(false)
const confirmingDelete = ref(false)
const deleting = ref(false)

useHead({ title: computed(() => group.value?.name || t('groups.groups')) })

function retry() {
  load()
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
    await groupsStore.deleteGroup(id.value)
    await navigateTo('/group')
  } catch {
    // Store already toasted the error.
  } finally {
    deleting.value = false
  }
}

function load() {
  groupsStore.fetchCurrent(id.value)
  groupsStore.fetchStats(id.value)
  groupsStore.fetchEvents(id.value)
  groupsStore.fetchVolunteers(id.value)
}

onMounted(load)
</script>

<template>
  <div class="container py-4" data-testid="group-view-page">
    <div v-if="groupsStore.current.loading" data-testid="group-view-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert
      v-else-if="groupsStore.current.error"
      :model-value="true"
      variant="danger"
      data-testid="group-view-error"
    >
      <p>{{ t('client.groups.load_error') }}</p>
      <BButton variant="danger" data-testid="group-view-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else-if="group">
      <header class="d-flex flex-wrap justify-content-between mb-4" data-testid="group-view-header">
        <div class="d-flex">
          <img :src="groupImage" alt="" class="groupImage me-3" data-testid="group-view-image">
          <div>
            <h1 data-testid="group-view-name">{{ group.name }}</h1>
            <BBadge v-if="group.archived_at" variant="secondary" pill data-testid="group-view-archived">
              {{ t('groups.archived_group') }}
            </BBadge>
            <div v-if="group.tags && group.tags.length" class="mb-2">
              <BBadge v-for="tag in group.tags" :key="tag.id" variant="info" pill class="me-1">
                {{ tag.name }}
              </BBadge>
            </div>
            <div v-if="location" data-testid="group-view-location">{{ location }}</div>
            <a v-if="group.website" :href="group.website" target="_blank" rel="noopener" data-testid="group-view-website">
              {{ t('groups.website') }}
            </a>
          </div>
        </div>

        <div class="d-flex align-items-start gap-2 flex-wrap">
          <GroupJoinButton :group-id="id" :group-name="group.name" :is-member="isMember" />
          <NuxtLink
            v-if="canedit"
            :to="`/group/edit/${id}`"
            class="btn btn-outline-primary"
            data-testid="group-view-edit"
          >
            {{ t('groups.edit_group') }}
          </NuxtLink>
          <BButton
            v-if="canInvite"
            variant="outline-primary"
            data-testid="group-view-invite"
            @click="showInvite = true"
          >
            {{ t('groups.invite_volunteers') }}
          </BButton>
          <template v-if="canSeeDelete">
            <template v-if="confirmingDelete">
              <span class="small align-self-center">{{ t('groups.delete_group_confirm', { name: group.name }) }}</span>
              <BButton
                variant="danger"
                :disabled="deleting"
                data-testid="group-view-delete-confirm"
                @click="confirmDelete"
              >
                {{ t('partials.yes') }}
              </BButton>
              <BButton variant="link" @click="cancelDelete">{{ t('partials.cancel') }}</BButton>
            </template>
            <BButton
              v-else
              variant="outline-danger"
              :disabled="!canPerformDelete"
              data-testid="group-view-delete"
              @click="askDelete"
            >
              {{ t('groups.delete_group') }}
            </BButton>
          </template>
        </div>
      </header>

      <section class="mb-4" data-testid="group-view-description">
        <template v-if="group.description">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div v-html="group.description" />
        </template>
        <p v-else class="text-muted" data-testid="group-view-description-empty">
          {{ t('groups.about_none') }}
        </p>

        <p v-if="group.phone" class="fw-bold" data-testid="group-view-phone">
          {{ t('groups.field_phone') }}:
          <a :href="`tel:${group.phone}`">{{ group.phone }}</a>
        </p>
        <p v-if="group.email" data-testid="group-view-email">
          <a :href="`mailto:${group.email}`">{{ group.email }}</a>
        </p>
      </section>

      <div class="d-flex flex-wrap">
        <div class="w-100 w-md-50 pe-md-3">
          <GroupVolunteers
            :group-id="id"
            :volunteers="groupsStore.volunteers.data"
            :loading="groupsStore.volunteers.loading"
            :canedit="canedit"
            :candemote="candemote"
            @invite="showInvite = true"
          />
        </div>
      </div>

      <hr>

      <GroupStats
        :stats="groupsStore.stats.data"
        :loading="groupsStore.stats.loading"
        :error="!!groupsStore.stats.error"
      />

      <hr>

      <GroupEventsList :events="groupsStore.events.data" :loading="groupsStore.events.loading" />

      <GroupInviteModal :show="showInvite" :group-id="id" @close="showInvite = false" />
    </template>
  </div>
</template>
