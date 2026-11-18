import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../../app/stores/auth.js'
import { useNotificationsStore } from '../../app/stores/notifications.js'

describe('stores/notifications', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      user: {
        notifications: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  it('starts with null counts, not loading, no error', () => {
    const store = useNotificationsStore()
    expect(store.restarters).toBeNull()
    expect(store.discourse).toBeNull()
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetch() is a no-op when no user is logged in', async () => {
    const store = useNotificationsStore()
    await store.fetch()

    expect(mockApi.user.notifications).not.toHaveBeenCalled()
  })

  it('fetch() populates counts from the flat (non-{data:...}) v1 response', async () => {
    useAuthStore().user = { id: 42, username: 'jane' }
    mockApi.user.notifications.mockResolvedValueOnce({ success: 'success', restarters: 3, discourse: 7 })

    const store = useNotificationsStore()
    await store.fetch()

    expect(mockApi.user.notifications).toHaveBeenCalledWith(42)
    expect(store.restarters).toBe(3)
    expect(store.discourse).toBe(7)
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetch() swallows failures and does not rethrow (best-effort polling)', async () => {
    useAuthStore().user = { id: 42, username: 'jane' }
    mockApi.user.notifications.mockRejectedValueOnce({ status: 500 })

    const store = useNotificationsStore()
    await expect(store.fetch()).resolves.toBeUndefined()

    expect(store.error).toEqual({ status: 500 })
    expect(store.loading).toBe(false)
  })

  it('fetchPanel() keeps the first 10 notifications and leaves the page list alone', async () => {
    mockApi.user.myNotifications = vi.fn().mockResolvedValueOnce({
      data: Array.from({ length: 20 }, (_, i) => ({ id: `n${i}`, read: false })),
      meta: {},
    })

    const store = useNotificationsStore()
    store.list.page = 3
    await store.fetchPanel()

    expect(mockApi.user.myNotifications).toHaveBeenCalledWith({ page: 1 })
    expect(store.panel.items).toHaveLength(10)
    expect(store.list.page).toBe(3)
    expect(store.panel.loading).toBe(false)
  })

  it('fetchPanel() records a failure without rethrowing', async () => {
    mockApi.user.myNotifications = vi.fn().mockRejectedValueOnce({ status: 500 })

    const store = useNotificationsStore()
    await expect(store.fetchPanel()).resolves.toBeUndefined()

    expect(store.panel.error).toEqual({ status: 500 })
  })

  it('markRead() marks panel cards read too', async () => {
    mockApi.user.markNotificationsRead = vi.fn().mockResolvedValueOnce({ data: { unread: 1 } })

    const store = useNotificationsStore()
    store.panel.items = [{ id: 'a', read: false }, { id: 'b', read: false }]
    await store.markRead('a')

    expect(store.panel.items.map((n) => n.read)).toEqual([true, false])
    expect(store.restarters).toBe(1)
  })
})
