import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DevicePhotos from '../../../app/components/devices/DevicePhotos.vue'
import { useDevicesStore } from '../../../app/stores/devices.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const TusImageUploadStub = {
  emits: ['uploaded', 'upload-error'],
  template: '<div data-testid="tus-image-upload-stub" />',
}

function mountPhotos(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DevicePhotos, {
    props: { eventId: 5, deviceId: 7, images: [], ...props },
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, TusImageUpload: TusImageUploadStub },
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

  it('deletes an image via the store, keyed by idxref (not the image id)', async () => {
    const store = useDevicesStore()
    store.deleteDeviceImage = vi.fn().mockResolvedValue({ deleted: true })

    const wrapper = mountPhotos({ images: [{ idxref: 9, path: 'a.jpg' }] })
    await wrapper.find('[data-testid="device-photo-remove-9"]').trigger('click')

    expect(store.deleteDeviceImage).toHaveBeenCalledWith(5, 7, 9)
  })
})
