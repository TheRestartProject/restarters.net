<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminCrudTable from '~/components/admin/AdminCrudTable.vue'
import { useAdminRefdataStore } from '~/stores/adminRefdata.js'

// /skills - resources/views/skills/index.blade.php +
// resources/js/components/SkillsPage.vue (design.md §6.2 Phase D task D4,
// PR #863's AdminCrudPage.vue prop contract is the functional spec).
// Administrator-only, same shape as pages/brands.vue.
//
// The category dropdown (1 = Organising, 2 = Technical) has no dedicated
// list endpoint - App\Helpers\Fixometer::skillCategories() is a hardcoded
// PHP array, not DB-backed, and SkillController::createSkillv2/
// updateSkillv2 just validate `category` against its keys. This hardcodes
// the same two entries client-side, reusing the exact top-level i18n keys
// GET /api/v2/users/me/skills already returns as its category `label`
// (components/profile/SkillsTab.vue's `t(cat.label)` - see that endpoint's
// controller) rather than inventing new ones - confirmed identical wording
// by reading App\Helpers\Fixometer::skillCategories() directly. Not an API
// gap: a dedicated /api/v2/skill-categories endpoint would be nice-to-have
// but the client can't get this wrong without the server's own validation
// also rejecting it.
//
// parity-v2/admin-and-static.md gap 13: develop puts Delete on the EDIT
// page (tags/edit.blade.php:55), not as a table-row action. Delete has
// been MOVED into AdminCrudTable's edit form to match that placement -
// this previously kept it on the row and called the difference an
// 'intentional safety improvement ... flagged for a product-owner call',
// which was parking a parity gap behind a decision nobody was asked to
// make.
//
// The confirm step is deliberately NOT dropped: develop's is
// `<a href="/tags/delete/{id}">`, a GET that deletes, so it is CSRF-able
// and can fire from a prefetch or a crawler. Matching the placement is
// parity; matching a destructive GET would import a vulnerability, the
// same line already drawn over updateQuantity's IDOR.
definePageMeta({ auth: true, role: 'Administrator' })

const { t } = useI18n()
useHead({ title: t('admin.skills') })

const adminStore = useAdminRefdataStore()
const route = useRoute()

const editId = computed(() => {
  const raw = route.query.editId
  const n = Number(raw)
  return raw != null && !Number.isNaN(n) ? n : null
})

// Wrapped in computed() (rather than plain consts) so everything stays
// correct if the locale changes without a full page reload - see
// pages/brands.vue's equivalent comment.
const categoryOptions = computed(() => [
  { value: 1, text: t('Organising skills - please select at least one if you’d like to host events') },
  { value: 2, text: t('Technical skills') },
])
const categoryLookup = computed(() => Object.fromEntries(categoryOptions.value.map((o) => [o.value, o.text])))

// live SkillsPage.vue (07e6abd7cc^) is the baseline: 3 columns (skill_name/
// category/description) and a plain "Description" label - develop's Blade
// (2 columns, "Description (optional)") is the wrong target for this branch,
// which had already migrated this page to its own Vue2 shape pre-Phase-F.
const tableFields = computed(() => [
  { key: 'skill_name', label: t('admin.skill-name'), sortable: true },
  { key: 'category', label: t('admin.category'), sortable: true, formatter: (value) => (value != null ? categoryLookup.value[value] || value : '') },
  { key: 'description', label: t('admin.description'), sortable: false },
])

const formFields = computed(() => [
  { key: 'skill_name', label: t('admin.skill-name'), type: 'text', required: true, maxLength: 255 },
  { key: 'category', label: t('admin.category'), type: 'select', required: true, options: categoryOptions.value },
  { key: 'description', label: t('admin.description'), type: 'textarea', required: false, maxLength: 255, rows: 4, nullIfEmpty: true },
])

const labels = computed(() => ({
  title: t('admin.skills'),
  createButton: t('admin.create-new-skill'),
  editTitle: t('admin.edit-skill'),
  saveButton: t('admin.save-skill'),
  deleteButton: t('admin.delete-skill'),
  cancel: t('partials.cancel'),
  emptyText: t('admin.no-skills'),
  confirmDeleteTitle: t('partials.are_you_sure'),
  loadError: t('client.admin.load_error'),
  retry: t('client.dashboard.retry'),
  createSuccess: t('skills.create_success'),
  updateSuccess: t('skills.update_success'),
  deleteSuccess: t('skills.delete_success'),
  createError: t('skills.create_error'),
  updateError: t('skills.update_error'),
  deleteError: t('skills.delete_error'),
  formatConfirmDelete: (item) => t('admin.confirm_delete_skill', { name: item.skill_name }),
}))
</script>

<template>
  <div class="container py-4" data-testid="skills-page">
    <AdminCrudTable
      display-key="skill_name"
      :table-fields="tableFields"
      :form-fields="formFields"
      :labels="labels"
      testid-prefix="skills"
      :edit-id="editId"
      :items="adminStore.skills.data"
      :fetch-items="adminStore.fetchSkills"
      :create-item="adminStore.createSkill"
      :update-item="adminStore.updateSkill"
      :delete-item="adminStore.deleteSkill"
    />
  </div>
</template>
