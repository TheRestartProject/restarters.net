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
})
