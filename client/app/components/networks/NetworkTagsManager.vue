<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNetworksStore } from '~/stores/networks.js'

// Network tag CRUD for /networks/{id}'s "Group Tags" section (parity-v2/
// networks.md gap #4). Legacy NetworkPage.vue's tags-management block is
// the functional spec: bordered card rows (name + count, description shown
// inline underneath, pencil/trash icon buttons) plus an always-visible
// inline create form below the list - NOT components/admin/AdminCrudTable.
// vue's generic modal-driven create flow + name-as-edit-link + text
// "Delete" button (AdminCrudTable is shared by 5 other reference-data pages
// which is a DIFFERENT shape, so a bespoke component here is the parity
// match - not a shortcut around reworking AdminCrudTable. Checked during the
// deferral-comment audit: the wording read like a dodge, the code does not).
// Edit
// stays a modal here too, matching legacy's own edit-tag b-modal.
const props = defineProps({
  networkId: {
    type: Number,
    required: true,
  },
})

const { t } = useI18n()
const networksStore = useNetworksStore()

const tags = computed(() => networksStore.tags.data)
const loading = computed(() => networksStore.tags.loading)
const error = computed(() => networksStore.tags.error)

const newTagName = ref('')
const newTagDescription = ref('')
const creating = ref(false)
const createError = ref('')

const showEditModal = ref(false)
const editingTag = ref(null)
const editName = ref('')
const editDescription = ref('')
const editSaving = ref(false)
const editError = ref('')

const showDeleteModal = ref(false)
const tagToDelete = ref(null)
const deleting = ref(false)

function retry() {
  networksStore.fetchTags(props.networkId)
}

async function createTag() {
  if (!newTagName.value.trim()) return

  creating.value = true
  createError.value = ''

  try {
    await networksStore.createTag(props.networkId, {
      name: newTagName.value.trim(),
      description: newTagDescription.value.trim() || null,
    })
    newTagName.value = ''
    newTagDescription.value = ''
  } catch (err) {
    createError.value = err?.data?.message || t('networks.tags.create_error')
  } finally {
    creating.value = false
  }
}

function openEdit(tag) {
  editingTag.value = tag
  editName.value = tag.name
  editDescription.value = tag.description || ''
  editError.value = ''
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  editingTag.value = null
}

async function saveEdit() {
  if (!editingTag.value || !editName.value.trim()) return

  editSaving.value = true
  editError.value = ''

  try {
    await networksStore.updateTag(props.networkId, editingTag.value.id, {
      name: editName.value.trim(),
      description: editDescription.value.trim() || null,
    })
    closeEdit()
  } catch (err) {
    editError.value = err?.data?.message || t('networks.tags.edit_error')
  } finally {
    editSaving.value = false
  }
}

function confirmDelete(tag) {
  tagToDelete.value = tag
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  tagToDelete.value = null
}

