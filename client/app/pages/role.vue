<script setup>
import { computed, onMounted, ref } from 'vue'
import { sortAriaValue, sortHintKey } from '~/composables/useSortAria.js'
import { useI18n } from 'vue-i18n'
import { useAdminRefdataStore } from '~/stores/adminRefdata.js'

// /role - resources/views/role/all.blade.php +
// resources/js/components/RolesPage.vue (design.md §6.2 Phase D task D4).
// Administrator-only (RoleController::index and every /api/v2/roles* /
// /api/v2/permissions endpoint alike - API\RoleController - all 403/redirect
// non-admins).
//
// Deliberately NOT built on AdminCrudTable.vue, unlike its four siblings:
// roles cannot be created, renamed or deleted (RoleController exposes no
// such routes - confirmed by reading routes/api.php: only GET '/roles',
// GET '/roles/{id}', PUT '/roles/{id}/permissions', GET '/permissions'), and
// the one thing that IS editable - a role's permission set - is a
// checkbox matrix against a separate `permissions` catalogue, not a
// text-field-per-row form. Forcing that shape through AdminCrudTable's
// single-record formFields contract would need a bespoke field `type`
// (multi-select-against-a-different-list-with-a-different-payload-key)
// that no other reference-data resource needs, so this page reimplements
// the legacy RolesPage.vue's table+matrix directly instead - a design
// decision, not a missing generic capability.
definePageMeta({ auth: true, role: 'Administrator' })

const { t } = useI18n()
useHead({ title: t('admin.roles') })

const adminStore = useAdminRefdataStore()
const route = useRoute()

const editId = computed(() => {
  const raw = route.query.editId
  const n = Number(raw)
  return raw != null && !Number.isNaN(n) ? n : null
})

const loading = ref(true)
const loadError = ref(null)

const showEdit = ref(false)
const editingRole = ref(null)
const selectedPermissions = ref([])
const saving = ref(false)
const editError = ref('')
const feedback = ref('')
const feedbackVariant = ref('success')

// Role names ("Restarter", "Administrator", ...) are the raw roles.role DB
// column value - see PublicProfileView.vue's doc comment for why t() (not
// plain interpolation) is required: those literal strings are themselves
// top-level i18n keys (lang/en.json et al, exported flat into
// i18n/locales/{en,fr,fr-BE}.json), translating e.g. Restarter -> "Repairer".
const editTitle = computed(() => (editingRole.value ? `${t('admin.edit-role')}: ${t(editingRole.value.name)}` : t('admin.edit-role')))

// live RolesPage.vue (07e6abd7cc^) marks id/name sortable but
// permissions_list explicitly `sortable: false` (it's a display-only
// comma-joined string, not develop's dead RolesTable.vue, which sorted all
// three) - reimplemented locally (this page isn't built on AdminCrudTable.vue,
// see the doc comment above), same sort-ascending-then-toggle-descending
// pattern as that component's own sortByColumn/sortIndicator.
const sortKey = ref(null)
const sortDesc = ref(false)

// aria-sort on the <th>; the hint on the control. See composables/useSortAria.js.
const sortAria = (key) => sortAriaValue(key, sortKey.value, sortDesc.value)
const sortHint = (key) => t(sortHintKey(key, sortKey.value, sortDesc.value))

const sortedRoles = computed(() => {
  if (!sortKey.value) return adminStore.roles.data

  const key = sortKey.value
  const sorted = [...adminStore.roles.data].sort((a, b) => {
    const av = a[key]
    const bv = b[key]
    if (av == null && bv == null) return 0
    if (av == null) return -1
    if (bv == null) return 1
    if (typeof av === 'number' && typeof bv === 'number') return av - bv
    return String(av).localeCompare(String(bv))
  })
  return sortDesc.value ? sorted.reverse() : sorted
})

function sortIndicator(key) {
  if (sortKey.value !== key) return ''
  return sortDesc.value ? '▼' : '▲'
}

function sortByColumn(key) {
  if (sortKey.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortKey.value = key
    sortDesc.value = false
  }
}

function openEditModal(role) {
  editingRole.value = role
  selectedPermissions.value = [...(role.permissions || [])]
  editError.value = ''
  showEdit.value = true
}

function closeEditModal() {
  showEdit.value = false
  editingRole.value = null
  selectedPermissions.value = []
  editError.value = ''
}

function togglePermission(id) {
  selectedPermissions.value = selectedPermissions.value.includes(id)
    ? selectedPermissions.value.filter((p) => p !== id)
    : [...selectedPermissions.value, id]
}

