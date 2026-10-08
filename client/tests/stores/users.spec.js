import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useUsersStore } from '../../app/stores/users.js'

describe('stores/users', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      user: {
        list: vi.fn(),
        get: vi.fn(),
        create: vi.fn(),
      },
      role: {
        list: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  describe('fetchList', () => {
    it('sets loading while in flight and populates data/meta on success', async () => {
      let resolveFetch
      mockApi.user.list.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveFetch = resolve
        }),
      )

      const store = useUsersStore()
      const promise = store.fetchList({ page: 1 })

      expect(store.list.loading).toBe(true)

      resolveFetch({
        data: [{ id: 1, name: 'Jane' }],
        meta: { current_page: 1, last_page: 3, per_page: 30, total: 61, from: 1, to: 30 },
      })
      await promise

      expect(store.list.loading).toBe(false)
      expect(store.list.error).toBeNull()
      expect(store.list.data).toEqual([{ id: 1, name: 'Jane' }])
      expect(store.list.meta).toEqual({ current_page: 1, last_page: 3, per_page: 30, total: 61, from: 1, to: 30 })
    })

    it('passes params straight through to $api.user.list', async () => {
      mockApi.user.list.mockResolvedValueOnce({ data: [], meta: {} })

      const store = useUsersStore()
      await store.fetchList({ page: 2, name: 'jane', sort: 'email', sortdir: 'desc' })

      expect(mockApi.user.list).toHaveBeenCalledWith({ page: 2, name: 'jane', sort: 'email', sortdir: 'desc' })
    })

    it('sets error and rethrows on failure', async () => {
      const apiError = { status: 403 }
      mockApi.user.list.mockRejectedValueOnce(apiError)

      const store = useUsersStore()
      await expect(store.fetchList({})).rejects.toEqual(apiError)
      expect(store.list.error).toEqual(apiError)
      expect(store.list.loading).toBe(false)
    })
  })

  describe('fetchRoles', () => {
    it('populates roles.data on success', async () => {
      mockApi.role.list.mockResolvedValueOnce({ data: [{ id: 4, name: 'Restarter' }] })

      const store = useUsersStore()
      const data = await store.fetchRoles()

      expect(data).toEqual([{ id: 4, name: 'Restarter' }])
      expect(store.roles.data).toEqual([{ id: 4, name: 'Restarter' }])
      expect(store.roles.loading).toBe(false)
    })

    it('sets error and rethrows on failure', async () => {
      const apiError = { status: 403 }
      mockApi.role.list.mockRejectedValueOnce(apiError)

      const store = useUsersStore()
      await expect(store.fetchRoles()).rejects.toEqual(apiError)
      expect(store.roles.error).toEqual(apiError)
    })
  })

  describe('createUser', () => {
    it('posts the payload straight through and returns the created user', async () => {
      mockApi.user.create.mockResolvedValueOnce({ data: { id: 99, name: 'Jane Fixit' } })

      const store = useUsersStore()
      const payload = { name: 'Jane Fixit', email: 'jane@example.com', role: 4, password: 'secret123' }
      const data = await store.createUser(payload)

      expect(mockApi.user.create).toHaveBeenCalledWith(payload)
      expect(data).toEqual({ id: 99, name: 'Jane Fixit' })
    })

    it('does not catch - lets the caller render field errors (422 convention)', async () => {
      const apiError = { status: 422, data: { errors: { email: ['The email has already been taken.'] } } }
      mockApi.user.create.mockRejectedValueOnce(apiError)

      const store = useUsersStore()
      await expect(store.createUser({ name: 'X', email: 'x@x.com', role: 4, password: 'a' })).rejects.toEqual(apiError)
    })
  })

  describe('fetchPublicProfile', () => {
    it('sets loading, tracks targetId, and populates data on success', async () => {
      let resolveFetch
      mockApi.user.get.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveFetch = resolve
        }),
      )

      const store = useUsersStore()
      const promise = store.fetchPublicProfile(42)

      expect(store.publicProfile.loading).toBe(true)
      expect(store.publicProfile.targetId).toBe(42)

      const payload = { id: 42, name: 'Jane Fixit', groups: [], skills: [], biography: null }
      resolveFetch({ data: payload })
      await promise

      expect(store.publicProfile.loading).toBe(false)
      expect(store.publicProfile.error).toBeNull()
      expect(store.publicProfile.data).toEqual(payload)
    })

    it('sets error, clears data and rethrows on failure (e.g. 404)', async () => {
      const apiError = { status: 404 }
      mockApi.user.get.mockRejectedValueOnce(apiError)

      const store = useUsersStore()
      store.publicProfile.data = { id: 99, name: 'Stale' }

      await expect(store.fetchPublicProfile(99)).rejects.toEqual(apiError)
      expect(store.publicProfile.error).toEqual(apiError)
      expect(store.publicProfile.data).toBeNull()
    })

    it('re-fetches (does not cache) when called again for the same id', async () => {
      mockApi.user.get.mockResolvedValue({ data: { id: 5, name: 'A' } })

      const store = useUsersStore()
      await store.fetchPublicProfile(5)
      await store.fetchPublicProfile(5)

      expect(mockApi.user.get).toHaveBeenCalledTimes(2)
    })
  })
})
