import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import ConfirmModal from '../../app/components/ConfirmModal.vue'
import { BButtonStub, BModalStub } from '../helpers/stubs.js'
import en from '../../i18n/locales/en.json'

function mountModal(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })
  return mount(ConfirmModal, {
    props: { modelValue: true, ...props },
    global: { plugins: [i18n], stubs: { BModal: BModalStub, BButton: BButtonStub } },
  })
}

describe('components/ConfirmModal', () => {
  it('shows live\'s default message and Cancel/Confirm buttons', () => {
    const wrapper = mountModal()
    expect(wrapper.text()).toContain('Please confirm that you wish to proceed.')
    expect(wrapper.find('[data-testid="confirm-modal-cancel"]').text()).toBe('Cancel')
    expect(wrapper.find('[data-testid="confirm-modal-confirm"]').text()).toBe('Confirm')
  })

  it('uses a supplied message', () => {
    expect(mountModal({ message: 'Please confirm that you want to delete X.' }).text()).toContain('delete X')
  })

  it('emits confirm then closes on Confirm, and only closes on Cancel', async () => {
    const wrapper = mountModal()
    await wrapper.find('[data-testid="confirm-modal-cancel"]').trigger('click')
    expect(wrapper.emitted('confirm')).toBeUndefined()
    expect(wrapper.emitted('update:modelValue')).toEqual([[false]])

    await wrapper.find('[data-testid="confirm-modal-confirm"]').trigger('click')
    expect(wrapper.emitted('confirm')).toHaveLength(1)
  })
})
