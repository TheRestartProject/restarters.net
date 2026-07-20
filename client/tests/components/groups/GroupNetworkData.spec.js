import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupNetworkData from '../../../app/components/groups/GroupNetworkData.vue'

// Ports resources/js/components/NetworkData.vue + NetworkDataField.vue
// (findings/parity-v2/group-forms.md #5): admin-only editor for a group's
// arbitrary network_data key/value pairs.
const BFormGroupStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs" @click="$emit(\'click\')"><slot /></button>' }

function mountEditor(props) {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        networks: {
          edit: {
            add_new_field: 'Add new field',
            new_field_name: 'New field name',
            add_field: 'Add field',
          },
        },
      },
    },
  })

  return mount(GroupNetworkData, {
    props,
    global: {
      plugins: [i18n],
      stubs: { BFormGroup: BFormGroupStub, BButton: BButtonStub },
    },
  })
}

describe('components/groups/GroupNetworkData', () => {
  it('renders an editable field for each existing key', () => {
    const wrapper = mountEditor({ modelValue: { colour: 'blue', size: null } })
    expect(wrapper.find('[data-testid="group-network-data-field-colour"]').element.value).toBe('blue')
    expect(wrapper.find('[data-testid="group-network-data-field-size"]').element.value).toBe('')
  })

  it('emits an updated object when an existing field changes', async () => {
    const wrapper = mountEditor({ modelValue: { colour: 'blue' } })
    await wrapper.find('[data-testid="group-network-data-field-colour"]').setValue('red')

    expect(wrapper.emitted('update:modelValue')[0][0]).toEqual({ colour: 'red' })
  })

  it('reveals a label input + add-field button, and emits the new key on add', async () => {
    const wrapper = mountEditor({ modelValue: {} })
    expect(wrapper.find('[data-testid="group-network-data-new-label"]').exists()).toBe(false)

    await wrapper.find('[data-testid="group-network-data-add-new"]').trigger('click')
    expect(wrapper.find('[data-testid="group-network-data-new-label"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-network-data-new-label"]').setValue('shape')
    await wrapper.find('[data-testid="group-network-data-add-field"]').trigger('click')

    expect(wrapper.emitted('update:modelValue').at(-1)[0]).toEqual({ shape: null })
  })

  it('does not add a field with a blank label', async () => {
    const wrapper = mountEditor({ modelValue: { colour: 'blue' } })
    await wrapper.find('[data-testid="group-network-data-add-new"]').trigger('click')
    await wrapper.find('[data-testid="group-network-data-add-field"]').trigger('click')

    expect(wrapper.emitted('update:modelValue')).toBeUndefined()
  })
})
