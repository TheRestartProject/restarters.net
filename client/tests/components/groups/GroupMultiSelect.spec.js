import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupMultiSelect from '../../../app/components/groups/GroupMultiSelect.vue'
import en from '../../../i18n/locales/en.json'

// Group-form-only replacement for legacy vue-multiselect (findings/
// parity-v2/group-forms.md #6): searchable dropdown + removable chips,
// optionally grouped under network-name headings with "Global" first.
function mountSelect(props) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })

  return mount(GroupMultiSelect, {
    props,
    global: { plugins: [i18n] },
  })
}

describe('components/groups/GroupMultiSelect', () => {
  const OPTIONS = [
    { value: 1, text: 'UK Network' },
    { value: 2, text: 'Ireland Network' },
  ]

  it('renders a chip for each selected value', () => {
    const wrapper = mountSelect({ modelValue: [1], options: OPTIONS })
    expect(wrapper.find('[data-testid="group-multiselect-chip-1"]').text()).toContain('UK Network')
    expect(wrapper.find('[data-testid="group-multiselect-chip-2"]').exists()).toBe(false)
  })

  it('shows matching, not-yet-selected options while typing, and selects on click', async () => {
    const wrapper = mountSelect({ modelValue: [], options: OPTIONS })

    await wrapper.find('[data-testid="group-multiselect-search"]').trigger('focus')
    await wrapper.find('[data-testid="group-multiselect-search"]').setValue('ire')

    expect(wrapper.find('[data-testid="group-multiselect-option-1"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-multiselect-option-2"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-multiselect-option-2"]').trigger('mousedown')
    expect(wrapper.emitted('update:modelValue')).toEqual([[[2]]])
  })

  it('removes a value via its chip remove button', async () => {
    const wrapper = mountSelect({ modelValue: [1, 2], options: OPTIONS })
    await wrapper.find('[data-testid="group-multiselect-remove-1"]').trigger('click')
    expect(wrapper.emitted('update:modelValue')).toEqual([[[2]]])
  })

  it('groups options under network-name headings with Global first (matches groupedTagOptions)', async () => {
    const grouped = [
      { value: 1, text: 'Scotland', group: 'MRES' },
      { value: 2, text: 'General', group: 'Global' },
      { value: 3, text: 'Amsterdam', group: 'BENELUX' },
    ]
    const wrapper = mountSelect({ modelValue: [], options: grouped })
    await wrapper.find('[data-testid="group-multiselect-search"]').trigger('focus')

    const headings = wrapper.findAll('.list-group-item.disabled').map((el) => el.text())
    expect(headings).toEqual(['Global', 'BENELUX', 'MRES'])
  })

  it('leaves options ungrouped (no heading) when they carry no group', async () => {
    const wrapper = mountSelect({ modelValue: [], options: OPTIONS })
    await wrapper.find('[data-testid="group-multiselect-search"]').trigger('focus')

    expect(wrapper.findAll('.list-group-item.disabled').length).toBe(0)
    expect(wrapper.find('[data-testid="group-multiselect-option-1"]').exists()).toBe(true)
  })
})
