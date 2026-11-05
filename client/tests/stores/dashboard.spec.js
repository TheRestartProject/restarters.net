import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useDashboardStore } from '../../app/stores/dashboard.js'

describe('stores/dashboard', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      dashboard: {
        fetch: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  it('starts empty, not loading, no error', () => {
    const store = useDashboardStore()
    expect(store.data).toBeNull()
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetch() sets loading while in flight and populates data on success', async () => {
    const dashboardData = {
      your_groups: [{ id: 1, name: 'Test Group', role: 3, archived: false, image_url: null }],
      nearby_groups: [],
      new_nearby_groups: [],
      upcoming_events: [],
    }

    let resolveFetch
    mockApi.dashboard.fetch.mockReturnValueOnce(
      new Promise((resolve) => {
        resolveFetch = resolve
      })
    )

    const store = useDashboardStore()
    const promise = store.fetch()

    expect(store.loading).toBe(true)

    resolveFetch({ data: dashboardData })
    const result = await promise

    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
    expect(store.data).toEqual(dashboardData)
    expect(result).toEqual(dashboardData)
  })

  it('fetch() sets error and rethrows on failure, leaving loading false', async () => {
    const apiError = { status: 500, message: 'Server error' }
    mockApi.dashboard.fetch.mockRejectedValueOnce(apiError)

    const store = useDashboardStore()

    await expect(store.fetch()).rejects.toEqual(apiError)

    expect(store.loading).toBe(false)
    expect(store.error).toEqual(apiError)
    expect(store.data).toBeNull()
  })

  it('fetch() clears a previous error on a fresh call', async () => {
    mockApi.dashboard.fetch.mockRejectedValueOnce({ status: 500 })
    const store = useDashboardStore()
    await expect(store.fetch()).rejects.toBeTruthy()
    expect(store.error).not.toBeNull()

    mockApi.dashboard.fetch.mockResolvedValueOnce({
      data: { your_groups: [], nearby_groups: [], new_nearby_groups: [], upcoming_events: [] },
    })
    await store.fetch()

    expect(store.error).toBeNull()
  })
})
