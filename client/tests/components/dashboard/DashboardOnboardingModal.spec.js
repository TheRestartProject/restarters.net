import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import DashboardOnboardingModal from '../../../app/components/dashboard/DashboardOnboardingModal.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue" data-testid="modal-stub"><slot /></div>',
}

const BButtonStub = {
  template: '<button v-bind="$attrs"><slot /></button>',
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DashboardOnboardingModal, {
    props,
    global: {
      plugins: [i18n],
      stubs: { BModal: BModalStub, BButton: BButtonStub },
    },
  })
}

describe('components/dashboard/DashboardOnboardingModal', () => {
  // app.blade.php describes each onboarding image; alt="" would declare
  // these content images decorative and tell a screen reader nothing.
  it('describes each slide image, and changes the description with the slide', async () => {
    const wrapper = mountComponent({ show: true })

    const alt = () => wrapper.find('img.rounded-circle').attributes('alt')
    expect(alt()).toBe(clientEn.client.onboarding.slide1_alt)
    expect(alt()).not.toBe('')

    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    expect(alt()).toBe(clientEn.client.onboarding.slide2_alt)
  })

  it('does not render when show is false', () => {
    const wrapper = mountComponent({ show: false })
    expect(wrapper.find('[data-testid="modal-stub"]').exists()).toBe(false)
  })

  it('renders the first slide when show is true', () => {
    const wrapper = mountComponent({ show: true })
    expect(wrapper.find('[data-testid="onboarding-slide-0"]').exists()).toBe(true)
  })

  it('advances through slides with next and back with previous', async () => {
    const wrapper = mountComponent({ show: true })

    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    expect(wrapper.find('[data-testid="onboarding-slide-1"]').exists()).toBe(true)

    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    expect(wrapper.find('[data-testid="onboarding-slide-2"]').exists()).toBe(true)
    // Last slide: no "next", a "finish" button instead.
    expect(wrapper.find('[data-testid="onboarding-next"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="onboarding-finish"]').exists()).toBe(true)

    await wrapper.find('[data-testid="onboarding-previous"]').trigger('click')
    expect(wrapper.find('[data-testid="onboarding-slide-1"]').exists()).toBe(true)
  })

  // Parity gap #19: legacy's modal-prev is a slick arrow - slick's
  // `slick-disabled` class fully removes it (display: none) on the first
  // slide rather than just disabling it.
  it('hides the previous button entirely on the first slide, not just disables it', () => {
    const wrapper = mountComponent({ show: true })

    expect(wrapper.find('[data-testid="onboarding-previous"]').exists()).toBe(false)
  })

  // Parity gap #19: legacy's close (X) only gains `.close--visible` once the
  // slick carousel reports the last slide (beforeChange, nextSlide === 2) -
  // it's not shown throughout.
  it('shows the close button only on the last slide', async () => {
    const wrapper = mountComponent({ show: true })

    expect(wrapper.find('[data-testid="onboarding-close"]').exists()).toBe(false)

    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    expect(wrapper.find('[data-testid="onboarding-close"]').exists()).toBe(false)

    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    expect(wrapper.find('[data-testid="onboarding-close"]').exists()).toBe(true)
  })

  it('emits dismiss when the close button is clicked on the last slide', async () => {
    const wrapper = mountComponent({ show: true })

    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')

    await wrapper.find('[data-testid="onboarding-close"]').trigger('click')

    expect(wrapper.emitted('dismiss')).toHaveLength(1)
  })

  it('emits dismiss when the finish button on the last slide is clicked', async () => {
    const wrapper = mountComponent({ show: true })

    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    await wrapper.find('[data-testid="onboarding-next"]').trigger('click')
    await wrapper.find('[data-testid="onboarding-finish"]').trigger('click')

    expect(wrapper.emitted('dismiss')).toHaveLength(1)
  })
})
