import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DevicePhotos from '../../../app/components/devices/DevicePhotos.vue'
import { useDevicesStore } from '../../../app/stores/devices.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
// Same shape as DeviceRow.spec.js/DevicesSearchTable.spec.js's BModalStub -
// only renders its slot (+ footer slot, for the zoom modal's Close button)
// while open.
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-modal-title="title"><slot /><slot name="footer" /></div>',
}
const TusImageUploadStub = {
  emits: ['uploaded', 'upload-error'],
  template: '<div data-testid="tus-image-upload-stub" />',
}

function mountPhotos(props = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        // partials.please_confirm/confirm (gap: DeviceImage.vue's bare
        // <ConfirmModal> falls back to ConfirmModal's own defaults) aren't
        // in the checked-in i18n/locales fixtures yet - overlay them here,
        // same convention as DeviceForm.spec.js's mountForm().
        partials: {
          ...en.partials,
          please_confirm: 'Please confirm that you wish to proceed.',
          confirm: 'Confirm',
        },
      },
    },
  })

  return mount(DevicePhotos, {
    props: { eventId: 5, deviceId: 7, images: [], ...props },
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BButton: BButtonStub, BModal: BModalStub, TusImageUpload: TusImageUploadStub },
    },
  })
}

describe('components/devices/DevicePhotos', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useNuxtApp', () => ({ $api: { device: {}, event: {} } }))
  })

  it('renders one thumbnail per image, keyed by idxref', () => {
    const wrapper = mountPhotos({ images: [{ idxref: 1, path: 'a.jpg' }, { idxref: 2, path: 'b.jpg' }] })
    expect(wrapper.findAll('[data-testid="device-photo"]')).toHaveLength(2)
    expect(wrapper.find('[data-testid="device-photo-remove-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="device-photo-remove-2"]').exists()).toBe(true)
  })

  it('uploads via the store, keyed by eventId/deviceId', async () => {
    const store = useDevicesStore()
    store.uploadDeviceImage = vi.fn().mockResolvedValue({ image_url: 'x' })

    const wrapper = mountPhotos()
    await wrapper.findComponent(TusImageUploadStub).vm.$emit('uploaded', { uploadKey: 'key-1' })

    expect(store.uploadDeviceImage).toHaveBeenCalledWith(5, 7, 'key-1')
  })

  it('shows an error message when upload fails', async () => {
    const store = useDevicesStore()
    store.uploadDeviceImage = vi.fn().mockRejectedValue({ status: 500 })

    const wrapper = mountPhotos()
    await wrapper.findComponent(TusImageUploadStub).vm.$emit('uploaded', { uploadKey: 'key-1' })
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="device-photos-error"]').exists()).toBe(true)
  })

  it('surfaces the upload widget error message directly', async () => {
    const wrapper = mountPhotos()
    await wrapper.findComponent(TusImageUploadStub).vm.$emit('upload-error', 'Too large')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="device-photos-error"]').text()).toBe('Too large')
  })

  // Gap fix (dropped-behaviour finding): DeviceImage.vue gates its remove
  // button behind a bare <ConfirmModal @confirm="remove" ref="confirm"/> -
  // clicking remove must NOT call the store until the modal is confirmed.
  describe('delete confirmation (DeviceImage.vue: ConfirmModal before remove)', () => {
    it('does not delete until the confirm modal is accepted, keyed by idxref (not the image id)', async () => {
      const store = useDevicesStore()
      store.deleteDeviceImage = vi.fn().mockResolvedValue({ deleted: true })

      const wrapper = mountPhotos({ images: [{ idxref: 9, path: 'a.jpg' }] })

      expect(wrapper.find('[data-modal-title]').exists()).toBe(false)
      await wrapper.find('[data-testid="device-photo-remove-9"]').trigger('click')
      expect(store.deleteDeviceImage).not.toHaveBeenCalled()
      expect(wrapper.find('[data-modal-title]').exists()).toBe(true)

      await wrapper.find('[data-testid="device-photo-delete-confirm"]').trigger('click')
      await wrapper.vm.$nextTick()

      expect(store.deleteDeviceImage).toHaveBeenCalledWith(5, 7, 9)
      expect(wrapper.find('[data-modal-title]').exists()).toBe(false)
    })

    it('does not call the store when the delete confirm is cancelled', async () => {
      const store = useDevicesStore()
      store.deleteDeviceImage = vi.fn().mockResolvedValue({ deleted: true })

      const wrapper = mountPhotos({ images: [{ idxref: 9, path: 'a.jpg' }] })

      await wrapper.find('[data-testid="device-photo-remove-9"]').trigger('click')
      await wrapper.find('[data-testid="device-photo-delete-cancel"]').trigger('click')

      expect(store.deleteDeviceImage).not.toHaveBeenCalled()
      expect(wrapper.find('[data-modal-title]').exists()).toBe(false)
    })
  })

  // Gap fix (dropped-behaviour finding): DeviceImage.vue's thumbnail opens
  // DeviceImageModal (full-size) on click, matching EventImagesGallery.vue's
  // own zoom/BModal pattern.
  describe('zoom (DeviceImage.vue: click opens DeviceImageModal)', () => {
    it('opens a modal with the full-size image when a thumbnail is clicked', async () => {
      const wrapper = mountPhotos({ images: [{ idxref: 9, path: 'a.jpg' }] })

      expect(wrapper.find('[data-testid="device-photo-zoom-modal"]').exists()).toBe(false)
      await wrapper.find('[data-testid="device-photo-thumb"]').trigger('click')

      const modal = wrapper.find('[data-testid="device-photo-zoom-modal"]')
      expect(modal.exists()).toBe(true)
      expect(modal.find('img').attributes('src')).toContain('a.jpg')
    })

    it('closes when Close is clicked', async () => {
      const wrapper = mountPhotos({ images: [{ idxref: 9, path: 'a.jpg' }] })

      await wrapper.find('[data-testid="device-photo-thumb"]').trigger('click')
      await wrapper.find('[data-testid="device-photo-zoom-close"]').trigger('click')

      expect(wrapper.find('[data-testid="device-photo-zoom-modal"]').exists()).toBe(false)
    })
  })

  // Gap fix (HIGH): DeviceForm.vue's readonly rendering (DevicesSearchTable's
  // row-details panel for non-admins) hides the remove button and upload
  // widget, leaving just the existing photos - EventDevice.vue passes its
  // own `disabled` straight through to DeviceImages the same way.
  describe('readonly', () => {
    it('hides the remove button and the upload widget, but keeps the photos', () => {
      const wrapper = mountPhotos({ images: [{ idxref: 1, path: 'a.jpg' }], readonly: true })

      expect(wrapper.findAll('[data-testid="device-photo"]')).toHaveLength(1)
      expect(wrapper.find('[data-testid="device-photo-remove-1"]').exists()).toBe(false)
      expect(wrapper.findComponent(TusImageUploadStub).exists()).toBe(false)
    })
  })

  describe('5-image cap (gap 14)', () => {
    it('shows the upload widget when under the limit', () => {
      const wrapper = mountPhotos({ images: [{ idxref: 1, path: 'a.jpg' }] })
      expect(wrapper.findComponent(TusImageUploadStub).exists()).toBe(true)
    })

    it('hides the upload widget once 5 images already exist', () => {
      const images = Array.from({ length: 5 }, (_, i) => ({ idxref: i + 1, path: `${i}.jpg` }))
      const wrapper = mountPhotos({ images })
      expect(wrapper.findComponent(TusImageUploadStub).exists()).toBe(false)
      expect(wrapper.findAll('[data-testid="device-photo"]')).toHaveLength(5)
    })
  })
})
