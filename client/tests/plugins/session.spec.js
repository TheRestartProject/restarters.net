import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'

// The cold-boot session bootstrap (plugins/session.ts): a persisted token
// must repopulate user/config on full page load, or reloads render the app
// logged-out (the bug the grouptags e2e port exposed).
describe('plugins/session (cold-boot bootstrap)', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())
    mockApi = {
      session: { fetch: vi.fn() },
    }
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
    vi.stubGlobal('defineNuxtPlugin', (fn) => fn)
  })

  async function runPlugin() {
    const mod = await import('../../app/plugins/session.ts')
    await mod.default()
  }

  it('fetches the session when a persisted token exists', async () => {
    useAuthStore().token = 'tok-persisted'
    mockApi.session.fetch.mockResolvedValueOnce({
      data: { user: { id: 1, name: 'Jane', role_name: 'Administrator' }, config: {}, flags: {} },
    })

    await runPlugin()

    expect(mockApi.session.fetch).toHaveBeenCalledTimes(1)
    expect(useSessionStore().user?.name).toBe('Jane')
    expect(useAuthStore().user?.role_name).toBe('Administrator')
  })

  it('does nothing for guests', async () => {
    await runPlugin()
    expect(mockApi.session.fetch).not.toHaveBeenCalled()
  })

  it('does not refetch when the session is already loaded', async () => {
    useAuthStore().token = 'tok'
    useSessionStore().loaded = true

    await runPlugin()

    expect(mockApi.session.fetch).not.toHaveBeenCalled()
  })

  it('survives a rejected fetch (expired token) and stays guest', async () => {
    useAuthStore().token = 'tok-expired'
    mockApi.session.fetch.mockRejectedValueOnce(Object.assign(new Error('401'), { status: 401 }))

    await expect(runPlugin()).resolves.toBeUndefined()
  })
})
