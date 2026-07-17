import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useGroupsStore } from '../../app/stores/groups.js'
import { useToastStore } from '../../app/stores/toast.js'

describe('stores/groups', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      group: {
        names: vi.fn(),
        get: vi.fn(),
        create: vi.fn(),
        update: vi.fn(),
        join: vi.fn(),
        leave: vi.fn(),
        nearby: vi.fn(),
        stats: vi.fn(),
        events: vi.fn(),
        volunteers: vi.fn(),
        invite: vi.fn(),
        del: vi.fn(),
        removeVolunteer: vi.fn(),
        setVolunteerHost: vi.fn(),
        tags: vi.fn(),
        uploadImage: vi.fn(),
        summary: vi.fn(),
      },
      dashboard: {
        fetch: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  describe('fetchNames', () => {
    it('sets loading while in flight and populates names on success', async () => {
      let resolveFetch
      mockApi.group.names.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveFetch = resolve
        })
      )

      const store = useGroupsStore()
      const promise = store.fetchNames()

      expect(store.namesLoading).toBe(true)

      resolveFetch({ data: [{ id: 1, name: 'A', archived_at: null }] })
      await promise

      expect(store.namesLoading).toBe(false)
      expect(store.namesError).toBeNull()
      expect(store.names).toEqual([{ id: 1, name: 'A', archived_at: null }])
    })

    it('sets namesError and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.names.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      await expect(store.fetchNames()).rejects.toEqual(apiError)

      expect(store.namesLoading).toBe(false)
      expect(store.namesError).toEqual(apiError)
    })
  })

  describe('fetchDetails', () => {
    it('fetches and caches a group, skipping a second call for the same id', async () => {
      mockApi.group.get.mockResolvedValueOnce({ data: { id: 5, name: 'Group 5', hosts: 2 } })

      const store = useGroupsStore()
      const first = await store.fetchDetails(5)
      const second = await store.fetchDetails(5)

      expect(first).toEqual({ id: 5, name: 'Group 5', hosts: 2 })
      expect(second).toEqual({ id: 5, name: 'Group 5', hosts: 2 })
      expect(mockApi.group.get).toHaveBeenCalledTimes(1)
      expect(store.details[5]).toEqual({ id: 5, name: 'Group 5', hosts: 2 })
    })

    it('re-fetches when force is set', async () => {
      mockApi.group.get.mockResolvedValueOnce({ data: { id: 5, name: 'Group 5' } })
      mockApi.group.get.mockResolvedValueOnce({ data: { id: 5, name: 'Group 5 updated' } })

      const store = useGroupsStore()
      await store.fetchDetails(5)
      const refetched = await store.fetchDetails(5, { force: true })

      expect(mockApi.group.get).toHaveBeenCalledTimes(2)
      expect(refetched).toEqual({ id: 5, name: 'Group 5 updated' })
    })

    it('returns null and does not throw on failure', async () => {
      mockApi.group.get.mockRejectedValueOnce({ status: 500 })

      const store = useGroupsStore()
      const result = await store.fetchDetails(5)

      expect(result).toBeNull()
      expect(store.details[5]).toBeUndefined()
      expect(store.detailsLoading[5]).toBe(false)
    })
  })

  describe('fetchSummaries', () => {
    it('batches every requested id into one call and populates summaryByIds', async () => {
      mockApi.group.summary.mockResolvedValueOnce({
        data: [
          { id: 1, name: 'Alpha' },
          { id: 2, name: 'Beta' },
        ],
      })

      const store = useGroupsStore()
      const result = await store.fetchSummaries([1, 2])

      expect(mockApi.group.summary).toHaveBeenCalledTimes(1)
      expect(mockApi.group.summary).toHaveBeenCalledWith(
        expect.objectContaining({ ids: '1,2', includeCounts: 'true', includeNextEvent: 'true' })
      )
      expect(result).toEqual([{ id: 1, name: 'Alpha' }, { id: 2, name: 'Beta' }])
      expect(store.summaryByIds[1]).toEqual({ id: 1, name: 'Alpha' })
      expect(store.summaryByIds[2]).toEqual({ id: 2, name: 'Beta' })
    })

    it('does not refetch an id already hydrated, unless force is set', async () => {
      mockApi.group.summary.mockResolvedValueOnce({ data: [{ id: 1, name: 'Alpha' }] })

      const store = useGroupsStore()
      await store.fetchSummaries([1])
      await store.fetchSummaries([1])

      expect(mockApi.group.summary).toHaveBeenCalledTimes(1)

      mockApi.group.summary.mockResolvedValueOnce({ data: [{ id: 1, name: 'Alpha updated' }] })
      await store.fetchSummaries([1], { force: true })

      expect(mockApi.group.summary).toHaveBeenCalledTimes(2)
      expect(store.summaryByIds[1]).toEqual({ id: 1, name: 'Alpha updated' })
    })

    it('only fetches the ids not already hydrated, in one call', async () => {
      mockApi.group.summary.mockResolvedValueOnce({ data: [{ id: 1, name: 'Alpha' }] })
      const store = useGroupsStore()
      await store.fetchSummaries([1])

      mockApi.group.summary.mockResolvedValueOnce({ data: [{ id: 2, name: 'Beta' }] })
      await store.fetchSummaries([1, 2])

      expect(mockApi.group.summary).toHaveBeenCalledTimes(2)
      expect(mockApi.group.summary).toHaveBeenLastCalledWith(expect.objectContaining({ ids: '2' }))
    })

    it('does not issue a second request for an id whose fetch is still in flight', async () => {
      let resolveFetch
      mockApi.group.summary.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveFetch = resolve
        })
      )

      const store = useGroupsStore()
      const first = store.fetchSummaries([1])
      const second = store.fetchSummaries([1])

      resolveFetch({ data: [{ id: 1, name: 'Alpha' }] })
      await Promise.all([first, second])

      expect(mockApi.group.summary).toHaveBeenCalledTimes(1)
    })

    it('returns [] and makes no call when every id is already hydrated', async () => {
      mockApi.group.summary.mockResolvedValueOnce({ data: [{ id: 1, name: 'Alpha' }] })
      const store = useGroupsStore()
      await store.fetchSummaries([1])

      const result = await store.fetchSummaries([1])

      expect(result).toEqual([])
      expect(mockApi.group.summary).toHaveBeenCalledTimes(1)
    })

    it('chunks requests at 200 ids', async () => {
      mockApi.group.summary.mockResolvedValue({ data: [] })

      const store = useGroupsStore()
      const ids = Array.from({ length: 250 }, (_, i) => i + 1)
      await store.fetchSummaries(ids)

      expect(mockApi.group.summary).toHaveBeenCalledTimes(2)
      expect(mockApi.group.summary.mock.calls[0][0].ids.split(',')).toHaveLength(200)
      expect(mockApi.group.summary.mock.calls[1][0].ids.split(',')).toHaveLength(50)
    })
  })

  describe('fetchMine', () => {
    it('populates mine.data from the dashboard your_groups and seeds memberIds', async () => {
      mockApi.dashboard.fetch.mockResolvedValueOnce({
        data: {
          your_groups: [{ id: 1, name: 'A', role: 3, archived: false, image_url: null }],
          nearby_groups: [],
          new_nearby_groups: [],
          upcoming_events: [],
        },
      })

      const store = useGroupsStore()
      await store.fetchMine()

      expect(store.mine.loading).toBe(false)
      expect(store.mine.error).toBeNull()
      expect(store.mine.data).toEqual([{ id: 1, name: 'A', role: 3, archived: false, image_url: null }])
      expect(store.memberIds).toEqual([1])
    })

    it('sets mine.error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.dashboard.fetch.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      await expect(store.fetchMine()).rejects.toEqual(apiError)

      expect(store.mine.loading).toBe(false)
      expect(store.mine.error).toEqual(apiError)
    })
  })

  describe('fetchNearby', () => {
    it('populates nearby.data on success', async () => {
      mockApi.group.nearby.mockResolvedValueOnce({
        data: [{ id: 2, name: 'B', distance: 4.2, location: 'Town', image_url: null }],
      })

      const store = useGroupsStore()
      await store.fetchNearby()

      expect(store.nearby.loading).toBe(false)
      expect(store.nearby.error).toBeNull()
      expect(store.nearby.data).toEqual([{ id: 2, name: 'B', distance: 4.2, location: 'Town', image_url: null }])
    })

    it('sets nearby.error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.nearby.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      await expect(store.fetchNearby()).rejects.toEqual(apiError)

      expect(store.nearby.error).toEqual(apiError)
    })
  })

  describe('join', () => {
    it('optimistically adds to memberIds and keeps it on success', async () => {
      mockApi.group.join.mockResolvedValueOnce({ data: { joined: true, already_member: false } })

      const store = useGroupsStore()
      const promise = store.join(7)

      expect(store.memberIds).toEqual([7])

      await promise

      expect(store.memberIds).toEqual([7])
    })

    it('reverts memberIds and pushes a toast on failure', async () => {
      const apiError = { status: 500, message: 'Nope' }
      mockApi.group.join.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const toastStore = useToastStore()

      await expect(store.join(7)).rejects.toEqual(apiError)

      expect(store.memberIds).toEqual([])
      expect(toastStore.toasts).toHaveLength(1)
      expect(toastStore.toasts[0].variant).toBe('danger')
    })
  })

  describe('leave', () => {
    it('optimistically removes from memberIds and mine.data on success', async () => {
      mockApi.group.leave.mockResolvedValueOnce({ data: { left: true } })

      const store = useGroupsStore()
      store.memberIds = [3]
      store.mine.data = [{ id: 3, name: 'C', role: 4, archived: false, image_url: null }]

      const promise = store.leave(3)

      expect(store.memberIds).toEqual([])
      expect(store.mine.data).toEqual([])

      await promise

      expect(store.memberIds).toEqual([])
      expect(store.mine.data).toEqual([])
    })

    it('reverts memberIds and mine.data at the same position, and toasts, on failure', async () => {
      const apiError = { status: 500, message: 'Nope' }
      mockApi.group.leave.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const entryA = { id: 1, name: 'A', role: 3, archived: false, image_url: null }
      const entryB = { id: 2, name: 'B', role: 3, archived: false, image_url: null }
      store.memberIds = [1, 2]
      store.mine.data = [entryA, entryB]
      const toastStore = useToastStore()

      await expect(store.leave(1)).rejects.toEqual(apiError)

      expect(store.memberIds.sort()).toEqual([1, 2])
      expect(store.mine.data).toEqual([entryA, entryB])
      expect(toastStore.toasts).toHaveLength(1)
    })
  })

  describe('isMember getter', () => {
    it('reflects memberIds', () => {
      const store = useGroupsStore()
      store.memberIds = [1, 2]

      expect(store.isMember(1)).toBe(true)
      expect(store.isMember(3)).toBe(false)
    })
  })

  describe('fetchCurrent', () => {
    it('populates current.data and details[id], and seeds mine when memberIds is empty', async () => {
      mockApi.group.get.mockResolvedValueOnce({ data: { id: 5, name: 'Group 5', permissions: {} } })
      mockApi.dashboard.fetch.mockResolvedValueOnce({
        data: { your_groups: [], nearby_groups: [], new_nearby_groups: [], upcoming_events: [] },
      })

      const store = useGroupsStore()
      await store.fetchCurrent(5)

      expect(store.current.data).toEqual({ id: 5, name: 'Group 5', permissions: {} })
      expect(store.details[5]).toEqual({ id: 5, name: 'Group 5', permissions: {} })
      expect(mockApi.dashboard.fetch).toHaveBeenCalledTimes(1)
    })

    it('does not seed mine when memberIds is already known', async () => {
      mockApi.group.get.mockResolvedValueOnce({ data: { id: 5, name: 'Group 5', permissions: {} } })

      const store = useGroupsStore()
      store.memberIds = [5]
      await store.fetchCurrent(5)

      expect(mockApi.dashboard.fetch).not.toHaveBeenCalled()
    })

    it('sets current.error and rethrows on failure', async () => {
      const apiError = { status: 404 }
      mockApi.group.get.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      await expect(store.fetchCurrent(5)).rejects.toEqual(apiError)

      expect(store.current.loading).toBe(false)
      expect(store.current.error).toEqual(apiError)
    })
  })

  describe('fetchStats', () => {
    it('populates stats.data on success', async () => {
      mockApi.group.stats.mockResolvedValueOnce({ data: { group_stats: { parties: 3 } } })

      const store = useGroupsStore()
      await store.fetchStats(5)

      expect(store.stats.data).toEqual({ group_stats: { parties: 3 } })
      expect(store.stats.error).toBeNull()
    })

    it('sets stats.error but does not throw (endpoint may not exist yet)', async () => {
      const apiError = { status: 404 }
      mockApi.group.stats.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const result = await store.fetchStats(5)

      expect(result).toBeNull()
      expect(store.stats.error).toEqual(apiError)
      expect(store.stats.loading).toBe(false)
    })
  })

  describe('fetchEvents', () => {
    it('populates events.data on success', async () => {
      mockApi.group.events.mockResolvedValueOnce({ data: [{ id: 1, title: 'Repair Cafe' }] })

      const store = useGroupsStore()
      await store.fetchEvents(5)

      expect(store.events.data).toEqual([{ id: 1, title: 'Repair Cafe' }])
    })

    it('sets events.error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.events.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      await expect(store.fetchEvents(5)).rejects.toEqual(apiError)
      expect(store.events.error).toEqual(apiError)
    })
  })

  describe('fetchVolunteers', () => {
    it('populates volunteers.data on success', async () => {
      mockApi.group.volunteers.mockResolvedValueOnce({ data: [{ id: 1, user: 2, name: 'Sam', host: true }] })

      const store = useGroupsStore()
      await store.fetchVolunteers(5)

      expect(store.volunteers.data).toEqual([{ id: 1, user: 2, name: 'Sam', host: true }])
    })

    it('sets volunteers.error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.volunteers.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      await expect(store.fetchVolunteers(5)).rejects.toEqual(apiError)
      expect(store.volunteers.error).toEqual(apiError)
    })
  })

  describe('inviteVolunteers', () => {
    it('returns the response data on success', async () => {
      mockApi.group.invite.mockResolvedValueOnce({ data: { invites_sent: 2, invalid: [] } })

      const store = useGroupsStore()
      const result = await store.inviteVolunteers(5, { emails: ['a@example.com'] })

      expect(result).toEqual({ invites_sent: 2, invalid: [] })
      expect(mockApi.group.invite).toHaveBeenCalledWith(5, { emails: ['a@example.com'] })
    })

    it('rethrows on failure without toasting (caller renders inline)', async () => {
      const apiError = { status: 422, message: 'Invalid' }
      mockApi.group.invite.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const toastStore = useToastStore()

      await expect(store.inviteVolunteers(5, { emails: [] })).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(0)
    })
  })

  describe('deleteGroup', () => {
    it('marks current.data as archived on success', async () => {
      mockApi.group.del.mockResolvedValueOnce({ data: { archived: true } })

      const store = useGroupsStore()
      store.current.data = { id: 5, name: 'Group 5', archived_at: null }
      await store.deleteGroup(5)

      expect(store.current.data.archived_at).not.toBeNull()
    })

    it('toasts and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.del.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const toastStore = useToastStore()
      store.current.data = { id: 5, name: 'Group 5', archived_at: null }

      await expect(store.deleteGroup(5)).rejects.toEqual(apiError)
      expect(store.current.data.archived_at).toBeNull()
      expect(toastStore.toasts).toHaveLength(1)
    })
  })

  describe('removeVolunteer', () => {
    it('optimistically removes the row and keeps it removed on success', async () => {
      mockApi.group.removeVolunteer.mockResolvedValueOnce({})

      const store = useGroupsStore()
      store.volunteers.data = [{ id: 1, user: 2, name: 'Sam' }, { id: 2, user: 3, name: 'Jo' }]

      const promise = store.removeVolunteer(5, 2)
      expect(store.volunteers.data).toEqual([{ id: 2, user: 3, name: 'Jo' }])

      await promise
      expect(store.volunteers.data).toEqual([{ id: 2, user: 3, name: 'Jo' }])
    })

    it('reverts and toasts on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.removeVolunteer.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const toastStore = useToastStore()
      const rows = [{ id: 1, user: 2, name: 'Sam' }]
      store.volunteers.data = rows

      await expect(store.removeVolunteer(5, 2)).rejects.toEqual(apiError)
      expect(store.volunteers.data).toEqual(rows)
      expect(toastStore.toasts).toHaveLength(1)
    })
  })

  describe('setVolunteerHost', () => {
    it('optimistically flips host and keeps it on success', async () => {
      mockApi.group.setVolunteerHost.mockResolvedValueOnce({})

      const store = useGroupsStore()
      store.volunteers.data = [{ id: 1, user: 2, name: 'Sam', host: false }]

      const promise = store.setVolunteerHost(5, 2, true)
      expect(store.volunteers.data[0].host).toBe(true)

      await promise
      expect(store.volunteers.data[0].host).toBe(true)
    })

    it('reverts and toasts on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.setVolunteerHost.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const toastStore = useToastStore()
      store.volunteers.data = [{ id: 1, user: 2, name: 'Sam', host: false }]

      await expect(store.setVolunteerHost(5, 2, true)).rejects.toEqual(apiError)
      expect(store.volunteers.data[0].host).toBe(false)
      expect(toastStore.toasts).toHaveLength(1)
    })
  })

  describe('createGroup', () => {
    it('returns the bare id from POST /api/v2/groups (no {data:...} envelope)', async () => {
      mockApi.group.create.mockResolvedValueOnce({ id: 42 })

      const store = useGroupsStore()
      const id = await store.createGroup({ name: 'New Group' })

      expect(mockApi.group.create).toHaveBeenCalledWith({ name: 'New Group' })
      expect(id).toBe(42)
    })

    it('rethrows (e.g. a 422) for the form to render inline, without toasting', async () => {
      const apiError = { status: 422, data: { errors: { name: ['required'] } } }
      mockApi.group.create.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const toastStore = useToastStore()

      await expect(store.createGroup({})).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(0)
    })
  })

  describe('updateGroup', () => {
    it('returns the bare id and invalidates the cached details/current entry', async () => {
      mockApi.group.update.mockResolvedValueOnce({ id: 5 })

      const store = useGroupsStore()
      store.details = { 5: { id: 5, name: 'Stale' } }
      store.current.data = { id: 5, name: 'Stale' }

      const id = await store.updateGroup(5, { name: 'Fresh' })

      expect(mockApi.group.update).toHaveBeenCalledWith(5, { name: 'Fresh' })
      expect(id).toBe(5)
      expect(store.details[5]).toBeUndefined()
      expect(store.current.data).toBeNull()
    })

    it('rethrows on failure without toasting', async () => {
      const apiError = { status: 422, data: { errors: { location: ['geocode failed'] } } }
      mockApi.group.update.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const toastStore = useToastStore()

      await expect(store.updateGroup(5, {})).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(0)
    })
  })

  describe('fetchTags', () => {
    it('sets loading while in flight and populates tags on success', async () => {
      let resolveFetch
      mockApi.group.tags.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveFetch = resolve
        })
      )

      const store = useGroupsStore()
      const promise = store.fetchTags()

      expect(store.tags.loading).toBe(true)

      resolveFetch({ data: [{ id: 1, name: 'Repair Café' }] })
      await promise

      expect(store.tags.loading).toBe(false)
      expect(store.tags.data).toEqual([{ id: 1, name: 'Repair Café' }])
    })

    it('sets tags.error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.tags.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      await expect(store.fetchTags()).rejects.toEqual(apiError)
      expect(store.tags.error).toEqual(apiError)
    })
  })

  describe('uploadGroupImage', () => {
    it('updates current.data.image when it matches the uploaded group, on success', async () => {
      mockApi.group.uploadImage.mockResolvedValueOnce({ data: { image_url: '/new.png' } })

      const store = useGroupsStore()
      store.current.data = { id: 5, image: null }

      const data = await store.uploadGroupImage(5, 'key123')

      expect(mockApi.group.uploadImage).toHaveBeenCalledWith(5, 'key123')
      expect(store.current.data.image).toBe('/new.png')
      expect(data).toEqual({ image_url: '/new.png' })
    })

    it('toasts and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.group.uploadImage.mockRejectedValueOnce(apiError)

      const store = useGroupsStore()
      const toastStore = useToastStore()

      await expect(store.uploadGroupImage(5, 'key123')).rejects.toEqual(apiError)
      expect(toastStore.toasts).toHaveLength(1)
    })
  })
})
