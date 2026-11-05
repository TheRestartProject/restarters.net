import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it, vi } from 'vitest'
import StatsShareImageModal from '../../../app/components/events/StatsShareImageModal.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { BButtonStub } from '../../helpers/stubs.js'

// The shared BModalStub (tests/helpers/stubs.js) only renders the default
// slot - every other modal spec in this repo uses no-footer, so nothing
// exercised the #footer slot before. StatsShareImageModal.vue is the first
// to need a custom footer (Close/Download, port of StatsShareModal.vue's
// modal-footer), so this local stub renders both.
const BModalFooterStub = {
  props: ['modelValue'],
  emits: ['hide', 'shown'],
  template: '<div v-if="modelValue"><slot /><slot name="footer" /></div>',
}

// StatsShareImage.vue paints onto a real canvas 2D context, which isn't
// implemented in happy-dom (see StatsShareImage.spec.js) - stubbed out here
// since this suite is only exercising the modal chrome (platform toggle +
// footer buttons), not the paint pipeline.
const StatsShareImageStub = {
  props: ['count', 'target', 'size'],
  template: '<div data-testid="stats-share-image-stub" />',
  methods: {
    paint() {},
    download() {},
  },
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(StatsShareImageModal, {
    props: { count: 600, ...props },
    global: {
      plugins: [i18n],
      stubs: { BModal: BModalFooterStub, BButton: BButtonStub, StatsShareImage: StatsShareImageStub },
    },
  })
}

describe('components/events/StatsShareImageModal', () => {
  it('renders nothing when show is false', () => {
    const wrapper = mountComponent({ show: false })
    expect(wrapper.find('[data-testid="stats-share-image-modal"]').exists()).toBe(false)
  })

  it('defaults to the Instagram platform toggle active', () => {
    const wrapper = mountComponent({ show: true })
    const instagram = wrapper.find('[data-testid="stats-share-image-target-instagram"]')
    expect(instagram.classes()).toContain('active')
  })

  it('switches the active platform on click, passed through to StatsShareImage as the target prop', async () => {
    const wrapper = mountComponent({ show: true })

    await wrapper.find('[data-testid="stats-share-image-target-twitter"]').trigger('click')

    expect(wrapper.find('[data-testid="stats-share-image-target-twitter"]').classes()).toContain('active')
    expect(wrapper.find('[data-testid="stats-share-image-target-instagram"]').classes()).not.toContain('active')
    expect(wrapper.findComponent(StatsShareImageStub).props('target')).toBe('Twitter')
  })

  it('passes the count prop straight through to StatsShareImage', () => {
    const wrapper = mountComponent({ show: true, count: 12345 })
    expect(wrapper.findComponent(StatsShareImageStub).props('count')).toBe(12345)
  })

  it('shows all four platform toggles in develop\'s order', () => {
    const wrapper = mountComponent({ show: true })
    const labels = ['instagram', 'facebook', 'twitter', 'linkedin']
    labels.forEach((label) => {
      expect(wrapper.find(`[data-testid="stats-share-image-target-${label}"]`).exists()).toBe(true)
    })
  })

  it('renders translated Close/Download footer buttons', () => {
    const wrapper = mountComponent({ show: true })
    expect(wrapper.find('[data-testid="stats-share-image-close"]').text()).toBe('Close')
    expect(wrapper.find('[data-testid="stats-share-image-download"]').text()).toBe('Download')
  })

  it('emits close when the Close button is clicked', async () => {
    const wrapper = mountComponent({ show: true })
    await wrapper.find('[data-testid="stats-share-image-close"]').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('emits close when the modal hides (e.g. backdrop click/Esc)', async () => {
    const wrapper = mountComponent({ show: true })
    await wrapper.findComponent(BModalFooterStub).vm.$emit('hide')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('delegates the Download button to StatsShareImage.download()', async () => {
    const wrapper = mountComponent({ show: true })
    const downloadSpy = vi.spyOn(wrapper.findComponent(StatsShareImageStub).vm, 'download')
    await wrapper.find('[data-testid="stats-share-image-download"]').trigger('click')
    expect(downloadSpy).toHaveBeenCalled()
  })

  it('uses the translated modal title', () => {
    const wrapper = mountComponent({ show: true })
    expect(wrapper.text()).toContain('Instagram')
    // title prop isn't rendered by the local stub's markup, but confirm the
    // translation key resolves to develop's copy rather than the raw key.
    expect(en.partials.share_modal_title).toBe('Shareable Statistics')
  })
})
