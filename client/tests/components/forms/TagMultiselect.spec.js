import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import TagMultiselect from '../../../app/components/forms/TagMultiselect.vue'

// Replaces the native <select multiple> that stood in for develop's
// vue-multiselect. The behaviours asserted here are the ones a native select
// does not have, and whose absence was the substitution's real cost: chips you
// can remove individually, type-to-filter, and free-text entry.
function mountPicker(props = {}) {
  return mount(TagMultiselect, { props: { options: [], ...props } })
}

const MEMBERS = [
  { email: 'ada@example.test', name: 'Ada' },
  { email: 'grace@example.test', name: 'Grace' },
]

describe('components/forms/TagMultiselect', () => {
  it('renders a removable chip per selected value, labelled from the option', async () => {
    const wrapper = mountPicker({
      options: MEMBERS,
      trackBy: 'email',
      labelBy: 'name',
      modelValue: ['ada@example.test'],
    })

    const chip = wrapper.find('[data-testid="tag-multiselect-tag-ada@example.test"]')
    expect(chip.text()).toContain('Ada')

    await wrapper.find('[data-testid="tag-multiselect-remove-ada@example.test"]').trigger('click')
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([[]])
  })

  it('filters the options as you type, and hides ones already chosen', async () => {
    const wrapper = mountPicker({
      options: MEMBERS,
      trackBy: 'email',
      labelBy: 'name',
      modelValue: ['ada@example.test'],
    })

    const input = wrapper.find('[data-testid="tag-multiselect-input"]')
    await input.trigger('focus')
    await input.setValue('gr')
    expect(wrapper.find('[data-testid="tag-multiselect-option-grace@example.test"]').exists()).toBe(true)
    // Ada is both filtered out by the term and already selected.
    expect(wrapper.find('[data-testid="tag-multiselect-option-ada@example.test"]').exists()).toBe(false)
  })

  it('selects the highlighted option on enter', async () => {
    const wrapper = mountPicker({ options: MEMBERS, trackBy: 'email', labelBy: 'name', modelValue: [] })
    const input = wrapper.find('[data-testid="tag-multiselect-input"]')

    await input.setValue('grace')
    await input.trigger('keydown.enter')

    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([['grace@example.test']])
  })

  it('accepts free text when taggable, which a native select cannot do at all', async () => {
    const wrapper = mountPicker({ taggable: true, modelValue: [] })
    const input = wrapper.find('[data-testid="tag-multiselect-input"]')

    await input.setValue('someone@example.test')
    await input.trigger('keydown.enter')

    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([['someone@example.test']])
    expect(wrapper.emitted('tag').at(-1)).toEqual(['someone@example.test'])
  })

  it('removes the last chip on backspace only once the search box is empty', async () => {
    const wrapper = mountPicker({ taggable: true, modelValue: ['a@example.test', 'b@example.test'] })
    const input = wrapper.find('[data-testid="tag-multiselect-input"]')

    await input.setValue('stil')
    await input.trigger('keydown.delete')
    expect(wrapper.emitted('update:modelValue')).toBeUndefined()

    await input.setValue('')
    await input.trigger('keydown.delete')
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([['a@example.test']])
  })

  it('flags values the caller considers invalid', () => {
    const wrapper = mountPicker({
      taggable: true,
      modelValue: ['good@example.test', 'nope'],
      invalidValues: ['nope'],
    })

    expect(wrapper.find('[data-testid="tag-multiselect-tag-nope"]').classes()).toContain('multiselect__tag--invalid')
    expect(wrapper.find('[data-testid="tag-multiselect-tag-good@example.test"]').classes())
      .not.toContain('multiselect__tag--invalid')
  })

  it('emits a scalar rather than an array in single-select mode', async () => {
    const wrapper = mountPicker({ options: MEMBERS, trackBy: 'email', labelBy: 'name', multiple: false, modelValue: null })

    await wrapper.find('[data-testid="tag-multiselect-input"]').trigger('focus')
    await wrapper.find('[data-testid="tag-multiselect-option-ada@example.test"]').trigger('mousedown')

    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['ada@example.test'])
  })
})