async function performDelete() {
  if (!tagToDelete.value) return

  deleting.value = true

  try {
    await networksStore.deleteTag(props.networkId, tagToDelete.value.id)
    cancelDelete()
  } catch {
    // Legacy's deleteTag() console.error()s and leaves the modal open
    // silently on failure - matched here rather than inventing new UI.
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div data-testid="network-tags-manager">
    <div v-if="loading" data-testid="network-tags-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 4rem" />
      </div>
    </div>

    <BAlert v-else-if="error" :model-value="true" variant="danger" data-testid="network-tags-load-error">
      <p>{{ t('client.networks.tags_load_error') }}</p>
      <BButton variant="danger" data-testid="network-tags-retry" @click="retry">{{ t('client.dashboard.retry') }}</BButton>
    </BAlert>

    <template v-else>
      <div v-if="!tags.length" class="text-muted mb-2" data-testid="network-tags-empty">
        {{ t('networks.tags.no_tags') }}
      </div>
      <div v-else class="mb-3">
        <div v-for="tag in tags" :key="tag.id" class="tag-item mb-2 p-2 border rounded" :data-testid="`network-tag-${tag.id}`">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong>{{ tag.name }}</strong>
              <span class="text-muted ms-2">({{ tag.groups_count }} {{ t('networks.stats.groups', { count: tag.groups_count }, tag.groups_count) }})</span>
            </div>
            <!-- NetworkPage.vue's plain monochrome pencil/trash icons
                 (18px/20px, no colour fill), not a coloured icon set - `:src`
                 (not a static `src=`) so Vite's SFC asset-url transform
                 doesn't inline these small public-dir svgs as data URIs,
                 matching EventCard.vue's `:src="'/images/clock.svg'"`. -->
            <div class="d-flex align-items-center">
              <BButton variant="link" size="sm" class="p-0 me-2" :data-testid="`network-tag-edit-${tag.id}`" @click="openEdit(tag)">
                <span class="visually-hidden">{{ t('networks.tags.edit') }}</span>
                <img :src="'/images/pencil.svg'" alt="" width="18" height="18">
              </BButton>
              <BButton variant="link" size="sm" class="p-0" :data-testid="`network-tag-delete-${tag.id}`" @click="confirmDelete(tag)">
                <span class="visually-hidden">{{ t('networks.tags.delete') }}</span>
                <img :src="'/images/trash.svg'" alt="" width="20" height="20">
              </BButton>
            </div>
          </div>
          <div v-if="tag.description" class="text-muted small mt-1" :data-testid="`network-tag-description-${tag.id}`">
            {{ tag.description }}
          </div>
        </div>
      </div>

      <BForm data-testid="network-tags-create-form" @submit.prevent="createTag">
        <input
          v-model="newTagName"
          type="text"
          class="form-control form-control-sm mb-2"
          :placeholder="t('networks.tags.new_tag_placeholder')"
          required
          data-testid="network-tags-create-name"
        >
        <input
          v-model="newTagDescription"
          type="text"
          class="form-control form-control-sm mb-2"
          :placeholder="t('networks.tags.description_placeholder')"
          data-testid="network-tags-create-description"
        >
        <BButton type="submit" variant="primary" size="sm" :disabled="!newTagName.trim() || creating" data-testid="network-tags-create-submit">
          {{ t('networks.tags.create') }}
        </BButton>
        <div v-if="createError" class="text-danger small mt-1" data-testid="network-tags-create-error">{{ createError }}</div>
      </BForm>
    </template>

    <BModal
      :model-value="showEditModal"
      :title="t('networks.tags.edit_title')"
      no-footer
      data-testid="network-tags-edit-modal"
      @hide="closeEdit"
    >
      <BFormGroup :label="`${t('networks.tags.name_label')}:`">
        <input v-model="editName" type="text" class="form-control" required data-testid="network-tags-edit-name">
      </BFormGroup>
      <BFormGroup :label="`${t('networks.tags.description_label')}:`">
        <textarea v-model="editDescription" class="form-control" rows="3" data-testid="network-tags-edit-description" />
      </BFormGroup>
      <p v-if="editError" class="text-danger small" data-testid="network-tags-edit-error">{{ editError }}</p>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <BButton variant="outline-secondary" @click="closeEdit">{{ t('partials.cancel') }}</BButton>
        <BButton variant="primary" :disabled="!editName.trim() || editSaving" data-testid="network-tags-edit-submit" @click="saveEdit">
          {{ t('admin.save-tag') }}
        </BButton>
      </div>
    </BModal>

    <BModal
      :model-value="showDeleteModal"
      :title="t('networks.tags.delete_confirm_title')"
      no-footer
      data-testid="network-tags-delete-modal"
      @hide="cancelDelete"
    >
      <p>{{ t('networks.tags.delete_confirm_message', { name: tagToDelete ? tagToDelete.name : '' }) }}</p>
      <p v-if="tagToDelete && tagToDelete.groups_count > 0" class="text-warning" data-testid="network-tags-delete-warning">
        {{ t('networks.tags.delete_warning', { count: tagToDelete.groups_count }, tagToDelete.groups_count) }}
      </p>
      <div class="d-flex justify-content-end gap-2">
        <BButton variant="outline-secondary" @click="cancelDelete">{{ t('partials.cancel') }}</BButton>
        <BButton variant="danger" :disabled="deleting" data-testid="network-tags-delete-confirm" @click="performDelete">
          {{ t('networks.tags.delete') }}
        </BButton>
      </div>
    </BModal>
  </div>
</template>

<style scoped lang="scss">
// $brand-grey from app/assets/css/_variables.scss, hardcoded here as other
// components already do in scoped styles (no global variable import wired
// up for component <style> blocks).
.tag-item {
  background: #f5f7fa;
}
</style>
