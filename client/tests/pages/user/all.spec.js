import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import UserAllPage from '../../../app/pages/user/all.vue'
import { useUsersStore } from '../../../app/stores/users.js'
import { useAdminRefdataStore } from '../../../app/stores/adminRefdata.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-title="title"><slot /></div>',
}
// Same shape as components/profile/ProfileInfoTab.spec.js's BForm*
// stubs - real elements so v-model/setValue work in tests. `emits` must be
// declared explicitly here (unlike those other spec files, which never
// assert call *counts* on the submit handler): without it, Vue's attrs
// fallthrough attaches the parent's onSubmit listener directly onto this
// stub's root <form> IN ADDITION to the explicit $emit below, so a single
// triggered 'submit' event invokes the handler twice.
const BFormStub = { emits: ['submit'], template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot /></div>' }
const BFormInputStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs" />',
}
// Coerces to Number, same as AdminSettingsTab.spec.js's own BFormSelectStub -
// the only BFormSelect on this page is the create-user role field, bound to
// numeric role ids (matching AdminSettingsTab's role select).
const BFormSelectStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', Number($event.target.value))"><slot /></select>',
}
// Same convention as tests/pages/notifications.spec.js's BPagination stub
// (gap 7: a numbered paginator replacing the old prev/next pair) - clicking
// it emits the next page number so tests can assert the page-jump handler.
const BPaginationStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<div @click="$emit(\'update:modelValue\', modelValue + 1)"><slot /></div>',
}

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BModal: BModalStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
  BFormInput: BFormInputStub,
  BFormSelect: BFormSelectStub,
  BPagination: BPaginationStub,
}

const ROW = {
  id: 7,
  name: 'Jane Fixit',
  email: 'jane@example.com',
  role: 4,
  role_name: 'Restarter',
  location: 'London',
  country: 'GB',
  country_name: 'United Kingdom',
  groups_count: 2,
  created_at: '2024-01-01T00:00:00Z',
  last_login_at: null,
}

