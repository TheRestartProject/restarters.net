<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminCrudTable from '~/components/admin/AdminCrudTable.vue'
import { useAdminRefdataStore } from '~/stores/adminRefdata.js'

// /tags - resources/views/tags/index.blade.php +
// resources/js/components/GroupTagsPage.vue (design.md §6.2 Phase D task
// D4, PR #863's AdminCrudPage.vue prop contract is the functional spec).
// Global (cross-network) tags only - network-scoped tags are managed on
// /networks/{id} (design.md §6.2 Phase E task E1). Administrator-only, same
// shape as pages/brands.vue.
//
// Beyond the legacy confirm-delete message, the Tag resource's
// `groups_count` field (how many groups currently have this tag applied)
// drives an extra in-use warning in the delete modal - the legacy Vue
// component didn't have this (its ConfirmModal only ever showed static
// text), but the field is already there for the taking on every row.
//
// parity-v2/admin-and-static.md gap 13: legacy puts Delete on the edit page
// itself (an unconfirmed plain link), not a table-row action with a confirm
// step - AdminCrudTable.vue's row button + confirm modal is KEPT here as an
// intentional safety improvement (and is what surfaces the groups_count
// in-use warning above) rather than reverted to match legacy's unconfirmed
// link. Flagged for a product-owner call per that gap's own suggested
// resolution.
definePageMeta({ auth: true, role: 'Administrator' })

const { t } = useI18n()
useHead({ title: t('admin.group-tags') })

const adminStore = useAdminRefdataStore()
const route = useRoute()

const editId = computed(() => {
  const raw = route.query.editId
  const n = Number(raw)
  return raw != null && !Number.isNaN(n) ? n : null
})

function stripTags(value) {
  if (value == null) return ''
  // Browser parser rather than a regex: avoids SonarCloud S5852's
  // false-positive super-linear-regex warning and is more robust against
  // odd input than any hand-rolled pattern - same approach as legacy
  // GroupTagsPage.vue's own stripTags().
  const doc = new DOMParser().parseFromString(String(value), 'text/html')
  return doc.body.textContent || ''
}

function truncate(value, length) {
  if (value == null) return ''
  return value.length > length ? value.substring(0, length) + '...' : value
}

// Wrapped in computed() (rather than plain consts) so everything stays
// correct if the locale changes without a full page reload - see
// pages/brands.vue's equivalent comment.
const tableFields = computed(() => [
  { key: 'name', label: t('admin.tag-name'), sortable: true },
  { key: 'description', label: t('admin.description'), sortable: false, formatter: (value) => truncate(stripTags(value), 150) },
])

const formFields = computed(() => [
  { key: 'name', label: t('admin.tag-name'), type: 'text', required: true, maxLength: 255 },
  { key: 'description', label: t('admin.description'), type: 'textarea', required: false, rows: 6, maxLength: 1000, nullIfEmpty: true },
])

const labels = computed(() => ({
  title: t('admin.group-tags'),
  createButton: t('admin.create-new-tag'),
  editTitle: t('admin.edit-tag'),
  saveButton: t('admin.save-tag'),
  deleteButton: t('admin.delete-tag'),
  cancel: t('partials.cancel'),
  emptyText: t('admin.no-group-tags'),
  confirmDeleteTitle: t('partials.are_you_sure'),
  loadError: t('client.admin.load_error'),
  retry: t('client.dashboard.retry'),
  createSuccess: t('group-tags.create_success'),
  updateSuccess: t('group-tags.update_success'),
  deleteSuccess: t('group-tags.delete_success'),
  createError: t('group-tags.create_error'),
  updateError: t('group-tags.update_error'),
  deleteError: t('group-tags.delete_error'),
  formatConfirmDelete: (item) => t('admin.confirm_delete_group_tag', { name: item.name }),
  deleteWarning: (item) => (item.groups_count > 0 ? t('client.admin.tag_in_use_warning', { count: item.groups_count }) : ''),
}))
</script>

<template>
  <div class="container py-4" data-testid="tags-page">
    <AdminCrudTable
      display-key="name"
      :table-fields="tableFields"
      :form-fields="formFields"
      :labels="labels"
      testid-prefix="tags"
      :edit-id="editId"
      :items="adminStore.groupTags.data"
      :fetch-items="adminStore.fetchGroupTags"
      :create-item="adminStore.createGroupTag"
      :update-item="adminStore.updateGroupTag"
      :delete-item="adminStore.deleteGroupTag"
    />
  </div>
</template>
