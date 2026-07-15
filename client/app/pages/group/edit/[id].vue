<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useGroupsStore } from '~/stores/groups.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'
import GroupForm from '~/components/groups/GroupForm.vue'
import TusImageUpload from '~/components/forms/TusImageUpload.vue'

// /group/edit/[id] - resources/views/group/edit.blade.php +
// GroupAddEditPage.vue (design.md §6.2 B6 task brief). Adds, over the
// create page: image upload (tus, see TusImageUpload.vue) and
// archive/delete (permission-gated, same DELETE /api/v2/groups/{id}
// contract + can_see_delete/can_perform_delete flags the group view page
// already uses - api-contracts-phase-b.md B2, not yet implemented
// server-side).
//
// The legacy page's admin-only "Group log" audit tab
// (App\Helpers\Fixometer::hasRole($user,'Administrator') &&
// $group->audits, resources/views/partials/log-accordion.blade.php) is
// deliberately omitted - there is no v2 (or any API) endpoint at all for a
// group's audit trail, only the Blade controller reading $group->audits
// directly. Recorded in docs/nuxt-migration/api-gaps.md B6.
definePageMeta({ auth: true })

const { t } = useI18n()
const route = useRoute()
const sessionStore = useSessionStore()
const groupsStore = useGroupsStore()

const id = computed(() => Number(route.params.id))
const group = computed(() => groupsStore.current.data)
const permissions = computed(() => group.value?.permissions || {})
const canEdit = computed(() => !!permissions.value.can_edit)
const canSeeDelete = computed(() => !!permissions.value.can_see_delete)
const canPerformDelete = computed(() => !!permissions.value.can_perform_delete)

// Role ints per app/Role.php: Root(1) has every role, Administrator(2) is
// the only other role that can change a group's network affiliation
// (legacy GroupAddEditPage's canNetwork = Auth::user()->hasRole('Administrator')).
const isAdmin = computed(() => sessionStore.user?.role === 1 || sessionStore.user?.role === 2)

const { uploadedImageUrl } = useUploadedImageUrl()
const groupImageUrl = computed(() => uploadedImageUrl(group.value?.image))
const updatedMessage = ref('')
const imageError = ref('')
const confirmingDelete = ref(false)
const deleting = ref(false)

useHead({ title: computed(() => (group.value ? `${t('groups.editing')} ${group.value.name}` : t('groups.editing'))) })

function load() {
  groupsStore.fetchCurrent(id.value)
}

onMounted(load)

function retry() {
  load()
}

function onUpdated() {
  updatedMessage.value = t('groups.edit_group_save_changes')
  load()
}

async function onImageUploaded({ uploadKey }) {
  imageError.value = ''
  try {
    await groupsStore.uploadGroupImage(id.value, uploadKey)
  } catch {
    imageError.value = t('client.groups.image_upload_error')
  }
}

function onImageUploadError(message) {
  imageError.value = message
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
    await navigateTo(`/group/view/${id.value}`)
  } catch {
    // Store already toasted the error.
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div class="container py-4" data-testid="group-edit-page">
    <div v-if="groupsStore.current.loading" data-testid="group-edit-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="groupsStore.current.error" :model-value="true" variant="danger" data-testid="group-edit-error">
      <p>{{ t('client.groups.load_error') }}</p>
      <BButton variant="danger" data-testid="group-edit-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <BAlert v-else-if="group && !canEdit" :model-value="true" variant="danger" data-testid="group-edit-forbidden">
      {{ t('client.groups.edit_forbidden') }}
    </BAlert>

    <template v-else-if="group">
      <h1>
        {{ t('groups.editing') }}
        <NuxtLink :to="`/group/view/${id}`" class="headlink" data-testid="group-edit-view-link">{{ group.name }}</NuxtLink>
      </h1>
      <p>{{ t('groups.edit_group_text') }}</p>

      <BAlert v-if="updatedMessage" :model-value="true" variant="success" dismissible data-testid="group-edit-success" @dismissed="updatedMessage = ''">
        {{ updatedMessage }}
      </BAlert>

      <BFormGroup :label="`${t('groups.group_image')}:`" class="mb-4">
        <TusImageUpload :current-image-url="groupImageUrl" @uploaded="onImageUploaded" @upload-error="onImageUploadError" />
        <div v-if="imageError" class="text-danger" data-testid="group-edit-image-error">{{ imageError }}</div>
      </BFormGroup>

      <GroupForm
        :group-id="id"
        :initial-group="group"
        :permissions="permissions"
        :is-admin="isAdmin"
        @updated="onUpdated"
      />

      <div v-if="canSeeDelete" class="mt-4 pt-3 border-top">
        <template v-if="confirmingDelete">
          <span class="me-2">{{ t('groups.delete_group_confirm', { name: group.name }) }}</span>
          <BButton variant="danger" :disabled="deleting" data-testid="group-edit-delete-confirm" @click="confirmDelete">
            {{ t('partials.yes') }}
          </BButton>
          <BButton variant="link" @click="cancelDelete">{{ t('partials.cancel') }}</BButton>
        </template>
        <BButton
          v-else
          variant="outline-danger"
          :disabled="!canPerformDelete"
          data-testid="group-edit-delete"
          @click="askDelete"
        >
          {{ t('groups.delete_group') }}
        </BButton>
      </div>
    </template>
  </div>
</template>
