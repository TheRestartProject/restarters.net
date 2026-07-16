import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import UserAllPage from '../../../app/pages/user/all.vue'
import { useUsersStore } from '../../../app/stores/users.js'
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
const AdminSettingsTabStub = {
  props: ['targetId'],
  template: '<div data-testid="stub-admin-settings" :data-target-id="targetId" />',
}

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BModal: BModalStub,
  AdminSettingsTab: AdminSettingsTabStub,
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

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(UserAllPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/user/all', () => {
  let usersStore

  beforeEach(() => {
    setActivePinia(createPinia())

    usersStore = useUsersStore()
    usersStore.fetchList = vi.fn().mockResolvedValue([])
    usersStore.fetchRoles = vi.fn().mockResolvedValue([])
    usersStore.roles.data = [
      { id: 3, name: 'Host' },
      { id: 4, name: 'Restarter' },
    ]
  })

  it('fetches roles and the first page of users on mount', () => {
    mountPage()

    expect(usersStore.fetchRoles).toHaveBeenCalled()
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
    expect(row.text()).toContain('Restarter')
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

    it('shows the current/last page and the showing-range text', () => {
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="users-pagination-indicator"]').text()).toContain('2')
      expect(wrapper.find('[data-testid="users-pagination-indicator"]').text()).toContain('3')
      expect(wrapper.find('[data-testid="users-showing"]').text()).toContain('31')
      expect(wrapper.find('[data-testid="users-showing"]').text()).toContain('60')
      expect(wrapper.find('[data-testid="users-showing"]').text()).toContain('61')
    })

    it('advances to the next page and re-fetches', async () => {
      const wrapper = mountPage()
      usersStore.fetchList.mockClear()

      await wrapper.find('[data-testid="users-pagination-next"]').trigger('click')

      expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 3 })
    })

    it('goes back to the previous page and re-fetches', async () => {
      const wrapper = mountPage()
      usersStore.fetchList.mockClear()

      await wrapper.find('[data-testid="users-pagination-prev"]').trigger('click')

      expect(usersStore.fetchList).toHaveBeenCalledWith({ page: 1 })
    })

    it('disables next on the last page', () => {
      usersStore.list.meta = { current_page: 3, last_page: 3, per_page: 30, total: 61, from: 61, to: 61 }
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="users-pagination-next"]').attributes('disabled')).toBeDefined()
    })

    it('disables previous on the first page', () => {
      usersStore.list.meta = { current_page: 1, last_page: 3, per_page: 30, total: 61, from: 1, to: 30 }
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="users-pagination-prev"]').attributes('disabled')).toBeDefined()
    })
  })

  describe('role editor modal', () => {
    beforeEach(() => {
      usersStore.list.data = [ROW]
    })

    it('is closed by default', () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="users-role-modal"]').exists()).toBe(false)
    })

    it('opens with AdminSettingsTab scoped to the clicked row on "Edit role"', async () => {
      const wrapper = mountPage()

      await wrapper.find('[data-testid="users-row-edit-role-7"]').trigger('click')

      const modal = wrapper.find('[data-testid="users-role-modal"]')
      expect(modal.exists()).toBe(true)
      expect(wrapper.find('[data-testid="stub-admin-settings"]').attributes('data-target-id')).toBe('7')
    })

    it('closes and re-fetches the current page when dismissed', async () => {
      const wrapper = mountPage()
      await wrapper.find('[data-testid="users-row-edit-role-7"]').trigger('click')
      usersStore.fetchList.mockClear()

      await wrapper.find('[data-testid="users-role-modal-close"]').trigger('click')

      expect(wrapper.find('[data-testid="users-role-modal"]').exists()).toBe(false)
      expect(usersStore.fetchList).toHaveBeenCalledTimes(1)
    })
  })
})
