<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'
import { useClipboard } from '~/composables/useClipboard.js'
import { useUsersStore } from '~/stores/users.js'
import { useAdminRefdataStore } from '~/stores/adminRefdata.js'
import AdminSettingsTab from '~/components/profile/AdminSettingsTab.vue'

// /user/all - resources/views/user/all.blade.php +
// resources/js/components/UsersPage.vue (design.md §6.2 Phase D task D3,
// PR #866's API + Vue page are the functional spec). Administrator-only
// (GET /api/v2/users itself 403s for anyone else - UserController::
// listUsersv2 - this is UX-level gating on top of that, per design.md §4.4).
//
// Unlike /group/all, this list is entirely server-filtered/sorted/paginated
// (UserController::listUsersv2 already does all of it) - the page just maps
// its filter/sort/page state to query params and re-fetches, it does not
// slice anything client-side.
//
// The "Edit role" button per row opens AdminSettingsTab.vue (already built
// for D1's /profile/edit/[[id]] Account tab) in a modal, exactly the "role
// editor modal -> PATCH /users/{id}/admin-settings" the task brief asks
// for - reusing it rather than re-implementing the role/groups/preferences/
// permissions form a second time. The "Create new user" button/modal (gap
// 13) ports `includes/modals/create-user.blade.php` onto POST /api/v2/users
// (stores/users.js#createUser) - see submitCreate() below. The Permission
// filter (gap 24) reuses the same permissions catalogue as
// AdminSettingsTab's checkbox matrix (useAdminRefdataStore, already fetched
// for /role's RolesPage) rather than duplicating a fetch in this store.
//
// parity-v2/admin-and-static.md gap 8 (distinct numbering from the gaps
// referenced just above, which are from an earlier findings pass): legacy's
// user list has no per-row quick-role-edit action, only the Name link to a
// full /user/edit/{id} page. KEPT here rather than removed - the Name link
// already reaches the same role editor (ProfileTabs.vue's AdminSettingsTab,
// shown to admins on /profile/edit/{id}), so this button is a redundant
// but non-regressive shortcut, not a capability legacy lacks. Flagged for a
// product-owner call per that gap's own suggested resolution.
definePageMeta({ auth: true, role: 'Administrator' })

const { t, tm } = useI18n()
useHead({ title: t('users.title') })

const { hasRole } = useAuth()
const { copy } = useClipboard()
const usersStore = useUsersStore()
const adminRefdataStore = useAdminRefdataStore()

// Belt-and-braces UX-level gate on top of the page-level Administrator-only
// definePageMeta above (same convention as ProfileTabs.vue's isAdmin prop) -
// GET/POST /api/v2/users both 403 server-side regardless.
const isAdmin = computed(() => hasRole('Administrator'))

const filters = reactive({ name: '', email: '', location: '', country: '', role: '', permissions: [] })
const appliedFilters = reactive({ name: '', email: '', location: '', country: '', role: '', permissions: [] })
const sortBy = ref('')
const sortDesc = ref(false)

const editingUserId = ref(null)
const editingUserName = ref('')

// Gap 6: legacy's filter <aside> is a Bootstrap collapse, hidden by default
// below the md breakpoint and toggled by a "Reveal filters" button (with an
// inline close control inside the panel) - this page rendered it as an
// always-visible, non-collapsible block on every screen size.
const filtersOpen = ref(false)

const columns = [
  { key: 'name', labelKey: 'users.name', sortKey: 'name' },
  { key: 'email', labelKey: 'users.email', sortKey: 'email' },
  { key: 'role_name', labelKey: 'users.role', sortKey: 'role' },
  { key: 'location', labelKey: 'users.location', sortKey: 'location' },
  { key: 'country_name', labelKey: 'users.country', sortKey: 'country' },
  { key: 'groups_count', labelKey: 'users.groups', sortKey: null },
  { key: 'created_at', labelKey: 'users.joined', sortKey: 'created_at' },
  { key: 'last_login_at', labelKey: 'users.last_login', sortKey: 'updated_at' },
]

