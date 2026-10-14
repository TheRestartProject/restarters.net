import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useFixometerStore } from '../../app/stores/fixometer.js'

describe('stores/fixometer', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      config: { homepageData: vi.fn() },
      device: { latestRepairedEvent: vi.fn() },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  describe('fetchImpactData', () => {
    it('populates impactData.data on success', async () => {
      mockApi.config.homepageData.mockResolvedValueOnce({ participants: 42 })

      const store = useFixometerStore()
      await store.fetchImpactData()

      expect(store.impactData.data).toEqual({ participants: 42 })
      expect(store.impactData.loading).toBe(false)
      expect(store.impactData.loaded).toBe(true)
    })

    it('does not refetch once loaded unless force is passed', async () => {
      mockApi.config.homepageData.mockResolvedValue({ participants: 1 })

      const store = useFixometerStore()
      await store.fetchImpactData()
      await store.fetchImpactData()
      expect(mockApi.config.homepageData).toHaveBeenCalledTimes(1)

      await store.fetchImpactData({ force: true })
      expect(mockApi.config.homepageData).toHaveBeenCalledTimes(2)
    })

    it('records the error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.config.homepageData.mockRejectedValueOnce(apiError)

      const store = useFixometerStore()
      await expect(store.fetchImpactData()).rejects.toEqual(apiError)
      expect(store.impactData.error).toEqual(apiError)
      expect(store.impactData.loading).toBe(false)
      expect(store.impactData.loaded).toBe(false)
    })
  })

  describe('fetchLatestRepaired', () => {
    it('populates latestRepaired.data on success', async () => {
      mockApi.device.latestRepairedEvent.mockResolvedValueOnce({
        data: { id: 9, waste_prevented: 12.3, group: { id: 3, name: 'A Group' } },
      })

      const store = useFixometerStore()
      await store.fetchLatestRepaired()

      expect(store.latestRepaired.data).toEqual({ id: 9, waste_prevented: 12.3, group: { id: 3, name: 'A Group' } })
    })

    it('treats a null data payload (no finished event yet) as a loaded, non-error state', async () => {
      mockApi.device.latestRepairedEvent.mockResolvedValueOnce({ data: null })

      const store = useFixometerStore()
      await store.fetchLatestRepaired()

      expect(store.latestRepaired.data).toBeNull()
      expect(store.latestRepaired.loaded).toBe(true)
      expect(store.latestRepaired.error).toBeNull()
    })

    it('does not refetch once loaded unless force is passed', async () => {
      mockApi.device.latestRepairedEvent.mockResolvedValue({ data: null })

      const store = useFixometerStore()
      await store.fetchLatestRepaired()
      await store.fetchLatestRepaired()
      expect(mockApi.device.latestRepairedEvent).toHaveBeenCalledTimes(1)

      await store.fetchLatestRepaired({ force: true })
      expect(mockApi.device.latestRepairedEvent).toHaveBeenCalledTimes(2)
    })

    it('records the error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.device.latestRepairedEvent.mockRejectedValueOnce(apiError)

      const store = useFixometerStore()
      await expect(store.fetchLatestRepaired()).rejects.toEqual(apiError)
      expect(store.latestRepaired.error).toEqual(apiError)
    })
  })
})
