import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import ProfileIndexPage from '../../../app/pages/profile/index.vue'
import { useAuthStore } from '../../../app/stores/auth.js'

const PublicProfileViewStub = {
  props: ['userId'],
  template: '<div data-testid="stub-public-profile-view" />',
}

function mountPage() {
  return mount(ProfileIndexPage, {
    global: {
      stubs: { PublicProfileView: PublicProfileViewStub },
    },
  })
}

describe('pages/profile/index', () => {
  let authStore

  beforeEach(() => {
    setActivePinia(createPinia())
    authStore = useAuthStore()
  })

  it('passes the session user id to PublicProfileView', () => {
    authStore.user = { id: 7, role_name: 'Restarter' }

    const wrapper = mountPage()
    const view = wrapper.findComponent(PublicProfileViewStub)

    expect(view.exists()).toBe(true)
    expect(view.props('userId')).toBe(7)
  })

  it('passes null when there is no session user', () => {
    authStore.user = null

    const wrapper = mountPage()
    const view = wrapper.findComponent(PublicProfileViewStub)

    expect(view.props('userId')).toBeNull()
  })
})