// tm() returns compiled message ASTs (not strings) for precompiled JSON
// locales, so resolve each name through t() - same pattern as
// pages/user/register.vue's country list.
const countryOptions = computed(() => {
  const options = Object.keys(tm('countries') || {}).map((code) => ({
    value: code,
    text: t(`countries.${code}`),
  }))
  options.sort((a, b) => a.text.localeCompare(b.text))
  return [{ value: '', text: '' }, ...options]
})

// Role names ("Restarter", "Administrator", ...) are the raw roles.role DB
// column value - see PublicProfileView.vue's doc comment for why t() is
// required (they're themselves top-level i18n keys).
const roleOptions = computed(() => [
  { value: '', text: t('users.role_any') },
  ...usersStore.roles.data.map((r) => ({ value: r.id, text: t(r.name) })),
])

const permissionOptions = computed(() =>
  adminRefdataStore.permissions.data.map((p) => ({ value: p.id, text: p.name })),
)

// The store's own meta.current_page (not a locally-tracked page ref) is the
// single source of truth for "what page are we on" - it's what the server
// actually returned, so prev/next always reflect reality instead of
// drifting from it if a fetch fails partway through.
function buildParams(targetPage) {
  const params = { page: targetPage }
  if (appliedFilters.name) params.name = appliedFilters.name
  if (appliedFilters.email) params.email = appliedFilters.email
  if (appliedFilters.location) params.location = appliedFilters.location
  if (appliedFilters.country) params.country = appliedFilters.country
  if (appliedFilters.role !== '') params.role = appliedFilters.role
  // Literal bracketed key (not `permissions`) so the query string carries
  // `permissions[]=1&permissions[]=2` - PHP only gathers repeated query
  // params into an array under that syntax, otherwise it's last-one-wins.
  if (appliedFilters.permissions.length) params['permissions[]'] = appliedFilters.permissions
  if (sortBy.value) {
    params.sort = sortBy.value
    params.sortdir = sortDesc.value ? 'desc' : 'asc'
  }
  return params
}

function fetch(targetPage = 1) {
  usersStore.fetchList(buildParams(targetPage))
}

function applyFilters() {
  Object.assign(appliedFilters, filters)
  fetch(1)
}

function sortByColumn(column) {
  const key = column.sortKey
  if (!key) return

  if (sortBy.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortBy.value = key
    sortDesc.value = false
  }
  fetch(1)
}

function sortIndicator(column) {
  if (!column.sortKey || sortBy.value !== column.sortKey) return ''
  return sortDesc.value ? '▼' : '▲'
}

function humanise(iso) {
  if (!iso) return t('users.never')
  try {
    return new Date(iso).toLocaleDateString()
  } catch {
    return iso
  }
}

// Gap 9: legacy truncates the email cell to 15 chars (Str::limit) and wraps
// it in a hover popover offering "Click/press to copy" - reused here as a
// native title tooltip (shows the full address on hover) plus a click
// handler through the shared useClipboard composable, rather than building
// a bespoke Bootstrap popover for a single cell.
function truncateEmail(email) {
  if (!email) return ''
  return email.length > 15 ? `${email.slice(0, 15)}...` : email
}

function copyEmail(email) {
  copy(email)
}

function openRoleModal(row) {
  editingUserId.value = row.id
  editingUserName.value = row.name
}

function closeRoleModal() {
  editingUserId.value = null
  editingUserName.value = ''
  // Refresh whatever page we're on - the row's role/name may have changed.
  fetch(usersStore.list.meta.current_page)
}

const roleModalTitle = computed(() =>
  editingUserName.value ? t('client.users.edit_role_for', { name: editingUserName.value }) : ''
)

function retry() {
  fetch()
}

// Create-user modal (gap 13) - `passwordRepeat` never leaves this component,
// matching PasswordTab.vue's own client-only mismatch check: it's checked
// in submitCreate() before stores/users.js#createUser is ever called, and
// isn't part of the POST body (see UserAPI.js#create's doc comment).
const showCreateModal = ref(false)
const createForm = reactive({ name: '', email: '', role: '', password: '', passwordRepeat: '' })
const createSaving = ref(false)
const createGeneralError = ref('')
const createFieldErrors = ref({})

function createFieldError(field) {
  return createFieldErrors.value[field]?.[0] || ''
}

