<script setup>
import { computed, onMounted, ref } from 'vue'
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

const editTitle = computed(() => (editingRole.value ? `${t('admin.edit-role')}: ${editingRole.value.name}` : t('admin.edit-role')))

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

    <div v-else class="table-responsive">
      <table class="table" data-testid="roles-table">
        <thead>
          <tr>
            <th>{{ t('admin.role_id') }}</th>
            <th>{{ t('admin.role') }}</th>
            <th>{{ t('admin.role_permissions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="role in adminStore.roles.data" :key="role.id" :data-testid="`roles-row-${role.id}`">
            <td>{{ role.id }}</td>
            <td>
              <a href="#" :data-testid="`roles-edit-link-${role.id}`" @click.prevent="openEditModal(role)">{{ role.name }}</a>
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
