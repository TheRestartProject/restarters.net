import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuth } from '../../app/composables/useAuth.js'
import { useAuthStore } from '../../app/stores/auth.js'

// Table-driven against app/User.php::hasRole()'s actual semantics (see the
// doc comment in useAuth.js): Root satisfies every check; every other role
// is an exact, case-sensitive string match with NO hierarchy beyond Root -
// e.g. Administrator does NOT also satisfy hasRole('Host').
const ROLE_NAMES = [
  'Administrator',
  'Host',
  'Restarter',
  'Guest',
  'NetworkCoordinator',
]

describe('useAuth: role semantics (mirrors app/User.php::hasRole)', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('Root satisfies every role check', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 1, role_name: 'Root' }
    const { hasRole } = useAuth()

    for (const role of [...ROLE_NAMES, 'Root', 'SomethingMadeUp']) {
      expect(hasRole(role)).toBe(true)
    }
  })

  it.each(ROLE_NAMES)(
    '%s satisfies only its own exact role check',
    (usersRole) => {
      const authStore = useAuthStore()
      authStore.user = { id: 1, role_name: usersRole }
      const { hasRole } = useAuth()

      for (const role of ROLE_NAMES) {
        expect(hasRole(role)).toBe(role === usersRole)
      }
      // Never satisfies Root.
      expect(hasRole('Root')).toBe(false)
    }
  )

  it('Administrator does NOT satisfy hasRole for other roles (no hierarchy)', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 1, role_name: 'Administrator' }
    const { hasRole } = useAuth()

    expect(hasRole('Administrator')).toBe(true)
    expect(hasRole('Host')).toBe(false)
    expect(hasRole('NetworkCoordinator')).toBe(false)
    expect(hasRole('Restarter')).toBe(false)
    expect(hasRole('Guest')).toBe(false)
    expect(hasRole('Root')).toBe(false)
  })

  it('role check is case-sensitive (exact match, no ucwords leniency)', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 1, role_name: 'administrator' }
    const { hasRole } = useAuth()

    expect(hasRole('Administrator')).toBe(false)
    expect(hasRole('administrator')).toBe(true)
  })

  it('returns false for every role when there is no user', () => {
    const { hasRole } = useAuth()

    for (const role of [...ROLE_NAMES, 'Root']) {
      expect(hasRole(role)).toBe(false)
    }
  })

  it('returns false for every role when the user has no role_name', () => {
    const authStore = useAuthStore()
    authStore.user = { id: 1, name: 'Jane' }
    const { hasRole } = useAuth()

    expect(hasRole('Administrator')).toBe(false)
  })
})

describe('useAuth: user/loggedIn/login/logout sugar', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      auth: {
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
      },
      session: {
        fetch: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  it('user/loggedIn reflect the auth store reactively', () => {
    const authStore = useAuthStore()
    const { user, loggedIn } = useAuth()

    expect(user.value).toBeNull()
    expect(loggedIn.value).toBe(false)

    authStore.token = 'tok-1'
    authStore.user = { id: 1, name: 'Jane' }

    expect(user.value).toEqual({ id: 1, name: 'Jane' })
    expect(loggedIn.value).toBe(true)
  })

  it('login() delegates to the auth store', async () => {
    mockApi.auth.login.mockResolvedValueOnce({
      data: { token: 'tok-1', user: { id: 1, name: 'Jane' } },
    })

    const { login, loggedIn } = useAuth()
    await login({ email: 'jane@bloggs.net', password: 'passw0rd' })

    expect(mockApi.auth.login).toHaveBeenCalledTimes(1)
    expect(loggedIn.value).toBe(true)
  })

  it('logout() delegates to the auth store', async () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, name: 'Jane' }
    mockApi.auth.logout.mockResolvedValueOnce({ message: 'Logged out' })

    const { logout, loggedIn } = useAuth()
    await logout()

    expect(mockApi.auth.logout).toHaveBeenCalledTimes(1)
    expect(loggedIn.value).toBe(false)
  })
})
