import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ProfileViewPage from '../../../app/pages/profile/[id].vue'

const PublicProfileViewStub = {
  props: ['userId'],
  template: '<div data-testid="stub-public-profile-view" :data-user-id="userId" />',
}

function mountPage(params) {
  vi.stubGlobal('useRoute', () => ({ params, query: {}, fullPath: '/profile/42' }))

  return mount(ProfileViewPage, {
    global: {
      stubs: { PublicProfileView: PublicProfileViewStub },
    },
  })
}

describe('pages/profile/[id]', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('parses the route id and passes it to PublicProfileView', () => {
    const wrapper = mountPage({ id: '42' })
    const view = wrapper.findComponent(PublicProfileViewStub)

    expect(view.exists()).toBe(true)
    expect(view.props('userId')).toBe(42)
  })

  it('passes null for a non-numeric id', () => {
    const wrapper = mountPage({ id: 'not-a-number' })
    const view = wrapper.findComponent(PublicProfileViewStub)

    expect(view.props('userId')).toBeNull()
  })
})
