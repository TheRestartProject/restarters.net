import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AssociateGroupsModal from '../../../app/components/networks/AssociateGroupsModal.vue'
import { useNetworksStore } from '../../../app/stores/networks.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-title="title"><slot /></div>',
}
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot /></div>' }

const GLOBAL_STUBS = {
  BModal: BModalStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
}

function selectOptions(wrapper, ids) {
  const select = wrapper.find('[data-testid="network-associate-groups-select"]')
  select.element.querySelectorAll('option').forEach((option) => {
    option.selected = ids.includes(Number(option.value))
  })
  return select.trigger('change')
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(AssociateGroupsModal, {
    props: {
      show: true,
      networkId: 1,
      networkName: 'Test London',
      candidates: [
        { id: 5, name: 'Alpha Group' },
        { id: 6, name: 'Beta Group' },
      ],
      ...props,
    },
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('components/networks/AssociateGroupsModal', () => {
  let networksStore

  beforeEach(() => {
    setActivePinia(createPinia())
    networksStore = useNetworksStore()
    networksStore.associateGroups = vi.fn().mockResolvedValue({})
  })

  it('lists the candidate groups as select options', () => {
    const wrapper = mountComponent()
    const options = wrapper.findAll('[data-testid="network-associate-groups-select"] option')

    expect(options).toHaveLength(2)
    expect(options[0].text()).toBe('Alpha Group')
  })

  it('shows a warning and does not submit when nothing is selected', async () => {
    const wrapper = mountComponent()

    await wrapper.find('[data-testid="network-associate-groups-form"]').trigger('submit')

    expect(networksStore.associateGroups).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="network-associate-groups-error"]').exists()).toBe(true)
  })

  it('submits the selected group ids and shows a success message', async () => {
    const wrapper = mountComponent()

    await selectOptions(wrapper, [5, 6])
    await wrapper.find('[data-testid="network-associate-groups-form"]').trigger('submit')
    await wrapper.vm.$nextTick()
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(networksStore.associateGroups).toHaveBeenCalledWith(1, [5, 6])
    expect(wrapper.find('[data-testid="network-associate-groups-success"]').exists()).toBe(true)
  })

  it('shows the API error message on failure', async () => {
    networksStore.associateGroups = vi.fn().mockRejectedValue({ data: { message: 'Group already in network' } })
    const wrapper = mountComponent()

    await selectOptions(wrapper, [5])
    await wrapper.find('[data-testid="network-associate-groups-form"]').trigger('submit')
    await wrapper.vm.$nextTick()
    await new Promise((resolve) => setTimeout(resolve, 0))

    expect(wrapper.find('[data-testid="network-associate-groups-error"]').text()).toContain('Group already in network')
  })

  it('resets state and emits close on cancel', async () => {
    const wrapper = mountComponent()
    await wrapper.find('[data-testid="network-associate-groups-cancel"]').trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