// lang/en/users.php's role_any changed to "Choose role" (gap 28) alongside
// this Nuxt work, but client/i18n/locales/en.json is a generated, checked-in
// artifact this change intentionally leaves untouched (php artisan
// translations:export-client) - overlay the changed key here so the spec
// doesn't depend on regenerating it.
const messages = {
  en: {
    ...en,
    ...clientEn,
    users: { ...en.users, role_any: 'Choose role' },
  },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(UserAllPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/user/all', () => {
  let usersStore
  let adminRefdataStore

  beforeEach(() => {
    setActivePinia(createPinia())

    // useAuth()'s hasRole() reads authStore.user.role_name directly (mirrors
    // DevicesSearchTable.spec.js's setLoggedInUser) - the create-user button
    // is gated on it in addition to the page-level definePageMeta role.
    useAuthStore().user = { role_name: 'Administrator' }

    usersStore = useUsersStore()
    usersStore.fetchList = vi.fn().mockResolvedValue([])
    usersStore.fetchRoles = vi.fn().mockResolvedValue([])
    usersStore.createUser = vi.fn().mockResolvedValue({ id: 99 })
    usersStore.roles.data = [
      { id: 3, name: 'Host' },
      { id: 4, name: 'Restarter' },
    ]

    adminRefdataStore = useAdminRefdataStore()
    adminRefdataStore.fetchPermissions = vi.fn().mockResolvedValue([])
    adminRefdataStore.permissions.data = [
      { id: 1, name: 'Manage groups' },
      { id: 2, name: 'Manage users' },
    ]
  })

  it('fetches roles, permissions and the first page of users on mount', () => {
    mountPage()

    expect(usersStore.fetchRoles).toHaveBeenCalled()
    expect(adminRefdataStore.fetchPermissions).toHaveBeenCalled()
    expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 1 })
  })

  it('shows a loading skeleton while loading', () => {
    usersStore.list.loading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="user-all-loading"]').exists()).toBe(true)
  })

  it('shows an error state with a retry button that calls fetchList again', async () => {
    usersStore.list.error = { status: 500 }

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="user-all-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="user-all-retry"]').trigger('click')
    // once on mount, once on retry
    expect(usersStore.fetchList).toHaveBeenCalledTimes(2)
  })

  it('shows the empty-results message when the list is empty', () => {
    usersStore.list.data = []

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="users-table-empty"]').exists()).toBe(true)
  })

  it('renders a row per user with name link, role, location, country, groups count and dates', () => {
    usersStore.list.data = [ROW]

    const wrapper = mountPage()
    const row = wrapper.find('[data-testid="users-row-7"]')

    expect(row.exists()).toBe(true)
    expect(row.find('[data-testid="users-row-link-7"]').attributes('href')).toBe('/profile/edit/7')
    expect(row.text()).toContain('Jane Fixit')
    // role_name is the raw roles.role DB value ("Restarter") - it's t()'d
    // (see the page's own comment), and the generated en.json translates
    // that literal string to "Repairer" (a top-level i18n key, same
    // mechanism PublicProfileView.vue uses for role_name).
    expect(row.text()).toContain('Repairer')
    expect(row.text()).toContain('London')
    expect(row.text()).toContain('United Kingdom')
    expect(row.text()).toContain('2')
    // last_login_at is null -> "Never"
    expect(row.text()).toContain('Never')
  })

  describe('filters', () => {
    it('only applies filters (and resets to page 1) once the form is submitted', async () => {
      const wrapper = mountPage()
      usersStore.fetchList.mockClear()

      await wrapper.find('[data-testid="users-filter-name"]').setValue('Jane')
      await wrapper.find('[data-testid="users-filter-email"]').setValue('jane@x.com')
      await wrapper.find('[data-testid="users-filter-location"]').setValue('London')
      await wrapper.find('[data-testid="users-filter-country"]').setValue('GB')
      await wrapper.find('[data-testid="users-filter-role"]').setValue('4')

      expect(usersStore.fetchList).not.toHaveBeenCalled()

      await wrapper.find('[data-testid="users-filter-form"]').trigger('submit')

      expect(usersStore.fetchList).toHaveBeenCalledWith({
        page: 1,
        name: 'Jane',
        email: 'jane@x.com',
        location: 'London',
        country: 'GB',
        // The role <select>'s options are bound with :value="r.id" (a
        // number, matching AdminSettingsTab's role select), so v-model
        // resolves the selection to the actual number, not the string the
        // <option> element renders.
        role: 4,
      })
    })

    it('omits blank filters from the query params', async () => {
      const wrapper = mountPage()
      usersStore.fetchList.mockClear()

      await wrapper.find('[data-testid="users-filter-name"]').setValue('Jane')
      await wrapper.find('[data-testid="users-filter-form"]').trigger('submit')

      expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 1, name: 'Jane' })
    })

    // Gap 28: legacy's role filter defaults to "Choose role", not "Any role".
    it('labels the default role filter option "Choose role"', () => {
      const wrapper = mountPage()
      const defaultOption = wrapper.find('[data-testid="users-filter-role"] option')

      expect(defaultOption.text()).toBe('Choose role')
    })
  })

  // Gap 6: legacy's filter <aside> is a Bootstrap collapse, hidden by
  // default below the md breakpoint and toggled by a "Reveal filters"
  // button, with an inline close control inside the panel itself.
  describe('mobile filter reveal (gap 6)', () => {
    it('starts collapsed, and the reveal button opens it', async () => {
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="users-filter-panel"]').classes()).toContain('d-none')

      await wrapper.find('[data-testid="users-filter-reveal"]').trigger('click')

      expect(wrapper.find('[data-testid="users-filter-panel"]').classes()).not.toContain('d-none')
    })

    it('the close control inside the panel collapses it again', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="users-filter-reveal"]').trigger('click')

      await wrapper.find('[data-testid="users-filter-close"]').trigger('click')

      expect(wrapper.find('[data-testid="users-filter-panel"]').classes()).toContain('d-none')
    })
  })

  describe('sorting', () => {
    it('sorts ascending on first click, mapping the column to its sort key, and resets to page 1', async () => {
      const wrapper = mountPage()
      usersStore.fetchList.mockClear()

      await wrapper.find('[data-testid="users-table-sort-role_name"]').trigger('click')

      expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 1, sort: 'role', sortdir: 'asc' })
    })

    it('toggles to descending on a second click of the same column', async () => {
      const wrapper = mountPage()

      await wrapper.find('[data-testid="users-table-sort-name"]').trigger('click')
      usersStore.fetchList.mockClear()
      await wrapper.find('[data-testid="users-table-sort-name"]').trigger('click')

      expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 1, sort: 'name', sortdir: 'desc' })
    })

    it('resets to ascending when switching to a different column', async () => {
      const wrapper = mountPage()

      await wrapper.find('[data-testid="users-table-sort-name"]').trigger('click')
      await wrapper.find('[data-testid="users-table-sort-name"]').trigger('click')
      usersStore.fetchList.mockClear()
      await wrapper.find('[data-testid="users-table-sort-email"]').trigger('click')

      expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 1, sort: 'email', sortdir: 'asc' })
    })

    it('has no sort control for the non-sortable groups column', () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="users-table-sort-groups_count"]').exists()).toBe(false)
    })
  })

  describe('pagination', () => {
    beforeEach(() => {
      usersStore.list.meta = { current_page: 2, last_page: 3, per_page: 30, total: 61, from: 31, to: 60 }
    })

    it('shows the showing-range text', () => {
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="users-showing"]').text()).toContain('31')
      expect(wrapper.find('[data-testid="users-showing"]').text()).toContain('60')
      expect(wrapper.find('[data-testid="users-showing"]').text()).toContain('61')
    })

    // Gap 7: legacy uses Laravel's full numbered paginator ($userlist->links()),
    // letting users jump directly to any page - not a bare Previous/Next pair.
    it('renders a BPagination widget instead of prev/next buttons when there is more than one page', async () => {
      const wrapper = mountPage()

      const pagination = wrapper.find('[data-testid="users-pagination"]')
      expect(pagination.exists()).toBe(true)

      await pagination.trigger('click')
      expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 3 })
    })

    it('hides the paginator entirely when there is only one page', () => {
      usersStore.list.meta = { current_page: 1, last_page: 1, per_page: 30, total: 5, from: 1, to: 5 }
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="users-pagination"]').exists()).toBe(false)
    })
  })

  describe('permission filter (gap 24)', () => {
    it('sends selected permission ids under the permissions[] key, omitted when none selected', async () => {
      const wrapper = mountPage()
      usersStore.fetchList.mockClear()

      await wrapper.find('[data-testid="users-filter-permissions"]').setValue(['1', '2'])
      await wrapper.find('[data-testid="users-filter-form"]').trigger('submit')

      expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 1, 'permissions[]': [1, 2] })
    })
  })

  describe('create-user modal (gap 13)', () => {
    it('shows the create button only for admins', () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="user-all-create-button"]').exists()).toBe(true)
    })

    it('hides the create button for a non-Administrator', () => {
      useAuthStore().user = { role_name: 'Host' }
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="user-all-create-button"]').exists()).toBe(false)
    })

    it('is closed by default and opens on the create button', async () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="create-user-modal"]').exists()).toBe(false)

      await wrapper.find('[data-testid="user-all-create-button"]').trigger('click')

      expect(wrapper.find('[data-testid="create-user-modal"]').exists()).toBe(true)
    })

    it('rejects a mismatched password confirmation without calling the API', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="user-all-create-button"]').trigger('click')

      await wrapper.find('[data-testid="create-user-name"]').setValue('Jane Fixit')
      await wrapper.find('[data-testid="create-user-email"]').setValue('jane@example.com')
      await wrapper.find('[data-testid="create-user-role"]').setValue('4')
      await wrapper.find('[data-testid="create-user-password"]').setValue('secret123')
      await wrapper.find('[data-testid="create-user-password-repeat"]').setValue('different')
      await wrapper.find('[data-testid="create-user-form"]').trigger('submit')

      expect(usersStore.createUser).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="create-user-password-repeat-error"]').exists()).toBe(true)
    })

    it('posts {name, email, role, password} (no passwordRepeat) when the confirmation matches, then closes and refreshes the list', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="user-all-create-button"]').trigger('click')
      usersStore.fetchList.mockClear()

      await wrapper.find('[data-testid="create-user-name"]').setValue('Jane Fixit')
      await wrapper.find('[data-testid="create-user-email"]').setValue('jane@example.com')
      await wrapper.find('[data-testid="create-user-role"]').setValue('4')
      await wrapper.find('[data-testid="create-user-password"]').setValue('secret123')
      await wrapper.find('[data-testid="create-user-password-repeat"]').setValue('secret123')
      await wrapper.find('[data-testid="create-user-form"]').trigger('submit')
      await Promise.resolve()
      await Promise.resolve()

      expect(usersStore.createUser).toHaveBeenCalledWith({
        name: 'Jane Fixit',
        email: 'jane@example.com',
        role: 4,
        password: 'secret123',
      })
      expect(wrapper.find('[data-testid="create-user-modal"]').exists()).toBe(false)
      expect(usersStore.fetchList).toHaveBeenCalledTimes(1)
    })

    it('renders a 422 field error from the server and keeps the modal open', async () => {
      usersStore.createUser = vi.fn().mockRejectedValue({
        status: 422,
        data: { errors: { email: ['The email has already been taken.'] } },
      })

      const wrapper = mountPage()
      await wrapper.find('[data-testid="user-all-create-button"]').trigger('click')

      await wrapper.find('[data-testid="create-user-name"]').setValue('Jane Fixit')
      await wrapper.find('[data-testid="create-user-email"]').setValue('taken@example.com')
      await wrapper.find('[data-testid="create-user-role"]').setValue('4')
      await wrapper.find('[data-testid="create-user-password"]').setValue('secret123')
      await wrapper.find('[data-testid="create-user-password-repeat"]').setValue('secret123')
      await wrapper.find('[data-testid="create-user-form"]').trigger('submit')
      await Promise.resolve()
      await Promise.resolve()

      expect(wrapper.find('[data-testid="create-user-modal"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="create-user-email-error"]').text()).toBe('The email has already been taken.')
    })

    it('resets the form and clears errors when cancelled', async () => {
      usersStore.createUser = vi.fn().mockRejectedValue({ status: 422, data: { errors: { name: ['Required'] } } })

      const wrapper = mountPage()
      await wrapper.find('[data-testid="user-all-create-button"]').trigger('click')
      await wrapper.find('[data-testid="create-user-name"]').setValue('Jane Fixit')
      await wrapper.find('[data-testid="create-user-form"]').trigger('submit')
      await Promise.resolve()
      await Promise.resolve()
      expect(wrapper.find('[data-testid="create-user-name-error"]').exists()).toBe(true)

      await wrapper.find('[data-testid="create-user-cancel"]').trigger('click')
      expect(wrapper.find('[data-testid="create-user-modal"]').exists()).toBe(false)

      await wrapper.find('[data-testid="user-all-create-button"]').trigger('click')
      expect(wrapper.find('[data-testid="create-user-name"]').element.value).toBe('')
      expect(wrapper.find('[data-testid="create-user-name-error"]').exists()).toBe(false)
    })
  })
})
