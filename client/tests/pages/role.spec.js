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
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-modal-title="title"><slot /></div>',
}

const GLOBAL_STUBS = { BAlert: BAlertStub, BButton: BButtonStub, BModal: BModalStub }

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
