import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useModerationStore } from '../../app/stores/moderation.js'

describe('stores/moderation', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      moderation: {
        events: vi.fn(),
        groups: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  // GET /api/v2/moderate/{groups,events} are the odd ones out among v2
  // endpoints: moderateGroupsv2/moderateEventsv2 do `response()->json($ret)`
  // on a resource collection, which skips Laravel's normal Responsable
  // auto-wrap and returns a bare JSON array, not the `{"data": [...]}`
  // envelope every other v2 endpoint uses - confirmed via a live capture
  // (200 OK, body `[{"id":1,"name":"Tag Test Group",...}]` directly). A
  // plain `const { data } = ...` destructure silently produced `undefined`
  // for that shape, so the admin moderation queue rendered as "empty" with
  // no error at all, for a group that genuinely needed moderating. This
  // pins the real (bare-array) shape so a regression here doesn't require
  // live-capturing the API again to notice.
  describe('fetchGroups', () => {
    it('handles the actual bare-array response shape (not a {data: [...]} envelope)', async () => {
      mockApi.moderation.groups.mockResolvedValue([{ id: 1, name: 'Tag Test Group' }])

      const store = useModerationStore()
      await store.fetchGroups()

      expect(store.groups.data).toEqual([{ id: 1, name: 'Tag Test Group' }])
      expect(store.groups.loading).toBe(false)
      expect(store.groups.error).toBeNull()
    })

    // Tolerated in case the backend envelope is ever normalised to match
    // the rest of the v2 API - this must keep working either way.
    it('also handles a {data: [...]} envelope, should the backend ever be normalised', async () => {
      mockApi.moderation.groups.mockResolvedValue({ data: [{ id: 1, name: 'Tag Test Group' }] })

      const store = useModerationStore()
      await store.fetchGroups()

      expect(store.groups.data).toEqual([{ id: 1, name: 'Tag Test Group' }])
    })

    it('defaults to an empty array when the response is empty/falsy', async () => {
      mockApi.moderation.groups.mockResolvedValue(null)

      const store = useModerationStore()
      await store.fetchGroups()

      expect(store.groups.data).toEqual([])
    })

    it('sets error and rethrows on failure', async () => {
      const error = { status: 500 }
      mockApi.moderation.groups.mockRejectedValue(error)

      const store = useModerationStore()
      await expect(store.fetchGroups()).rejects.toStrictEqual(error)
      expect(store.groups.error).toStrictEqual(error)
      expect(store.groups.loading).toBe(false)
      expect(store.groups.data).toEqual([])
    })
  })

  describe('fetchEvents', () => {
    // moderateEventsv2 has the identical response()->json($ret) pattern as
    // moderateGroupsv2 - same bug, same shape, same fix.
    it('handles the actual bare-array response shape (not a {data: [...]} envelope)', async () => {
      mockApi.moderation.events.mockResolvedValue([{ id: 7, title: 'Repair Café' }])

      const store = useModerationStore()
      await store.fetchEvents()

      expect(store.events.data).toEqual([{ id: 7, title: 'Repair Café' }])
    })
  })
})
