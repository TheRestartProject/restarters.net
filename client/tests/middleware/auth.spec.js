import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import authMiddleware from '../../app/middleware/auth.global.ts'
import { useAuthStore } from '../../app/stores/auth.js'

describe('middleware/auth.global', () => {
  let navigateToMock

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  function route({ auth, role, fullPath = '/somewhere' } = {}) {
    return { meta: { auth, role }, fullPath }
  }

  it('does nothing for routes that do not require auth', () => {
    const result = authMiddleware(route({ auth: undefined }), route())
    expect(result).toBeUndefined()
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('redirects to /login with a redirect query when logged out', () => {
    authMiddleware(route({ auth: true, fullPath: '/dashboard' }), route())

    expect(navigateToMock).toHaveBeenCalledWith('/login?redirect=%2Fdashboard')
  })

  it('allows through when logged in and no role is required', () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, role_name: 'Restarter' }

    authMiddleware(route({ auth: true }), route())

    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('allows through when the role matches exactly', () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, role_name: 'Administrator' }

    authMiddleware(route({ auth: true, role: 'Administrator' }), route())

    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('redirects to /forbidden when the role does not match', () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, role_name: 'Restarter' }

    authMiddleware(route({ auth: true, role: 'Administrator' }), route())

    expect(navigateToMock).toHaveBeenCalledWith('/forbidden')
  })

  it('Root satisfies any required role', () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, role_name: 'Root' }

    authMiddleware(route({ auth: true, role: 'Administrator' }), route())

    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('redirects to /forbidden when logged in but there is no role_name at all', () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1 }

    authMiddleware(route({ auth: true, role: 'Administrator' }), route())

    expect(navigateToMock).toHaveBeenCalledWith('/forbidden')
  })
})
