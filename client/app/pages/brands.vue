<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminCrudTable from '~/components/admin/AdminCrudTable.vue'
import { useAdminRefdataStore } from '~/stores/adminRefdata.js'

// /brands - resources/views/brands/index.blade.php +
// resources/js/components/BrandsPage.vue (design.md §6.2 Phase D task D4,
// PR #863's AdminCrudPage.vue prop contract is the functional spec).
// Administrator-only (BrandsController::index redirects non-admins to
// /user/forbidden; GET /api/v2/brands itself is public, but create/update/
// delete 403 for anyone else - API\BrandController).
//
// `?editId=N` reproduces the legacy `/brands/edit/{id}` bookmark as a query
// param instead of a separate path route - see AdminCrudTable.vue's own doc
// comment and docs/nuxt-migration/api-gaps.md Phase D.
//
// live BrandsPage.vue (07e6abd7cc^) is the baseline, not develop's older
// Blade (which predates this branch's own Blade->Vue2 admin migration and
// never got a delete UI at all) - BrandsPage.vue DOES wire a real delete
// button + ConfirmModal, same shape as its skills/tags siblings.
definePageMeta({ auth: true, role: 'Administrator' })

const { t } = useI18n()
useHead({ title: t('admin.brand') })

const adminStore = useAdminRefdataStore()
const route = useRoute()

const editId = computed(() => {
  const raw = route.query.editId
  const n = Number(raw)
  return raw != null && !Number.isNaN(n) ? n : null
})

// Wrapped in computed() (rather than plain consts) so the labels stay
// correct if the locale changes without a full page reload - matches how
// e.g. components/profile/AdminSettingsTab.vue re-resolves its own labels
// reactively, even though the locale switcher itself (design.md §6.2 Phase
// E task E4) hasn't landed yet.
const tableFields = computed(() => [{ key: 'brand_name', label: t('admin.brand-name'), sortable: true }])

const formFields = computed(() => [{ key: 'brand_name', label: t('admin.brand-name'), type: 'text', required: true, maxLength: 255 }])

const labels = computed(() => ({
  title: t('admin.brand'),
  createButton: t('admin.create-new-brand'),
  editTitle: t('admin.edit-brand'),
  saveButton: t('admin.save-brand'),
  // partials.delete does not exist in any locale - this rendered the
  // literal string "partials.delete" on the button. Its siblings use
  // per-entity keys (admin.delete-skill, admin.delete-tag).
  deleteButton: t('admin.delete-brand'),
  cancel: t('partials.cancel'),
  emptyText: t('admin.no-brands'),
  confirmDeleteTitle: t('partials.are_you_sure'),
  loadError: t('client.admin.load_error'),
  retry: t('client.dashboard.retry'),
  createSuccess: t('brands.create_success'),
  updateSuccess: t('brands.update_success'),
  deleteSuccess: t('brands.delete_success'),
  createError: t('brands.create_error'),
  updateError: t('brands.update_error'),
  deleteError: t('brands.delete_error'),
  formatConfirmDelete: (item) => t('admin.confirm_delete_brand', { name: item.brand_name }),
}))
</script>

<template>
  <div class="container py-4" data-testid="brands-page">
    <AdminCrudTable
      display-key="brand_name"
      :table-fields="tableFields"
      :form-fields="formFields"
      :labels="labels"
      testid-prefix="brands"
      :allow-delete="false"
      :edit-id="editId"
      :items="adminStore.brands.data"
      :fetch-items="adminStore.fetchBrands"
      :create-item="adminStore.createBrand"
      :update-item="adminStore.updateBrand"
      :delete-item="adminStore.deleteBrand"
    />
  </div>
</template>