function openCreateModal() {
  showCreateModal.value = true
}

// Just resets/hides - unlike closeRoleModal, nothing can have been saved by
// the time this runs (AdminSettingsTab has its own in-modal save button;
// this form's submit button IS the save, handled by submitCreate below), so
// dismissing without submitting needs no re-fetch.
function closeCreateModal() {
  showCreateModal.value = false
  createForm.name = ''
  createForm.email = ''
  createForm.role = ''
  createForm.password = ''
  createForm.passwordRepeat = ''
  createGeneralError.value = ''
  createFieldErrors.value = {}
}

async function submitCreate() {
  createSaving.value = true
  createGeneralError.value = ''
  createFieldErrors.value = {}

  if (createForm.password !== createForm.passwordRepeat) {
    createFieldErrors.value = { passwordRepeat: [t('client.users.password_mismatch')] }
    createSaving.value = false
    return
  }

  try {
    await usersStore.createUser({
      name: createForm.name,
      email: createForm.email,
      role: createForm.role,
      password: createForm.password,
    })
    closeCreateModal()
    // Refresh whatever page we're on, same convention as closeRoleModal.
    fetch(usersStore.list.meta.current_page)
  } catch (err) {
    if (err?.status === 422) {
      createFieldErrors.value = err.data?.errors || {}
    }
    createGeneralError.value = t('general.error_occurred')
  } finally {
    createSaving.value = false
  }
}

onMounted(() => {
  usersStore.fetchRoles()
  adminRefdataStore.fetchPermissions()
  fetch()
})
</script>

