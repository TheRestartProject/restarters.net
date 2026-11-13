import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LogoutPage from '../../app/pages/logout.vue'
import { useAuthStore } from '../../app/stores/auth.js'

describe('pages/logout', () => {
  let navigateToMock

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  // The wiki's user menu links to /logout, which was a Laravel route on live.
  it('logs out, then goes to the login page', async () => {
    const store = useAuthStore()
    const order = []
    store.logout = vi.fn(async () => order.push('logout'))
    navigateToMock.mockImplementation(async (to) => order.push(`navigate ${to}`))

    mount(LogoutPage)
    await flushPromises()

    expect(order).toEqual(['logout', 'navigate /login'])
    expect(navigateToMock).toHaveBeenCalledWith('/login', { replace: true })
  })
})
