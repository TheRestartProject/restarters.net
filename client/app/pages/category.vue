<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminCrudTable from '~/components/admin/AdminCrudTable.vue'
import { useAdminRefdataStore } from '~/stores/adminRefdata.js'

// /category - resources/views/category/index.blade.php +
// resources/js/components/CategoriesTable.vue, develop's real, mounted
// component (registered as `categories-table` in resources/js/app.js -
// design.md §6.2 Phase D task D4, PR #863's AdminCrudPage.vue prop contract
// is the functional spec for the generic AdminCrudTable.vue this page is
// built on).
//
// Unlike its siblings (brands/skills/tags), CategoryController::index is a
// PUBLIC list view - any authenticated user can see the categories table;
// only the update itself is Administrator-only (enforced server-side by
// API\CategoryController::updateCategoryv2, same as every other admin
// mutation - design.md §4.4). So this page is gated on `auth: true` alone,
// not `role: 'Administrator'` - matching the legacy controller exactly. The
// edit link/modal stays visible to every viewer, same as legacy
// CategoriesTable.vue (which never hid it either); a non-Administrator who
// submits an edit gets the same 403 rendered inline as any other API
// error, not a client-side pre-emptive block.
//
// `:allow-create="false" :allow-delete="false"`: confirmed by reading
// routes/api.php - only GET '/', GET '{id}' and PUT '{id}' exist under
// /categories, no POST or DELETE.
//
// Unlike legacy (which had to build its own cluster-id -> name lookup
// client-side because the old admin-only API didn't join it),
// API\CategoryController::listCategoriesv2/getCategoryv2/updateCategoryv2
// already return `cluster_name` pre-joined on every row - the table column
// below reads it directly, no lookup needed.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('admin.categories') })

const adminStore = useAdminRefdataStore()
const route = useRoute()

const editId = computed(() => {
  const raw = route.query.editId
  const n = Number(raw)
  return raw != null && !Number.isNaN(n) ? n : null
})

onMounted(() => {
  adminStore.fetchClusters().catch(() => {})
})

// Cluster names ("Kitchen and Household Items", ...) are the raw
// clusters.name DB column value, same top-level-i18n-key mechanism as role
// names - see PublicProfileView.vue's doc comment.
const clusterOptions = computed(() => [
  { value: null, text: '—' },
  ...adminStore.clusters.data.map((c) => ({ value: c.id, text: t(c.name) })),
])

// Wrapped in computed() (rather than plain consts) so everything stays
// correct if the locale changes without a full page reload - see
// pages/brands.vue's equivalent comment.
const reliabilityOptions = computed(() => [1, 2, 3, 4, 5, 6].map((n) => ({ value: n, text: t(`admin.reliability-${n}`) })))
const reliabilityLookup = computed(() => Object.fromEntries(reliabilityOptions.value.map((o) => [o.value, o.text])))

// Reliability level -> badge colour, ported verbatim from legacy
// CategoryController::index's $colors map (level 6 - "N/A" - shares level
// 3's amber, and an unrecognised/null level falls back to that same amber).
// Bootstrap's base .badge class supplies the white label text, matching the
// legacy `<span class="badge indicator-N" style="background-color:...">`.
const RELIABILITY_COLORS = {
  1: '#AD2C1C',
  2: '#FF1B00',
  3: '#FFBA00',
  4: '#43B136',
  5: '#26781C',
  6: '#FFBA00',
}
const RELIABILITY_FALLBACK_COLOR = RELIABILITY_COLORS[6]
const reliabilityColor = (level) => RELIABILITY_COLORS[level] || RELIABILITY_FALLBACK_COLOR

// develop's real, mounted counterpart for /category is
// resources/js/components/CategoriesTable.vue (registered as
// `categories-table` in resources/js/app.js and rendered directly by
// resources/views/category/index.blade.php) - "CategoriesPage.vue"/
// "AdminCrudPage.vue" never existed on develop at all; they were this
// branch's OWN pre-Phase-F scaffolding (commit 07e6abd7cc^, the commit
// immediately before this branch's own Phase F commit deleted the legacy
// Blade+Vue2 frontend), so citing them as "the baseline" was citing our own
// unreviewed work as if it were develop. CategoriesTable.vue's b-table
// fields give: "Name" as the column header (not "Category name" - the form
// field below keeps that key, matching category/edit.blade.php's label),
// and an "N/A" fallback for a blank cluster cell (not blank).
const tableFields = computed(() => [
  { key: 'name', label: t('admin.category_name'), sortable: true },
  {
    key: 'cluster_name',
    label: t('admin.category_cluster'),
    sortable: true,
    // cluster_name is the same raw, top-level-i18n-keyed DB string as
    // clusterOptions above - translate it here too rather than rendering
    // it as-is. 'N/A' (untranslated literal, matching CategoriesTable.vue's
    // own `: 'N/A'` fallback verbatim) when there's no cluster.
    formatter: (value) => (value ? t(value) : 'N/A'),
  },
  { key: 'weight', label: t('admin.weight'), sortable: true },
  { key: 'footprint', label: t('admin.co2_footprint'), sortable: true },
  {
    key: 'footprint_reliability',
    label: t('admin.reliability'),
    sortable: true,
    formatter: (value) => (value != null ? reliabilityLookup.value[value] || value : ''),
  },
])

const formFields = computed(() => [
  { key: 'name', label: t('admin.category_name'), type: 'text', required: true, maxLength: 255 },
  { key: 'weight', label: t('admin.weight'), type: 'number', required: false, nullIfEmpty: true },
  { key: 'footprint', label: t('admin.co2_footprint'), type: 'number', required: false, nullIfEmpty: true },
  { key: 'footprint_reliability', label: t('admin.reliability'), type: 'select', required: false, options: reliabilityOptions.value, nullIfEmpty: true },
  { key: 'cluster', label: t('admin.category_cluster'), type: 'select', required: false, options: clusterOptions.value, nullIfEmpty: true },
  { key: 'description_short', label: t('admin.description'), type: 'textarea', required: false, rows: 4, nullIfEmpty: true },
])

const labels = computed(() => ({
  title: t('admin.categories'),
  editTitle: t('admin.edit-category'),
  saveButton: t('admin.save-category'),
  cancel: t('partials.cancel'),
  emptyText: t('admin.no-categories'),
  loadError: t('client.admin.load_error'),
  retry: t('client.dashboard.retry'),
  updateSuccess: t('category.update_success'),
  updateError: t('category.update_error'),
}))
</script>

<template>
  <div class="container py-4" data-testid="category-page">
    <AdminCrudTable
      display-key="name"
      :table-fields="tableFields"
      :form-fields="formFields"
      :labels="labels"
      testid-prefix="category"
      :allow-create="false"
      :allow-delete="false"
      :edit-id="editId"
      :items="adminStore.categories.data"
      :fetch-items="adminStore.fetchCategories"
      :update-item="adminStore.updateCategory"
    >
      <template #cell-footprint_reliability="{ item, value }">
        <span class="badge" :style="{ backgroundColor: reliabilityColor(item.footprint_reliability) }">{{ value }}</span>
      </template>
    </AdminCrudTable>
  </div>
</template>
