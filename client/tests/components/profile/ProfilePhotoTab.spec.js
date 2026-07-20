import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ProfilePhotoTab from '../../../app/components/profile/ProfilePhotoTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import { useSessionStore } from '../../../app/stores/session.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// Uppy itself is exercised by tests/components/forms/TusImageUpload.spec.js
// (mocked @uppy/* stack). This component only needs to prove it wires
// TusImageUpload's `uploaded`/`upload-error` emits to
// stores/profile.js#uploadPhoto correctly - so TusImageUpload is stubbed
// out entirely here, same convention as tests/pages/group/edit.spec.js's
// TusImageUploadStub.
const BAlertStub = { template: '<div><slot /></div>' }


const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

describe('components/profile/ProfilePhotoTab', () => {
  // user/profile/profile.blade.php:138-145 - a labelled file input and an
  // explicit CHANGE MY PHOTO submit. This used to be a TusImageUpload
  // dropzone that uploaded the moment a file was dropped; those tests went
  // with it. POST /api/v2/users/me/photo accepts multipart as well as a tus
  // upload_key and shares every validation between them.
  beforeEach(() => {
    setActivePinia(createPinia())
    const sessionStore = useSessionStore()
    sessionStore.user = { avatar_url: 'https://api.example.test/uploads/thumbnail_x.png' }
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'https://api.example.test' } }))
  })

  function mountTab() {
    const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

    return mount(ProfilePhotoTab, {
      global: { plugins: [i18n], stubs: { BAlert: BAlertStub, BButton: BButtonStub } },
    })
  }

  function fileInput(wrapper) {
    return wrapper.find('[data-testid="profile-photo-input"]')
  }

  it('renders a labelled file input and a submit, disabled until a file is chosen', () => {
    const wrapper = mountTab()

    expect(fileInput(wrapper).exists()).toBe(true)
    expect(wrapper.find('label[for="profile-photo-input"]').text()).toContain('Profile picture')
    expect(wrapper.find('[data-testid="profile-photo-submit"]').attributes('disabled')).toBeDefined()
  })

  it('uploads the chosen file on submit and shows a success message', async () => {
    const store = useProfileStore()
    store.uploadPhotoFile = vi.fn().mockResolvedValue({ path: 'x.png' })

    const wrapper = mountTab()
    const file = new File(['x'], 'x.png', { type: 'image/png' })
    Object.defineProperty(fileInput(wrapper).element, 'files', { value: [file] })
    await fileInput(wrapper).trigger('change')

    await wrapper.find('[data-testid="profile-photo-form"]').trigger('submit')
    await flushPromises()

    expect(store.uploadPhotoFile).toHaveBeenCalledWith(file)
    expect(wrapper.find('[data-testid="profile-photo-feedback"]').text()).toBeTruthy()
  })

  it('shows an error message when the upload fails', async () => {
    const store = useProfileStore()
    store.uploadPhotoFile = vi.fn().mockRejectedValue({ data: { message: 'Nope' } })

    const wrapper = mountTab()
    const file = new File(['x'], 'x.png', { type: 'image/png' })
    Object.defineProperty(fileInput(wrapper).element, 'files', { value: [file] })
    await fileInput(wrapper).trigger('change')

    await wrapper.find('[data-testid="profile-photo-form"]').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[data-testid="profile-photo-feedback"]').text()).toContain('Nope')
  })
})
