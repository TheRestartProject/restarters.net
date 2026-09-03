import { mount } from '@vue/test-utils'
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
const TusImageUploadStub = {
  props: ['currentImageUrl'],
  emits: ['uploaded', 'upload-error'],
  template:
    '<div><button data-testid="stub-upload-ok" @click="$emit(\'uploaded\', { uploadKey: \'key123\' })" /><button data-testid="stub-upload-fail" @click="$emit(\'upload-error\', \'boom\')" /></div>',
}
const BAlertStub = { template: '<div><slot /></div>' }

function mountComponent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(ProfilePhotoTab, {
    global: {
      plugins: [i18n],
      stubs: { TusImageUpload: TusImageUploadStub, BAlert: BAlertStub },
    },
  })
}

describe('components/profile/ProfilePhotoTab', () => {
  let profileStore
  let sessionStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'http://api.test' } }))

    profileStore = useProfileStore()
    sessionStore = useSessionStore()
    sessionStore.user = { id: 1, avatar_url: null }
  })

  it('passes the session avatar_url through as the current image', () => {
    sessionStore.user.avatar_url = 'http://api.test/uploads/thumbnail_old.jpg'

    const wrapper = mountComponent()

    expect(wrapper.findComponent(TusImageUploadStub).props('currentImageUrl')).toBe('http://api.test/uploads/thumbnail_old.jpg')
  })

  it('calls uploadPhoto with the emitted upload key and shows a success message', async () => {
    profileStore.uploadPhoto = vi.fn().mockResolvedValue({ path: 'new.jpg' })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.uploadPhoto).toHaveBeenCalledWith('key123')
    expect(wrapper.find('[data-testid="profile-photo-feedback"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Profile picture updated!')
  })

  it('shows an error message when the upload attach call fails', async () => {
    profileStore.uploadPhoto = vi.fn().mockRejectedValue({ data: { message: 'Too big' } })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.text()).toContain('Too big')
  })

  it('shows an error message when TusImageUpload itself reports an upload error', async () => {
    const wrapper = mountComponent()
    await wrapper.find('[data-testid="stub-upload-fail"]').trigger('click')

    expect(wrapper.text()).toContain('boom')
  })
})
