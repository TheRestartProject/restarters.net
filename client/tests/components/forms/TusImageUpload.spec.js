import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it, vi } from 'vitest'
import TusImageUpload from '../../../app/components/forms/TusImageUpload.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// Mock the whole @uppy/* stack the same way the folded-in PR #868
// ProfilePhotoTab.vue pattern uses it: the component's job is to wire
// Uppy's events to its own emits, not to actually upload files - see
// docs/nuxt-migration/api-gaps.md B6 for the "attach" endpoint this feeds
// into (POST /api/v2/groups/{id}/images, not yet implemented server-side).
let lastUppyInstance = null

class MockUppy {
  constructor() {
    this.handlers = {}
    this.usedPlugins = []
    this.files = []
    lastUppyInstance = this
  }

  use(Plugin, opts) {
    this.usedPlugins.push({ Plugin, opts })
    return this
  }

  on(event, handler) {
    this.handlers[event] = handler
    return this
  }

  clear() {}

  destroy() {}

  addFile(file) {
    const id = `file-${this.files.length}`
    this.files.push({ id, ...file })
    return id
  }

  getFiles() {
    return this.files
  }

  removeFile(id) {
    this.files = this.files.filter((f) => f.id !== id)
  }

  emitComplete(result) {
    this.handlers.complete?.(result)
  }

  emitUploadError(file, error) {
    this.handlers['upload-error']?.(file, error)
  }
}

vi.mock('@uppy/core', () => ({ default: MockUppy }))
vi.mock('@uppy/dashboard', () => ({ default: class Dashboard {} }))
vi.mock('@uppy/tus', () => ({ default: class Tus {} }))
vi.mock('@uppy/compressor', () => ({ default: class Compressor {} }))

async function mountUpload(props = {}) {
  vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'http://localhost:8001' } }))
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  const wrapper = mount(TusImageUpload, { props, global: { plugins: [i18n] } })
  // Flush the dynamic import()s + the state Vue sets afterwards.
  await Promise.resolve()
  await Promise.resolve()
  await new Promise((resolve) => setTimeout(resolve, 0))
  return wrapper
}

