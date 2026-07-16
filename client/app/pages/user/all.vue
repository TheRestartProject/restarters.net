<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUsersStore } from '~/stores/users.js'
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
// permissions form a second time. The legacy page's "Create new user" modal
// is not ported - see stores/users.js's class doc comment.
definePageMeta({ auth: true, role: 'Administrator' })

const { t, tm } = useI18n()
useHead({ title: t('users.title') })

const usersStore = useUsersStore()

const filters = reactive({ name: '', email: '', location: '', country: '', role: '' })
const appliedFilters = reactive({ name: '', email: '', location: '', country: '', role: '' })
const sortBy = ref('')
const sortDesc = ref(false)

const editingUserId = ref(null)
const editingUserName = ref('')

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

const roleOptions = computed(() => [
  { value: '', text: t('users.role_any') },
  ...usersStore.roles.data.map((r) => ({ value: r.id, text: r.name })),
])

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

function previousPage() {
  const current = usersStore.list.meta.current_page
  if (current > 1) {
    fetch(current - 1)
  }
}

function nextPage() {
  const current = usersStore.list.meta.current_page
  if (current < usersStore.list.meta.last_page) {
    fetch(current + 1)
  }
}

function humanise(iso) {
  if (!iso) return t('users.never')
  try {
    return new Date(iso).toLocaleDateString()
  } catch {
    return iso
  }
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

onMounted(() => {
  usersStore.fetchRoles()
  fetch()
})
</script>

<template>
  <div class="container py-4" data-testid="user-all-page">
    <h1>{{ t('users.title') }}</h1>

    <div class="row">
      <div class="col-md-4 col-lg-3">
        <aside class="panel p-3">
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
            <table class="table" data-testid="users-table">
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
                  <td>{{ row.email }}</td>
                  <td>{{ row.role_name }}</td>
                  <td>{{ row.location }}</td>
                  <td>{{ row.country_name }}</td>
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

            <div class="d-flex justify-content-between align-items-center mt-3" data-testid="users-pagination">
              <BButton
                variant="secondary"
                :disabled="usersStore.list.meta.current_page <= 1"
                data-testid="users-pagination-prev"
                @click="previousPage"
              >
                {{ t('client.groups.previous_page') }}
              </BButton>
              <span data-testid="users-pagination-indicator">
                {{ t('client.groups.page_of', { page: usersStore.list.meta.current_page, total: usersStore.list.meta.last_page }) }}
              </span>
              <BButton
                variant="secondary"
                :disabled="usersStore.list.meta.current_page >= usersStore.list.meta.last_page"
                data-testid="users-pagination-next"
                @click="nextPage"
              >
                {{ t('client.groups.next_page') }}
              </BButton>
            </div>

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
      hide-footer
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
