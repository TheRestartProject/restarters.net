import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../../app/stores/auth.js'

describe('stores/auth', () => {
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

    // useNuxtApp is stubbed globally in tests/setup.ts; override its return
    // value for this suite.
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))

    // login/register/clear refresh the session store, which calls
    // $api.session.fetch — give it a sensible default response.
    mockApi.session.fetch.mockResolvedValue({
      data: {
        user: { id: 1, name: 'Jane', role_name: 'Restarter' },
        config: { frontend_url: 'http://localhost:3000' },
        flags: { onboarding: false },
      },
    })
  })

  it('starts logged out', () => {
    const store = useAuthStore()
    expect(store.loggedIn).toBe(false)
    expect(store.token).toBeNull()
  })

  it('login stores the token and user from the API response', async () => {
    mockApi.auth.login.mockResolvedValueOnce({
      data: { token: 'tok-1', user: { id: 1, name: 'Jane' } },
    })

    const store = useAuthStore()
    await store.login({ email: 'jane@bloggs.net', password: 'passw0rd' })

    expect(store.token).toBe('tok-1')
    // The session refresh replaces the login summary with the fuller
    // session user (canonical source).
    expect(store.user).toEqual({ id: 1, name: 'Jane', role_name: 'Restarter' })
    expect(store.loggedIn).toBe(true)

    // The navbar renders from the session store, so login must refresh it
    // (regression caught by e2e: dashboard greeted the user while the navbar
    // still showed Sign in).
    const { useSessionStore } = await import('../../app/stores/session.js')
    expect(mockApi.session.fetch).toHaveBeenCalled()
    expect(useSessionStore().user?.name).toBe('Jane')
  })

  it('register stores the token and user from the API response', async () => {
    mockApi.auth.register.mockResolvedValueOnce({
      data: { token: 'tok-2', user: { id: 2, name: 'Bob' } },
    })
    mockApi.session.fetch.mockResolvedValue({
      data: {
        user: { id: 2, name: 'Bob', role_name: 'Restarter' },
        config: {},
        flags: {},
      },
    })

    const store = useAuthStore()
    await store.register({
      name: 'Bob',
      email: 'bob@bloggs.net',
      password: 'passw0rd',
      password_confirmation: 'passw0rd',
      age: 30,
      country: 'UK',
      consent_gdpr: true,
      consent_future_data: true,
    })

    expect(mockApi.auth.register).toHaveBeenCalledTimes(1)
    expect(store.token).toBe('tok-2')
    expect(store.user).toEqual({ id: 2, name: 'Bob', role_name: 'Restarter' })
    expect(store.loggedIn).toBe(true)
  })

  it('logout calls the API and clears local state', async () => {
    const store = useAuthStore()
    store.token = 'tok-1'
    store.user = { id: 1, name: 'Jane' }

    mockApi.auth.logout.mockResolvedValueOnce({ message: 'Logged out' })

    await store.logout()

    expect(mockApi.auth.logout).toHaveBeenCalledTimes(1)
    expect(store.token).toBeNull()
    expect(store.user).toBeNull()

    // Session context downgrades to guest immediately on logout.
    const { useSessionStore } = await import('../../app/stores/session.js')
    expect(useSessionStore().user).toBeNull()
  })

  it('fetchSession replaces the user summary with the full session user', async () => {
    mockApi.session.fetch.mockResolvedValueOnce({
      data: {
        user: { id: 1, name: 'Jane', role_name: 'Administrator' },
        config: {},
        flags: {},
      },
    })

    const store = useAuthStore()
    store.token = 'tok-1'
    store.user = { id: 1, name: 'Jane' }

    await store.fetchSession()

    expect(store.user).toEqual({
      id: 1,
      name: 'Jane',
      role_name: 'Administrator',
    })
  })

  it('clear() removes local state without calling the API', () => {
    const store = useAuthStore()
    store.token = 'tok-1'
    store.user = { id: 1, name: 'Jane' }

    store.clear()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(mockApi.auth.logout).not.toHaveBeenCalled()
  })
})