describe('components/forms/TusImageUpload', () => {
  it('renders a dashboard mount point and, when given one, the current image', async () => {
    const wrapper = await mountUpload({ currentImageUrl: '/uploads/mid_1.png' })
    expect(wrapper.find('[data-testid="tus-image-upload-dashboard"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="tus-image-upload-preview"]').attributes('src')).toBe('/uploads/mid_1.png')
  })

  it('wires the tus plugin to {apiBase}/api/tus', async () => {
    await mountUpload()
    const tusPlugin = lastUppyInstance.usedPlugins.find((p) => p.Plugin.name === 'Tus')
    expect(tusPlugin.opts.endpoint).toBe('http://localhost:8001/api/tus')
  })

  it('emits uploaded with the upload key parsed from the tus upload URL on complete', async () => {
    const wrapper = await mountUpload()

    lastUppyInstance.emitComplete({
      successful: [{ tus: { uploadUrl: 'http://localhost:8001/api/tus/abc123def' } }],
    })

    expect(wrapper.emitted('uploaded')[0][0]).toEqual({
      uploadKey: 'abc123def',
      file: { tus: { uploadUrl: 'http://localhost:8001/api/tus/abc123def' } },
    })
  })

  it('emits nothing when complete has no successful file (Uppy already reported upload-error separately)', async () => {
    const wrapper = await mountUpload()

    lastUppyInstance.emitComplete({ successful: [] })

    expect(wrapper.emitted('upload-error')).toBeFalsy()
    expect(wrapper.emitted('uploaded')).toBeFalsy()
  })

  it('emits upload-error on an uppy upload-error event', async () => {
    const wrapper = await mountUpload()

    lastUppyInstance.emitUploadError({}, { message: 'Network error' })

    expect(wrapper.emitted('upload-error')[0]).toEqual(['Network error'])
  })

  // findings/parity-v2/group-forms.md #13: GroupForm.vue's compact
  // 100x100 thumbnail picker, matching legacy GroupImage.vue's vue-dropzone
  // - a separate render path from the default Dashboard one above, so none
  // of those tests (or their consumers) are affected by this.
  describe('compact mode', () => {
    it('does not mount a Dashboard panel', async () => {
      const wrapper = await mountUpload({ compact: true })
      expect(wrapper.find('[data-testid="tus-image-upload-dashboard"]').exists()).toBe(false)
      expect(lastUppyInstance.usedPlugins.some((p) => p.Plugin.name === 'Dashboard')).toBe(false)
    })

    it('shows the current image, or the grey upload placeholder when there is none, in a 100x100 thumbnail', async () => {
      let wrapper = await mountUpload({ compact: true, currentImageUrl: '/uploads/mid_1.png' })
      expect(wrapper.find('[data-testid="tus-image-upload-preview"]').attributes('src')).toBe('/uploads/mid_1.png')

      wrapper = await mountUpload({ compact: true })
      expect(wrapper.find('[data-testid="tus-image-upload-preview"]').attributes('src')).toBe('/images/upload_ico_grey.svg')
    })

    it('opens the file picker when the thumbnail is clicked', async () => {
      const wrapper = await mountUpload({ compact: true })
      const input = wrapper.find('[data-testid="tus-image-upload-file-input"]').element
      const clickSpy = vi.spyOn(input, 'click')

      await wrapper.find('[data-testid="tus-image-upload-dropzone"]').trigger('click')

      expect(clickSpy).toHaveBeenCalled()
    })

    it('shows an instant local preview and calls uppy.addFile when a file is picked', async () => {
      const wrapper = await mountUpload({ compact: true })
      const file = new File(['x'], 'photo.png', { type: 'image/png' })
      const addFileSpy = vi.spyOn(lastUppyInstance, 'addFile')
      vi.stubGlobal('URL', { ...URL, createObjectURL: vi.fn().mockReturnValue('blob:local-preview') })

      const input = wrapper.find('[data-testid="tus-image-upload-file-input"]')
      Object.defineProperty(input.element, 'files', { value: [file], configurable: true })
      await input.trigger('change')

      expect(addFileSpy).toHaveBeenCalledWith(expect.objectContaining({ name: 'photo.png', type: 'image/png' }))
      expect(wrapper.find('[data-testid="tus-image-upload-preview"]').attributes('src')).toBe('blob:local-preview')
    })

    it('shows a spinner while uploading, that clears on complete', async () => {
      const wrapper = await mountUpload({ compact: true })

      lastUppyInstance.handlers.upload?.()
      await wrapper.vm.$nextTick()
      expect(wrapper.find('.tus-image-upload__thumb-spinner').exists()).toBe(true)

      lastUppyInstance.emitComplete({ successful: [{ tus: { uploadUrl: 'http://localhost:8001/api/tus/abc123' } }] })
      await wrapper.vm.$nextTick()
      expect(wrapper.find('.tus-image-upload__thumb-spinner').exists()).toBe(false)
    })

    // Legacy GroupImage.vue's .deleteme button - v-if="currentimage" there
    // (only once a file has actually been picked, never for a pre-existing
    // currentImageUrl loaded from the server).
    describe('remove button (GroupImage.vue deleteMe() parity)', () => {
      async function pickFile(wrapper) {
        vi.stubGlobal('URL', { ...URL, createObjectURL: vi.fn().mockReturnValue('blob:local-preview'), revokeObjectURL: vi.fn() })
        const file = new File(['x'], 'photo.png', { type: 'image/png' })
        const input = wrapper.find('[data-testid="tus-image-upload-file-input"]')
        Object.defineProperty(input.element, 'files', { value: [file], configurable: true })
        await input.trigger('change')
      }

      it('is absent for a pre-existing image, and appears once a file is picked', async () => {
        const wrapper = await mountUpload({ compact: true, currentImageUrl: '/uploads/mid_1.png' })
        expect(wrapper.find('[data-testid="tus-image-upload-remove"]').exists()).toBe(false)

        await pickFile(wrapper)

        expect(wrapper.find('[data-testid="tus-image-upload-remove"]').exists()).toBe(true)
      })

      it('clears the preview and the uppy file, re-shows the placeholder, and emits remove', async () => {
        const wrapper = await mountUpload({ compact: true, currentImageUrl: '/uploads/mid_1.png' })
        await pickFile(wrapper)
        expect(lastUppyInstance.getFiles()).toHaveLength(1)

        await wrapper.find('[data-testid="tus-image-upload-remove"]').trigger('click')

        expect(wrapper.find('[data-testid="tus-image-upload-remove"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="tus-image-upload-preview"]').attributes('src')).toBe('/uploads/mid_1.png')
        expect(lastUppyInstance.getFiles()).toHaveLength(0)
        expect(wrapper.emitted('remove')).toBeTruthy()
      })

      it('does not reopen the file picker (the click must not bubble to the dropzone)', async () => {
        const wrapper = await mountUpload({ compact: true })
        await pickFile(wrapper)
        const input = wrapper.find('[data-testid="tus-image-upload-file-input"]').element
        const clickSpy = vi.spyOn(input, 'click')

        await wrapper.find('[data-testid="tus-image-upload-remove"]').trigger('click')

        expect(clickSpy).not.toHaveBeenCalled()
      })
    })
  })
})
