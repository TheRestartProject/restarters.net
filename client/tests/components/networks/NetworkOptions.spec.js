import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkOptions from '../../../app/components/networks/NetworkOptions.vue'
import { useNetworksStore } from '../../../app/stores/networks.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NETWORK = {
  id: 1,
  name: 'Repair Together',
  features: { item_weight: null, custom_fields: 'default_on' },
  feature_defaults: { item_weight: true, custom_fields: false },
}

function mountOptions(network = NETWORK) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  return mount(NetworkOptions, {
    props: { network },
    global: { plugins: [i18n], stubs: { BAlert: { template: '<div v-bind="$attrs"><slot /></div>' }, BButton: { template: '<button v-bind="$attrs"><slot /></button>' } } },
  })
}

describe('components/networks/NetworkOptions', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useNetworksStore()
    store.updateOptions = vi.fn().mockResolvedValue({})
  })

  it('has the section title and a select per feature with the five choices', () => {
    const wrapper = mountOptions()

    expect(wrapper.find('[data-testid="network-options-title"]').text()).toBe('Options for groups in this network')
    const select = wrapper.find('[data-testid="network-option-item_weight"]')
    expect(select.findAll('option').map((o) => o.text())).toEqual([
      'Not set',
      'Always on',
      'Always off',
      'On by default (groups can change)',
      'Off by default (groups can change)',
    ])
    expect(wrapper.find('[data-testid="network-option-item_weight"]').element.value).toBe('')
    expect(wrapper.find('[data-testid="network-option-custom_fields"]').element.value).toBe('default_on')
  })

  it('saves every feature, sending null for Not set', async () => {
    const wrapper = mountOptions()

    await wrapper.find('[data-testid="network-option-item_weight"]').setValue('off')
    await wrapper.find('[data-testid="network-option-custom_fields"]').setValue('')
    await wrapper.find('[data-testid="network-options-form"]').trigger('submit')
    await flushPromises()

    expect(store.updateOptions).toHaveBeenCalledWith(1, { features: { item_weight: 'off', custom_fields: null } })
    expect(wrapper.find('[data-testid="network-options-saved"]').exists()).toBe(true)
  })

  it('shows an error when saving fails', async () => {
    store.updateOptions = vi.fn().mockRejectedValue(new Error('403'))
    const wrapper = mountOptions()

    await wrapper.find('[data-testid="network-options-form"]').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[data-testid="network-options-error"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="network-options-saved"]').exists()).toBe(false)
  })
})