<template>
  <div class="container py-4" data-testid="user-all-page">
    <div class="d-flex align-items-center flex-wrap mb-2">
      <h1 class="mb-0 me-3">{{ t('users.title') }}</h1>
      <BButton v-if="isAdmin" variant="primary" class="ms-auto" data-testid="user-all-create-button" @click="openCreateModal">
        {{ t('client.users.create_user') }}
      </BButton>
    </div>

    <!-- Gap 6: mobile-only "Reveal filters" toggle for the collapsible
         filter panel below - always visible from md upward, matching
         legacy's d-md-none reveal button. -->
    <button
      type="button"
      class="btn btn-secondary d-md-none mb-3 w-100"
      data-testid="users-filter-reveal"
      @click="filtersOpen = !filtersOpen"
    >
      {{ t('client.users.reveal_filters') }}
    </button>

    <div class="row">
      <div class="col-md-4 col-lg-3">
        <aside class="panel p-3 d-md-block" :class="{ 'd-none': !filtersOpen }" data-testid="users-filter-panel">
          <button
            type="button"
            class="btn-close d-md-none float-end"
            aria-label="Close"
            data-testid="users-filter-close"
            @click="filtersOpen = false"
          />
          <form data-testid="users-filter-form" @submit.prevent="applyFilters">
            <div class="mb-3">
              <label class="form-label" for="filter-name">{{ t('users.name') }}</label>
              <input
                id="filter-name"
                v-model="filters.name"
                type="text"
                class="form-control"
                :placeholder="t('users.placeholder_name')"
                data-testid="users-filter-name"
              >
            </div>

            <div class="mb-3">
              <label class="form-label" for="filter-email">{{ t('users.email') }}</label>
              <input
                id="filter-email"
                v-model="filters.email"
                type="text"
                class="form-control"
                :placeholder="t('users.placeholder_email')"
                data-testid="users-filter-email"
              >
            </div>

            <div class="mb-3">
              <label class="form-label" for="filter-location">{{ t('users.location') }}</label>
              <input
                id="filter-location"
                v-model="filters.location"
                type="text"
                class="form-control"
                :placeholder="t('users.placeholder_location')"
                data-testid="users-filter-location"
              >
            </div>

            <div class="mb-3">
              <label class="form-label" for="filter-country">{{ t('users.country') }}</label>
              <select id="filter-country" v-model="filters.country" class="form-select" data-testid="users-filter-country">
                <option v-for="opt in countryOptions" :key="opt.value" :value="opt.value">{{ opt.text }}</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label" for="filter-role">{{ t('users.role') }}</label>
              <select id="filter-role" v-model="filters.role" class="form-select" data-testid="users-filter-role">
                <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.text }}</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label" for="filter-permissions">{{ t('client.users.permission') }}</label>
              <select
                id="filter-permissions"
                v-model="filters.permissions"
                class="form-select"
                multiple
                size="6"
                data-testid="users-filter-permissions"
              >
                <option v-for="opt in permissionOptions" :key="opt.value" :value="opt.value">{{ opt.text }}</option>
              </select>
            </div>

            <button type="submit" class="btn btn-secondary w-100" data-testid="users-filter-submit">
              {{ t('users.search') }}
            </button>
          </form>
        </aside>
      </div>

      <div class="col-md-8 col-lg-9">
        <div v-if="usersStore.list.loading" data-testid="user-all-loading">
          <div class="placeholder-glow mb-3">
            <span class="placeholder col-12" style="height: 6rem" />
          </div>
        </div>

        <BAlert v-else-if="usersStore.list.error" :model-value="true" variant="danger" data-testid="user-all-error">
          <p>{{ t('client.users.load_error') }}</p>
          <BButton variant="danger" data-testid="user-all-retry" @click="retry">
            {{ t('client.dashboard.retry') }}
          </BButton>
        </BAlert>

        <template v-else>
          <div class="table-responsive panel">
            <table class="table table-striped" data-testid="users-table">
              <thead>
                <tr>
                  <th v-for="column in columns" :key="column.key">
                    <button
                      v-if="column.sortKey"
                      type="button"
                      class="sort-header"
                      :data-testid="`users-table-sort-${column.key}`"
                      @click="sortByColumn(column)"
                    >
                      {{ t(column.labelKey) }} {{ sortIndicator(column) }}
                    </button>
                    <template v-else>{{ t(column.labelKey) }}</template>
                  </th>
                  <th />
                </tr>
              </thead>
              <tbody>
                <tr v-if="!usersStore.list.data.length" data-testid="users-table-empty">
                  <td :colspan="columns.length + 1">{{ t('users.empty') }}</td>
                </tr>
                <tr v-for="row in usersStore.list.data" :key="row.id" :data-testid="`users-row-${row.id}`">
                  <td>
                    <NuxtLink :to="`/profile/edit/${row.id}`" :data-testid="`users-row-link-${row.id}`">
                      {{ row.name }}
                    </NuxtLink>
                  </td>
                  <td>
                    <span
                      class="hover-pointer"
                      role="button"
                      tabindex="0"
                      :title="`${row.email} - ${t('client.users.click_to_copy')}`"
                      :data-testid="`users-row-email-${row.id}`"
                      @click="copyEmail(row.email)"
                      @keydown.enter="copyEmail(row.email)"
                    >{{ truncateEmail(row.email) }}</span>
                  </td>
                  <td>{{ t(row.role_name) }}</td>
                  <td>{{ row.location || t('users.location_na') }}</td>
                  <td>{{ row.country_name }}</td>
                  <!-- parity-v2/admin-and-static.md gap 10: legacy wraps this
                       count in a hover popover listing the member's actual
                       group names (partials/usergroups-popover.blade.php).
                       Left as a bare count - BACKEND GAP: UserAdmin (API\
                       UserController::listUsersv2) only returns
                       `groups_count` (withCount('groups')), never the group
                       names themselves, so there's nothing to render in a
                       popover without faking data; needs a
                       `groups: [{id, name}]` (or similar) field added
                       server-side first. -->
                  <td>{{ row.groups_count }}</td>
                  <td :title="row.created_at">{{ humanise(row.created_at) }}</td>
                  <td :title="row.last_login_at">{{ humanise(row.last_login_at) }}</td>
                  <td>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary"
                      :data-testid="`users-row-edit-role-${row.id}`"
                      @click="openRoleModal(row)"
                    >
                      {{ t('client.users.edit_role') }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Gap 7: legacy uses Laravel's full numbered paginator
                 ($userlist->links()), letting users jump directly to any
                 page - a bare Previous/Next pair can't. Same BPagination
                 widget used elsewhere for this (pages/notifications.vue,
                 DevicesSearchTable.vue). -->
            <BPagination
              v-if="usersStore.list.meta.last_page > 1"
              :model-value="usersStore.list.meta.current_page"
              :total-rows="usersStore.list.meta.total"
              :per-page="usersStore.list.meta.per_page"
              align="center"
              class="mt-3"
              data-testid="users-pagination"
              @update:model-value="fetch"
            />

            <p v-if="usersStore.list.meta.total > 0" class="text-center mt-2" data-testid="users-showing">
              {{ t('users.showing', { from: usersStore.list.meta.from, to: usersStore.list.meta.to, total: usersStore.list.meta.total }) }}
            </p>
          </div>
        </template>
      </div>
    </div>

    <BModal
      :model-value="editingUserId !== null"
      :title="roleModalTitle"
      no-footer
      data-testid="users-role-modal"
      @hide="closeRoleModal"
    >
      <AdminSettingsTab v-if="editingUserId !== null" :target-id="editingUserId" />
      <div class="d-flex justify-content-end mt-3">
        <BButton variant="outline-secondary" data-testid="users-role-modal-close" @click="closeRoleModal">
          {{ t('partials.cancel') }}
        </BButton>
      </div>
    </BModal>

    <BModal
      :model-value="showCreateModal"
      :title="t('client.users.create_user_title')"
      no-footer
      data-testid="create-user-modal"
      @hide="closeCreateModal"
    >
      <BAlert v-if="createGeneralError" :model-value="true" variant="danger" data-testid="create-user-error">
        {{ createGeneralError }}
      </BAlert>

      <BForm data-testid="create-user-form" @submit.prevent="submitCreate">
        <BFormGroup :label="`${t('users.name')}:`" label-for="create-user-name">
          <BFormInput id="create-user-name" v-model="createForm.name" required data-testid="create-user-name" />
          <div v-if="createFieldError('name')" class="invalid-feedback d-block" data-testid="create-user-name-error">
            {{ createFieldError('name') }}
          </div>
        </BFormGroup>

        <BFormGroup :label="`${t('users.email')}:`" label-for="create-user-email">
          <BFormInput id="create-user-email" v-model="createForm.email" type="email" required data-testid="create-user-email" />
          <div v-if="createFieldError('email')" class="invalid-feedback d-block" data-testid="create-user-email-error">
            {{ createFieldError('email') }}
          </div>
        </BFormGroup>

        <BFormGroup :label="`${t('users.role')}:`" label-for="create-user-role">
          <BFormSelect id="create-user-role" v-model="createForm.role" required data-testid="create-user-role">
            <option value="" />
            <option v-for="r in usersStore.roles.data" :key="r.id" :value="r.id">{{ t(r.name) }}</option>
          </BFormSelect>
          <div v-if="createFieldError('role')" class="invalid-feedback d-block" data-testid="create-user-role-error">
            {{ createFieldError('role') }}
          </div>
        </BFormGroup>

        <BFormGroup :label="`${t('auth.password')}:`" label-for="create-user-password">
          <BFormInput id="create-user-password" v-model="createForm.password" type="password" required data-testid="create-user-password" />
          <div v-if="createFieldError('password')" class="invalid-feedback d-block" data-testid="create-user-password-error">
            {{ createFieldError('password') }}
          </div>
        </BFormGroup>

        <BFormGroup :label="`${t('auth.repeat_password')}:`" label-for="create-user-password-repeat">
          <BFormInput
            id="create-user-password-repeat"
            v-model="createForm.passwordRepeat"
            type="password"
            required
            data-testid="create-user-password-repeat"
          />
          <div v-if="createFieldError('passwordRepeat')" class="invalid-feedback d-block" data-testid="create-user-password-repeat-error">
            {{ createFieldError('passwordRepeat') }}
          </div>
        </BFormGroup>

        <div class="d-flex justify-content-end gap-2 mt-3">
          <BButton variant="outline-secondary" data-testid="create-user-cancel" @click="closeCreateModal">
            {{ t('partials.cancel') }}
          </BButton>
          <BButton type="submit" variant="primary" :disabled="createSaving" data-testid="create-user-submit">
            {{ t('client.users.create_user_title') }}
          </BButton>
        </div>
      </BForm>
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

.hover-pointer {
  cursor: pointer;
}
</style>
