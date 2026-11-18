import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { watch } from 'vue'
import { useDevicesStore } from '../../app/stores/devices.js'
import { useToastStore } from '../../app/stores/toast.js'

describe('stores/devices', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      device: {
        create: vi.fn(),
        update: vi.fn(),
        del: vi.fn(),
        options: vi.fn(),
        itemTypes: vi.fn(),
        categories: vi.fn(),
        categoryClusters: vi.fn(),
        brands: vi.fn(),
        uploadImage: vi.fn(),
        deleteImage: vi.fn(),
        search: vi.fn(),
      },
      event: {
        devices: vi.fn(),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  describe('fetchForEvent', () => {
    it('populates byEvent[id].data on success', async () => {
      mockApi.event.devices.mockResolvedValueOnce({ data: [{ id: 1, item_type: 'Toaster' }] })

      const store = useDevicesStore()
      await store.fetchForEvent(5)

      expect(store.list(5)).toEqual([{ id: 1, item_type: 'Toaster' }])
      expect(store.listLoading(5)).toBe(false)
    })

    it('does not refetch a loaded list unless force is passed', async () => {
      mockApi.event.devices.mockResolvedValue({ data: [] })

      const store = useDevicesStore()
      await store.fetchForEvent(5)
      await store.fetchForEvent(5)
      expect(mockApi.event.devices).toHaveBeenCalledTimes(1)

      await store.fetchForEvent(5, { force: true })
      expect(mockApi.event.devices).toHaveBeenCalledTimes(2)
    })

    it('records the error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.event.devices.mockRejectedValueOnce(apiError)

      const store = useDevicesStore()
      await expect(store.fetchForEvent(5)).rejects.toEqual(apiError)
      expect(store.listError(5)).toEqual(apiError)
    })

    // The other fetchForEvent tests only re-read state AFTER awaiting, so
    // they pass even if the mutations bypass reactivity. This one pins the
    // notification itself: a subscriber that rendered the loading state
    // must be told when the first fetch settles. (The event page's devices
    // panel hung on its skeleton forever without this - the action mutated
    // the raw pre-assignment object, not the reactive proxy, so whether the
    // panel updated depended on whether the fetch beat the first render:
    // the CI spare-parts flake.)
    it('notifies reactive subscribers when the first fetch settles', async () => {
      let resolveFetch
      mockApi.event.devices.mockReturnValueOnce(new Promise((resolve) => { resolveFetch = resolve }))

      const store = useDevicesStore()
      const loadingSeen = []
      const countSeen = []
      watch(() => store.listLoading(5), (v) => loadingSeen.push(v), { flush: 'sync' })
      watch(() => store.list(5).length, (v) => countSeen.push(v), { flush: 'sync' })

      const pending = store.fetchForEvent(5)
      expect(loadingSeen).toEqual([true])

      resolveFetch({ data: [{ id: 1, item_type: 'Toaster' }] })
      await pending

      expect(loadingSeen).toEqual([true, false])
      expect(countSeen).toEqual([1])
    })
  })

  describe('addDevice', () => {
    it('optimistically appends a placeholder row, then reconciles with the server device', async () => {
      const store = useDevicesStore()
      store.byEvent[5] = { data: [], loading: false, error: null, loaded: true }

      let resolveCreate
      mockApi.device.create.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveCreate = resolve
        })
      )

      const promise = store.addDevice(5, { category: 10, item_type: 'Toaster' })

      // Optimistic row present immediately, before the server responds.
      expect(store.list(5)).toHaveLength(1)
      expect(store.list(5)[0].item_type).toBe('Toaster')
      expect(store.list(5)[0].id).toBeLessThan(0)

      resolveCreate({ id: 42, device: { id: 42, item_type: 'Toaster' }, stats: { fixed_devices: 1 } })
      await promise

      expect(store.list(5)).toEqual([{ id: 42, item_type: 'Toaster' }])
    })

    it('sends eventid merged into the payload', async () => {
      mockApi.device.create.mockResolvedValueOnce({ id: 42, device: { id: 42 }, stats: {} })

      const store = useDevicesStore()
      store.byEvent[5] = { data: [], loading: false, error: null, loaded: true }
      await store.addDevice(5, { category: 10, item_type: 'Toaster' })

      expect(mockApi.device.create).toHaveBeenCalledWith({ category: 10, item_type: 'Toaster', eventid: 5 })
    })

    it('drops the optimistic row and toasts on failure', async () => {
      const apiError = { status: 500 }
      mockApi.device.create.mockRejectedValueOnce(apiError)

      const store = useDevicesStore()
      store.byEvent[5] = { data: [], loading: false, error: null, loaded: true }
      const toastStore = useToastStore()

      await expect(store.addDevice(5, { category: 10 })).rejects.toEqual(apiError)

      expect(store.list(5)).toEqual([])
      expect(toastStore.toasts).toHaveLength(1)
    })
  })

  describe('updateDevice', () => {
    it('optimistically patches the safe scalar fields, then replaces with the server device on success', async () => {
      const store = useDevicesStore()
      store.byEvent[5] = {
        data: [{ id: 1, item_type: 'Old name', category: { id: 10, name: 'Toaster', powered: true } }],
        loading: false,
        error: null,
        loaded: true,
      }

      let resolveUpdate
      mockApi.device.update.mockReturnValueOnce(
        new Promise((resolve) => {
          resolveUpdate = resolve
        })
      )

      const promise = store.updateDevice(5, 1, { category: 10, item_type: 'New name' })

      // Optimistic patch applied immediately - category untouched (shape
      // mismatch between the int payload and the object read-shape).
      expect(store.list(5)[0].item_type).toBe('New name')
      expect(store.list(5)[0].category).toEqual({ id: 10, name: 'Toaster', powered: true })

      resolveUpdate({
        id: 1,
        device: { id: 1, item_type: 'New name', category: { id: 10, name: 'Toaster', powered: true } },
        stats: {},
      })
      await promise

      expect(store.list(5)[0]).toEqual({ id: 1, item_type: 'New name', category: { id: 10, name: 'Toaster', powered: true } })
    })

    it('reverts to the previous device and toasts on failure', async () => {
      const original = { id: 1, item_type: 'Old name', category: { id: 10, name: 'Toaster', powered: true } }
      const apiError = { status: 500 }
      mockApi.device.update.mockRejectedValueOnce(apiError)

      const store = useDevicesStore()
      store.byEvent[5] = { data: [original], loading: false, error: null, loaded: true }
      const toastStore = useToastStore()

      await expect(store.updateDevice(5, 1, { category: 10, item_type: 'New name' })).rejects.toEqual(apiError)

      expect(store.list(5)[0]).toEqual(original)
      expect(toastStore.toasts).toHaveLength(1)
    })

    it('sends eventid merged into the payload', async () => {
      mockApi.device.update.mockResolvedValueOnce({ id: 1, device: { id: 1 }, stats: {} })

      const store = useDevicesStore()
      store.byEvent[5] = { data: [{ id: 1 }], loading: false, error: null, loaded: true }
      await store.updateDevice(5, 1, { category: 10 })

      expect(mockApi.device.update).toHaveBeenCalledWith(1, { category: 10, eventid: 5 })
    })
  })

  describe('deleteDevice', () => {
    it('optimistically removes the row on success', async () => {
      mockApi.device.del.mockResolvedValueOnce({ id: 1, stats: { fixed_devices: 0 } })

      const store = useDevicesStore()
      store.byEvent[5] = { data: [{ id: 1 }, { id: 2 }], loading: false, error: null, loaded: true }

      await store.deleteDevice(5, 1)

      expect(store.list(5)).toEqual([{ id: 2 }])
    })

    it('restores the row at its original index and toasts on failure', async () => {
      const apiError = { status: 500 }
      mockApi.device.del.mockRejectedValueOnce(apiError)

      const store = useDevicesStore()
      const rowA = { id: 1 }
      const rowB = { id: 2 }
      const rowC = { id: 3 }
      store.byEvent[5] = { data: [rowA, rowB, rowC], loading: false, error: null, loaded: true }
      const toastStore = useToastStore()

      await expect(store.deleteDevice(5, 2)).rejects.toEqual(apiError)

      expect(store.list(5)).toEqual([rowA, rowB, rowC])
      expect(toastStore.toasts).toHaveLength(1)
    })
  })

  describe('image upload/delete', () => {
    it('uploadDeviceImage force-refetches the event device list (the upload response has no id/idxref to patch with)', async () => {
      mockApi.device.uploadImage.mockResolvedValueOnce({ data: { image_url: 'https://x/uploads/mid_a.jpg' } })
      mockApi.event.devices.mockResolvedValueOnce({ data: [{ id: 1, images: [{ idxref: 9, path: 'a.jpg' }] }] })

      const store = useDevicesStore()
      store.byEvent[5] = { data: [{ id: 1, images: [] }], loading: false, error: null, loaded: true }

      await store.uploadDeviceImage(5, 1, 'upload-key-1')

      expect(mockApi.device.uploadImage).toHaveBeenCalledWith(1, 'upload-key-1')
      expect(mockApi.event.devices).toHaveBeenCalledWith(5)
      expect(store.list(5)).toEqual([{ id: 1, images: [{ idxref: 9, path: 'a.jpg' }] }])
    })

    it('deleteDeviceImage keys off idxref and force-refetches', async () => {
      mockApi.device.deleteImage.mockResolvedValueOnce({ data: { deleted: true } })
      mockApi.event.devices.mockResolvedValueOnce({ data: [{ id: 1, images: [] }] })

      const store = useDevicesStore()
      store.byEvent[5] = { data: [{ id: 1, images: [{ idxref: 9, path: 'a.jpg' }] }], loading: false, error: null, loaded: true }

      await store.deleteDeviceImage(5, 1, 9)

      expect(mockApi.device.deleteImage).toHaveBeenCalledWith(1, 9)
      expect(store.list(5)).toEqual([{ id: 1, images: [] }])
    })
  })

  describe('cached metadata fetches', () => {
    it('only fetches item types once unless forced', async () => {
      mockApi.device.itemTypes.mockResolvedValue({ data: [{ type: 'Toaster', powered: true }] })

      const store = useDevicesStore()
      await store.fetchItemTypes()
      await store.fetchItemTypes()
      expect(mockApi.device.itemTypes).toHaveBeenCalledTimes(1)

      await store.fetchItemTypes({ force: true })
      expect(mockApi.device.itemTypes).toHaveBeenCalledTimes(2)
    })

    it('ensureMetaLoaded fetches item types, categories, cluster headers, brands and options in parallel', async () => {
      mockApi.device.itemTypes.mockResolvedValue({ data: [] })
      mockApi.device.categories.mockResolvedValue({ data: [] })
      mockApi.device.categoryClusters.mockResolvedValue({ data: [] })
      mockApi.device.brands.mockResolvedValue({ data: [] })
      mockApi.device.options.mockResolvedValue({ data: { barriers: [], spare_parts: [], next_steps: [] } })

      const store = useDevicesStore()
      await store.ensureMetaLoaded()

      expect(mockApi.device.itemTypes).toHaveBeenCalledTimes(1)
      expect(mockApi.device.categories).toHaveBeenCalledTimes(1)
      expect(mockApi.device.categoryClusters).toHaveBeenCalledTimes(1)
      expect(mockApi.device.brands).toHaveBeenCalledTimes(1)
      expect(mockApi.device.options).toHaveBeenCalledTimes(1)
    })

    it('does not let one metadata fetch failing block the others', async () => {
      mockApi.device.itemTypes.mockRejectedValueOnce({ status: 500 })
      mockApi.device.categories.mockResolvedValue({ data: [] })
      mockApi.device.categoryClusters.mockResolvedValue({ data: [] })
      mockApi.device.brands.mockResolvedValue({ data: [{ id: 1, brand_name: 'Acme' }] })
      mockApi.device.options.mockResolvedValue({ data: { barriers: [], spare_parts: [], next_steps: [] } })

      const store = useDevicesStore()
      await store.ensureMetaLoaded()

      expect(store.itemTypes.data).toEqual([])
      expect(store.brands.data).toEqual([{ id: 1, brand_name: 'Acme' }])
    })
  })

  describe('clusters getter', () => {
    it('groups flat categories under their category-cluster headers, in header order', async () => {
      mockApi.device.categories.mockResolvedValueOnce({
        data: [
          { id: 10, name: 'Toaster', powered: true, cluster: 2, cluster_name: 'Kitchen' },
          { id: 11, name: 'Kettle', powered: true, cluster: 2, cluster_name: 'Kitchen' },
          { id: 20, name: 'Bicycle', powered: false, cluster: 1, cluster_name: 'Outdoors' },
        ],
      })
      mockApi.device.categoryClusters.mockResolvedValueOnce({
        data: [
          { id: 1, name: 'Outdoors' },
          { id: 2, name: 'Kitchen' },
        ],
      })

      const store = useDevicesStore()
      await store.fetchCategories()
      await store.fetchClusterHeaders()

      expect(store.clusters).toEqual([
        { id: 1, name: 'Outdoors', categories: [{ idcategories: 20, name: 'Bicycle', powered: false }] },
        {
          id: 2,
          name: 'Kitchen',
          categories: [
            { idcategories: 10, name: 'Toaster', powered: true },
            { idcategories: 11, name: 'Kettle', powered: true },
          ],
        },
      ])
    })
  })

  describe('searchDevices', () => {
    it('populates searchResults.data/count on success and passes params straight through', async () => {
      mockApi.device.search.mockResolvedValueOnce({
        data: { items: [{ id: 1, item_type: 'Toaster' }], count: 37 },
      })

      const store = useDevicesStore()
      await store.searchDevices({ page: 2, size: 20, powered: true })

      expect(mockApi.device.search).toHaveBeenCalledWith({ page: 2, size: 20, powered: true })
      expect(store.searchResults.data).toEqual([{ id: 1, item_type: 'Toaster' }])
      expect(store.searchResults.count).toBe(37)
      expect(store.searchResults.loading).toBe(false)
    })

    it('always refetches - no loaded guard, unlike fetchForEvent', async () => {
      mockApi.device.search.mockResolvedValue({ data: { items: [], count: 0 } })

      const store = useDevicesStore()
      await store.searchDevices({ page: 1 })
      await store.searchDevices({ page: 1 })

      expect(mockApi.device.search).toHaveBeenCalledTimes(2)
    })

    it('records the error and rethrows on failure', async () => {
      const apiError = { status: 500 }
      mockApi.device.search.mockRejectedValueOnce(apiError)

      const store = useDevicesStore()
      await expect(store.searchDevices({ page: 1 })).rejects.toEqual(apiError)
      expect(store.searchResults.error).toEqual(apiError)
      expect(store.searchResults.loading).toBe(false)
    })

    it('applies only the newest search when responses arrive out of order', async () => {
      // Two overlapping searches (e.g. a sort click then a filter edit); the
      // STALE first-issued one resolves LAST and must not clobber the fresher
      // results.
      let resolveStale
      let resolveFresh
      mockApi.device.search
        .mockImplementationOnce(() => new Promise((resolve) => { resolveStale = resolve }))
        .mockImplementationOnce(() => new Promise((resolve) => { resolveFresh = resolve }))

      const store = useDevicesStore()
      const stale = store.searchDevices({ page: 1, brand: 'stale' })
      const fresh = store.searchDevices({ page: 1, brand: 'fresh' })

      resolveFresh({ data: { items: [{ id: 2, item_type: 'Fresh' }], count: 1 } })
      await fresh
      expect(store.searchResults.data).toEqual([{ id: 2, item_type: 'Fresh' }])

      resolveStale({ data: { items: [{ id: 1, item_type: 'Stale' }], count: 9 } })
      await stale
      // The stale response is ignored; the fresh results stand.
      expect(store.searchResults.data).toEqual([{ id: 2, item_type: 'Fresh' }])
      expect(store.searchResults.count).toBe(1)
      expect(store.searchResults.loading).toBe(false)
    })
  })
})
