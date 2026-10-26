import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useNetworksStore } from '../../app/stores/networks.js'

describe('stores/networks', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      network: {
        list: vi.fn(),
        get: vi.fn(),
        groups: vi.fn(),
        events: vi.fn(),
        tags: vi.fn(),
        createTag: vi.fn(),
        updateTag: vi.fn(),
        deleteTag: vi.fn(),
        stats: vi.fn(),
        associateGroups: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  describe('fetchList', () => {
    it('populates list.data on success', async () => {
      mockApi.network.list.mockResolvedValue({ data: [{ id: 1, name: 'Test London' }] })

      const store = useNetworksStore()
      await store.fetchList()

      expect(store.list.data).toEqual([{ id: 1, name: 'Test London' }])
      expect(store.list.loading).toBe(false)
      expect(store.list.error).toBeNull()
    })

    it('sets error and rethrows on failure', async () => {
      const error = { status: 500 }
      mockApi.network.list.mockRejectedValue(error)

      const store = useNetworksStore()
      await expect(store.fetchList()).rejects.toStrictEqual(error)
      expect(store.list.error).toStrictEqual(error)
      expect(store.list.loading).toBe(false)
    })
  })

  describe('fetchCurrent', () => {
    it('populates current.data on success', async () => {
      mockApi.network.get.mockResolvedValue({ data: { id: 1, name: 'Test London', stats: { parties: 3 } } })

      const store = useNetworksStore()
      await store.fetchCurrent(1)

      expect(store.current.data).toEqual({ id: 1, name: 'Test London', stats: { parties: 3 } })
    })

    it('records a 404 error so the page can render a not-found state', async () => {
      const error = { status: 404 }
      mockApi.network.get.mockRejectedValue(error)

      const store = useNetworksStore()
      await expect(store.fetchCurrent(999)).rejects.toStrictEqual(error)
      expect(store.current.error).toStrictEqual(error)
      expect(store.current.data).toBeNull()
    })
  })

  describe('fetchGroups', () => {
    it('requests includeCounts and includeNextEvent by default', async () => {
      mockApi.network.groups.mockResolvedValue({ data: [{ id: 5, name: 'Tag Test Group' }] })

      const store = useNetworksStore()
      await store.fetchGroups(1)

      expect(mockApi.network.groups).toHaveBeenCalledWith(1, { includeCounts: true, includeNextEvent: true })
      expect(store.groups.data).toEqual([{ id: 5, name: 'Tag Test Group' }])
    })

    it('merges an extra group_tag filter param on top of the defaults', async () => {
      mockApi.network.groups.mockResolvedValue({ data: [] })

      const store = useNetworksStore()
      await store.fetchGroups(1, { group_tag: 7 })

      expect(mockApi.network.groups).toHaveBeenCalledWith(1, { includeCounts: true, includeNextEvent: true, group_tag: 7 })
    })
  })

  describe('tags', () => {
    it('fetchTags sorts tags by name', async () => {
      mockApi.network.tags.mockResolvedValue({
        data: [
          { id: 2, name: 'Zulu', groups_count: 0 },
          { id: 1, name: 'Alpha', groups_count: 2 },
        ],
      })

      const store = useNetworksStore()
      await store.fetchTags(1)

      expect(store.tags.data.map((t) => t.name)).toEqual(['Alpha', 'Zulu'])
    })

    it('createTag appends and re-sorts', async () => {
      const store = useNetworksStore()
      store.tags.data = [{ id: 1, name: 'Alpha', groups_count: 0 }]
      mockApi.network.createTag.mockResolvedValue({ data: { id: 2, name: 'Beta', groups_count: 0 } })

      const created = await store.createTag(1, { name: 'Beta' })

      expect(mockApi.network.createTag).toHaveBeenCalledWith(1, { name: 'Beta' })
      expect(created).toEqual({ id: 2, name: 'Beta', groups_count: 0 })
      expect(store.tags.data.map((t) => t.name)).toEqual(['Alpha', 'Beta'])
    })

    it('createTag surfaces a duplicate-name 422 to the caller unchanged', async () => {
      const store = useNetworksStore()
      const error = { status: 422, data: { message: 'A tag with this name already exists in this network' } }
      mockApi.network.createTag.mockRejectedValue(error)

      await expect(store.createTag(1, { name: 'Alpha' })).rejects.toStrictEqual(error)
    })

    it('updateTag replaces the matching row by id and re-sorts', async () => {
      const store = useNetworksStore()
      store.tags.data = [
        { id: 1, name: 'Alpha', groups_count: 0 },
        { id: 2, name: 'Beta', groups_count: 0 },
      ]
      mockApi.network.updateTag.mockResolvedValue({ data: { id: 1, name: 'Zeta', groups_count: 0 } })

      await store.updateTag(1, 1, { name: 'Zeta' })

      expect(mockApi.network.updateTag).toHaveBeenCalledWith(1, 1, { name: 'Zeta' })
      expect(store.tags.data.map((t) => t.name)).toEqual(['Beta', 'Zeta'])
    })

    it('deleteTag removes the row', async () => {
      const store = useNetworksStore()
      store.tags.data = [{ id: 1, name: 'Alpha', groups_count: 0 }]
      mockApi.network.deleteTag.mockResolvedValue({})

      await store.deleteTag(1, 1)

      expect(mockApi.network.deleteTag).toHaveBeenCalledWith(1, 1)
      expect(store.tags.data).toEqual([])
    })
  })

  describe('associateGroups', () => {
    it('posts the group ids and re-fetches the network groups list', async () => {
      mockApi.network.associateGroups.mockResolvedValue({ message: 'ok' })
      mockApi.network.groups.mockResolvedValue({ data: [{ id: 9, name: 'New Group' }] })

      const store = useNetworksStore()
      await store.associateGroups(1, [9, 10])

      expect(mockApi.network.associateGroups).toHaveBeenCalledWith(1, [9, 10])
      expect(mockApi.network.groups).toHaveBeenCalled()
      expect(store.groups.data).toEqual([{ id: 9, name: 'New Group' }])
    })

    it('propagates a failure without touching groups.data', async () => {
      const error = { status: 404, data: { message: 'Not Found' } }
      mockApi.network.associateGroups.mockRejectedValue(error)

      const store = useNetworksStore()
      store.groups.data = [{ id: 1, name: 'Existing' }]

      await expect(store.associateGroups(1, [9])).rejects.toStrictEqual(error)
      expect(mockApi.network.groups).not.toHaveBeenCalled()
      expect(store.groups.data).toEqual([{ id: 1, name: 'Existing' }])
    })
  })
})
