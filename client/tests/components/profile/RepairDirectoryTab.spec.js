import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RepairDirectoryTab from '../../../app/components/profile/RepairDirectoryTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BFormGroupStub = { template: '<div><slot /></div>' }
const BFormSelectStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value === \'\' ? null : Number($event.target.value))" v-bind="$attrs"><slot /></select>',
}
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form data-testid="repair-dir-form" @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(RepairDirectoryTab, {
    props: { targetId: 7, ...props },
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BFormGroup: BFormGroupStub, BFormSelect: BFormSelectStub, BButton: BButtonStub, BForm: BFormStub },
    },
  })
}

describe('components/profile/RepairDirectoryTab', () => {
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    profileStore = useProfileStore()
  })

  it('does not fetch on its own - reads the store state the page already populated (see ProfileTabs.vue)', () => {
    profileStore.fetchRepairDirectoryOptions = vi.fn()
    profileStore.repairDirectory.data = {
      current: 0,
      options: [{ value: 0, key: 'profile.repair_dir_none', selected: true, disabled: false }],
    }

    mountComponent()
    expect(profileStore.fetchRepairDirectoryOptions).not.toHaveBeenCalled()
  })

  it('renders the options from the store, pre-selecting current', () => {
    profileStore.repairDirectory.data = {
      current: 3,
      options: [
        { value: 0, key: 'profile.repair_dir_none', selected: false, disabled: false },
        { value: 3, key: 'profile.repair_dir_editor', selected: true, disabled: false },
      ],
    }

    const wrapper = mountComponent()
    expect(wrapper.find('[data-testid="repair-dir-select"]').element.value).toBe('3')
  })

  it('sends {role} to the target id via updateRepairDirectoryRole on save', async () => {
    profileStore.repairDirectory.data = {
      current: 0,
      options: [
        { value: 0, key: 'profile.repair_dir_none', selected: true, disabled: false },
        { value: 3, key: 'profile.repair_dir_editor', selected: false, disabled: false },
      ],
    }
    profileStore.updateRepairDirectoryRole = vi.fn().mockResolvedValue({ role: 3 })

    const wrapper = mountComponent({ targetId: 7 })
    await wrapper.find('[data-testid="repair-dir-select"]').setValue('3')
    await wrapper.find('[data-testid="repair-dir-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.updateRepairDirectoryRole).toHaveBeenCalledWith(7, 3)
  })

  it('shows an error state when the store has an error', () => {
    profileStore.repairDirectory.error = { status: 403 }

    const wrapper = mountComponent()
    expect(wrapper.find('[data-testid="repair-dir-error"]').exists()).toBe(true)
  })
})
