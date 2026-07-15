import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'

describe('stores/session', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      session: {
        fetch: vi.fn(),
      },
      user: {
        dismissOnboarding: vi.fn(),
      },
    }

    // useNuxtApp is stubbed globally in tests/setup.ts; override its return
    // value for this suite.
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  it('starts unloaded', () => {
    const store = useSessionStore()
    expect(store.loaded).toBe(false)
    expect(store.user).toBeNull()
    expect(store.config).toBeNull()
    expect(store.flags).toBeNull()
  })

  it('fetch() populates user/config/flags and marks loaded', async () => {
    const sessionData = {
      user: { id: 1, name: 'Jane', role_name: 'Administrator' },
      config: { discourse_url: 'https://talk.example.com' },
      flags: { onboarding: false },
    }
    mockApi.session.fetch.mockResolvedValueOnce({ data: sessionData })

    const store = useSessionStore()
    const result = await store.fetch()

    expect(store.user).toEqual(sessionData.user)
    expect(store.config).toEqual(sessionData.config)
    expect(store.flags).toEqual(sessionData.flags)
    expect(store.loaded).toBe(true)
    expect(result).toEqual(sessionData)
  })

  it('fetch() keeps the auth store user summary in step', async () => {
    mockApi.session.fetch.mockResolvedValueOnce({
      data: {
        user: { id: 1, name: 'Jane', role_name: 'Host' },
        config: {},
        flags: {},
      },
    })

    const sessionStore = useSessionStore()
    const authStore = useAuthStore()
    authStore.user = { id: 1, name: 'Jane' }

    await sessionStore.fetch()

    expect(authStore.user).toEqual({
      id: 1,
      name: 'Jane',
      role_name: 'Host',
    })
  })

  it('fetch() with a logged-out session sets user to null', async () => {
    mockApi.session.fetch.mockResolvedValueOnce({
      data: { user: null, config: {}, flags: {} },
    })

    const store = useSessionStore()
    await store.fetch()

    expect(store.user).toBeNull()
    expect(store.loaded).toBe(true)
  })

  describe('dismissOnboarding', () => {
    it('flips flags.onboarding to false immediately and calls the API', async () => {
      mockApi.user.dismissOnboarding.mockResolvedValueOnce({ data: { onboarding: false } })

      const store = useSessionStore()
      store.flags = { onboarding: true }

      await store.dismissOnboarding()

      expect(store.flags.onboarding).toBe(false)
      expect(mockApi.user.dismissOnboarding).toHaveBeenCalledTimes(1)
    })

    it('still clears the flag locally when the API call fails (endpoint is a recorded gap)', async () => {
      mockApi.user.dismissOnboarding.mockRejectedValueOnce({ status: 404 })

      const store = useSessionStore()
      store.flags = { onboarding: true }

      await expect(store.dismissOnboarding()).resolves.toBeUndefined()

      expect(store.flags.onboarding).toBe(false)
    })

    it('is a no-op on flags when flags has not been loaded yet', async () => {
      mockApi.user.dismissOnboarding.mockResolvedValueOnce({ data: {} })

      const store = useSessionStore()
      expect(store.flags).toBeNull()

      await store.dismissOnboarding()

      expect(store.flags).toBeNull()
    })
  })
})
