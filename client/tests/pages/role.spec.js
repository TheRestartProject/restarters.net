import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RolePage from '../../app/pages/role.vue'
import { useAdminRefdataStore } from '../../app/stores/adminRefdata.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

// pages/role.vue is bespoke (not built on AdminCrudTable.vue - see the
// page's own doc comment for why), so unlike the other four reference-data
// pages this spec exercises real rendering/interaction rather than just
// prop-wiring.
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-modal-title="title"><slot /></div>',
}

const GLOBAL_STUBS = { BAlert: BAlertStub, BButton: BButtonStub, BFormGroup: BFormGroupStub, BModal: BModalStub }

const ROLES = [
  { id: 3, name: 'Host', permissions: [4], permissions_list: 'Create Party' },
  { id: 4, name: 'Restarter', permissions: [], permissions_list: '' },
]
const PERMISSIONS = [
  { id: 4, name: 'Create Party' },
  { id: 6, name: 'View Reports' },
]

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(RolePage, {
    global: { plugins: [i18n], stubs: GLOBAL_STUBS },
  })
}

describe('pages/role', () => {
  let adminStore

  beforeEach(() => {
    setActivePinia(createPinia())
    adminStore = useAdminRefdataStore()
    adminStore.fetchRoles = vi.fn().mockImplementation(async () => {
      adminStore.roles.data = ROLES
      return ROLES
    })
    adminStore.fetchPermissions = vi.fn().mockImplementation(async () => {
      adminStore.permissions.data = PERMISSIONS
      return PERMISSIONS
    })
    adminStore.updateRolePermissions = vi.fn()
    vi.stubGlobal('useRoute', () => ({ query: {} }))
  })


  it('fetches roles and permissions on mount', () => {
    mountPage()

    expect(adminStore.fetchRoles).toHaveBeenCalledTimes(1)
    expect(adminStore.fetchPermissions).toHaveBeenCalledTimes(1)
  })

  it('renders a row per role with id/name/permissions_list', async () => {
    const wrapper = mountPage()
    await flushPromises()

    const row = wrapper.find('[data-testid="roles-row-3"]')
    expect(row.text()).toContain('3')
    expect(row.text()).toContain('Host')
    expect(row.text()).toContain('Create Party')
  })

  it('opens the edit modal pre-checked with the role\'s current permissions', async () => {
    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="roles-edit-link-3"]').trigger('click')

    const modal = wrapper.find('[data-testid="roles-edit-modal"]')
    expect(modal.exists()).toBe(true)
    expect(wrapper.find('[data-testid="role-permission-4"]').element.checked).toBe(true)
    expect(wrapper.find('[data-testid="role-permission-6"]').element.checked).toBe(false)
  })

  // live RolesPage.vue (07e6abd7cc^) is the baseline: id/name are sortable,
  // but permissions_list is explicitly `sortable: false` (a display-only
  // comma-joined string) - develop's dead RolesTable.vue (not mounted by
  // anything on this branch) sorted all three, which was the wrong target.
  describe('sorting', () => {
    it('has no sort control for the permissions column', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="roles-table-sort-permissions_list"]').exists()).toBe(false)
    })

    it('sorts ascending on first click of a sortable column', async () => {
      const wrapper = mountPage()
      await flushPromises()

      await wrapper.find('[data-testid="roles-table-sort-name"]').trigger('click')

      const rows = wrapper.findAll('tbody tr')
      expect(rows[0].attributes('data-testid')).toBe('roles-row-3') // 'Host' < 'Restarter'
    })

    it('toggles to descending on a second click', async () => {
      const wrapper = mountPage()
      await flushPromises()

      await wrapper.find('[data-testid="roles-table-sort-name"]').trigger('click')
      await wrapper.find('[data-testid="roles-table-sort-name"]').trigger('click')

      const rows = wrapper.findAll('tbody tr')
      expect(rows[0].attributes('data-testid')).toBe('roles-row-4')
    })

    it('sorts numerically on the id column', async () => {
      const wrapper = mountPage()
      await flushPromises()

      await wrapper.find('[data-testid="roles-table-sort-id"]').trigger('click')
      await wrapper.find('[data-testid="roles-table-sort-id"]').trigger('click')

      const rows = wrapper.findAll('tbody tr')
      expect(rows[0].attributes('data-testid')).toBe('roles-row-4') // descending: 4 before 3
    })
  })

  it('toggling a checkbox and saving calls updateRolePermissions with the full new selection', async () => {
    adminStore.updateRolePermissions.mockResolvedValue({ id: 3, name: 'Host', permissions: [4, 6], permissions_list: 'Create Party, View Reports' })

    const wrapper = mountPage()
    await flushPromises()
    await wrapper.find('[data-testid="roles-edit-link-3"]').trigger('click')

    await wrapper.find('[data-testid="role-permission-6"]').setValue(true)
    await wrapper.find('[data-testid="roles-edit-save"]').trigger('click')
    await flushPromises()

    expect(adminStore.updateRolePermissions).toHaveBeenCalledWith(3, [4, 6])
    expect(wrapper.find('[data-testid="roles-edit-modal"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="roles-feedback"]').text()).toBe('Role permissions updated.')
  })

  it('unchecking a permission removes it from the saved selection', async () => {
    adminStore.updateRolePermissions.mockResolvedValue({ id: 3, name: 'Host', permissions: [], permissions_list: '' })

    const wrapper = mountPage()
    await flushPromises()
    await wrapper.find('[data-testid="roles-edit-link-3"]').trigger('click')

    await wrapper.find('[data-testid="role-permission-4"]').setValue(false)
    await wrapper.find('[data-testid="roles-edit-save"]').trigger('click')
    await flushPromises()

    expect(adminStore.updateRolePermissions).toHaveBeenCalledWith(3, [])
  })

  it('shows an error and keeps the modal open when the save fails', async () => {
    adminStore.updateRolePermissions.mockRejectedValue({ data: { message: 'Nope' } })

    const wrapper = mountPage()
    await flushPromises()
    await wrapper.find('[data-testid="roles-edit-link-3"]').trigger('click')
    await wrapper.find('[data-testid="roles-edit-save"]').trigger('click')
    await flushPromises()

    expect(wrapper.find('[data-testid="roles-edit-error"]').text()).toBe('Nope')
    expect(wrapper.find('[data-testid="roles-edit-modal"]').exists()).toBe(true)
  })

  it('auto-opens the edit modal for ?editId= once roles have loaded', async () => {
    vi.stubGlobal('useRoute', () => ({ query: { editId: '4' } }))

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="roles-edit-modal"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="role-permission-4"]').element.checked).toBe(false)
  })

  it('shows an error state with retry when loading roles/permissions fails', async () => {
    adminStore.fetchRoles = vi.fn().mockRejectedValue({ status: 500 })

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="role-load-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="role-retry"]').trigger('click')
    await flushPromises()

    expect(adminStore.fetchRoles).toHaveBeenCalledTimes(2)
  })
})