async function saveRolePermissions() {
  if (!editingRole.value) return

  saving.value = true
  editError.value = ''

  try {
    await adminStore.updateRolePermissions(editingRole.value.id, selectedPermissions.value)
    feedback.value = t('admin.role_update_success')
    feedbackVariant.value = 'success'
    showEdit.value = false
  } catch (err) {
    editError.value = err?.data?.message || t('admin.role_update_error')
  } finally {
    saving.value = false
  }
}

async function load() {
  loading.value = true
  loadError.value = null

  try {
    await Promise.all([adminStore.fetchRoles(), adminStore.fetchPermissions()])

    if (editId.value != null) {
      const target = adminStore.roles.data.find((r) => r.id === editId.value)
      if (target) openEditModal(target)
    }
  } catch (err) {
    loadError.value = err
  } finally {
    loading.value = false
  }
}

function retry() {
  load()
}

onMounted(load)
</script>

<template>
  <div class="container py-4" data-testid="role-page">
    <BAlert :model-value="!!feedback" :variant="feedbackVariant" dismissible data-testid="roles-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <h1 class="mb-3">{{ t('admin.roles') }}</h1>

    <div v-if="loading" data-testid="role-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="loadError" :model-value="true" variant="danger" data-testid="role-load-error">
      <p>{{ t('client.admin.load_error') }}</p>
      <BButton variant="danger" data-testid="role-retry" @click="retry">{{ t('client.dashboard.retry') }}</BButton>
    </BAlert>

    <!-- CategoriesTable.vue:2 / RolesTable.vue:2 / brands|skills|tags
         index.blade: `class="table-responsive table-section"`.
         `.table-section` is _tables.scss's white/20px-padding/1px-border/
         6px-shadow panel - already ported here, just never applied, so
         every admin table rendered flat on the page background. -->
    <div v-else class="table-responsive table-section">
      <table class="table table-striped table-hover" data-testid="roles-table">
        <thead>
          <tr>
            <th :aria-sort="sortAria('id')">
              <button type="button" class="sort-header" data-testid="roles-table-sort-id" @click="sortByColumn('id')">
                {{ t('admin.role_id') }} {{ sortIndicator('id') }}
                <span class="visually-hidden">{{ sortHint('id') }}</span>
              </button>
            </th>
            <th :aria-sort="sortAria('name')">
              <button type="button" class="sort-header" data-testid="roles-table-sort-name" @click="sortByColumn('name')">
                {{ t('admin.role') }} {{ sortIndicator('name') }}
                <span class="visually-hidden">{{ sortHint('name') }}</span>
              </button>
            </th>
            <th>{{ t('admin.role_permissions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="role in sortedRoles" :key="role.id" :data-testid="`roles-row-${role.id}`">
            <td>{{ role.id }}</td>
            <td>
              <a href="#" :data-testid="`roles-edit-link-${role.id}`" @click.prevent="openEditModal(role)">{{ t(role.name) }}</a>
            </td>
            <td class="text-muted">{{ role.permissions_list }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <BModal :model-value="showEdit" :title="editTitle" no-footer data-testid="roles-edit-modal" @hide="closeEditModal">
      <p v-if="editError" class="text-danger" data-testid="roles-edit-error">{{ editError }}</p>
      <div v-if="editingRole">
        <p>{{ t('admin.role_permissions_help') }}</p>
        <div data-testid="roles-edit-permissions">
          <div v-for="permission in adminStore.permissions.data" :key="permission.id" class="form-check">
            <input
              :id="`role-permission-${permission.id}`"
              type="checkbox"
              class="form-check-input"
              :checked="selectedPermissions.includes(permission.id)"
              :data-testid="`role-permission-${permission.id}`"
              @change="togglePermission(permission.id)"
            >
            <label class="form-check-label" :for="`role-permission-${permission.id}`">{{ permission.name }}</label>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <BButton variant="outline-secondary" data-testid="roles-edit-cancel" @click="closeEditModal">{{ t('partials.cancel') }}</BButton>
        <BButton variant="primary" :disabled="saving" data-testid="roles-edit-save" @click="saveRolePermissions">
          {{ t('admin.save-role') }}
        </BButton>
      </div>
    </BModal>
  </div>
</template>

<style scoped lang="scss">
.sort-header {
  background: none;
  border: 0;
  padding: 0;
  font-weight: bold;
  cursor: pointer;
}
</style>
