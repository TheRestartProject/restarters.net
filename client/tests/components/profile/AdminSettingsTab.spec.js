import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AdminSettingsTab from '../../../app/components/profile/AdminSettingsTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
const BFormSelectStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', Number($event.target.value))" v-bind="$attrs"><slot /></select>',
}
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }

const ADMIN_SETTINGS_RESPONSE = {
  role: 4,
  assigned_groups: [1],
  preferences: [10],
  permissions: [20],
  roles: [{ value: 4, label: 'Restarter' }, { value: 3, label: 'Host' }],
  groups: [{ id: 1, name: 'Group One' }, { id: 2, name: 'Group Two' }],
  preferences_options: [{ id: 10, name: 'Pref A', purpose: null }],
  permissions_options: [{ id: 20, name: 'Perm A', purpose: null }],
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(AdminSettingsTab, {
    props: { targetId: 9, ...props },
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BFormGroup: BFormGroupStub, BFormSelect: BFormSelectStub, BButton: BButtonStub, BForm: BFormStub },
    },
  })
}

describe('components/profile/AdminSettingsTab', () => {
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    profileStore = useProfileStore()
  })

  it('fetches GET /users/{id}/admin-settings for the given targetId on mount', async () => {
    profileStore.fetchAdminSettings = vi.fn().mockResolvedValue(ADMIN_SETTINGS_RESPONSE)

    mountComponent({ targetId: 9 })
    await flushPromises()

    expect(profileStore.fetchAdminSettings).toHaveBeenCalledWith(9)
  })

  it('re-fetches when targetId changes (e.g. an admin browsing between users)', async () => {
    profileStore.fetchAdminSettings = vi.fn().mockResolvedValue(ADMIN_SETTINGS_RESPONSE)

    const wrapper = mountComponent({ targetId: 9 })
    await flushPromises()

    await wrapper.setProps({ targetId: 11 })
    await flushPromises()

    expect(profileStore.fetchAdminSettings).toHaveBeenCalledWith(11)
  })

  it('sends the exact legacy payload shape (user_role/assigned_groups/preferences/permissions) on save', async () => {
    // The mocked fetchAdminSettings (unlike the real store action) never
    // touches profileStore.adminSettings.data itself, so it's seeded here
    // directly - same convention as tests/components/profile/SkillsTab.spec.js.
    profileStore.fetchAdminSettings = vi.fn().mockResolvedValue(ADMIN_SETTINGS_RESPONSE)
    profileStore.adminSettings.data = ADMIN_SETTINGS_RESPONSE
    profileStore.updateAdminSettings = vi.fn().mockResolvedValue({ role: 3 })

    const wrapper = mountComponent({ targetId: 9 })
    await flushPromises()
    await wrapper.find('[data-testid="admin-role-select"]').setValue('3')
    await wrapper.find('[data-testid="admin-settings-form"]').trigger('submit')
    await flushPromises()

    expect(profileStore.updateAdminSettings).toHaveBeenCalledWith(9, {
      user_role: 3,
      assigned_groups: [1],
      preferences: [10],
      permissions: [20],
    })
  })

  it('shows an error state when the store has an error', async () => {
    profileStore.fetchAdminSettings = vi.fn().mockRejectedValue({ status: 403 })

    const wrapper = mountComponent({ targetId: 9 })
    await flushPromises()
    profileStore.adminSettings.error = { status: 403 }
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="admin-settings-error"]').exists()).toBe(true)
  })
})
