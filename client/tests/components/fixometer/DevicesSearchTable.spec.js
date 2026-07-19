import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DevicesSearchTable from '../../../app/components/fixometer/DevicesSearchTable.vue'
import { useDevicesStore } from '../../../app/stores/devices.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
// Same shape as components/admin/AdminCrudTable.spec.js's BModalStub - only
// renders its slot while open, and exposes `title` for assertions.
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-modal-title="title"><slot /></div>',
}
const DeviceFormStub = {
  props: ['eventId', 'device', 'powered'],
  emits: ['saved', 'cancel'],
  template: '<div data-testid="device-form-stub">{{ eventId }}:{{ device && device.id }}:{{ powered }}</div>',
}

function mountComponent() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn, devices: { ...en.devices, category_toasters: 'Toasters' } } },
  })

  return mount(DevicesSearchTable, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, BButton: BButtonStub, BModal: BModalStub, DeviceForm: DeviceFormStub },
    },
  })
}

// Mirrors AppNavbar.spec.js's setLoggedInUser: useAuth()'s hasRole() reads
// authStore.user.role_name directly.
function setAdmin() {
  useAuthStore().user = { role_name: 'Administrator' }
}

function device(overrides = {}) {
  return {
    id: 1,
    eventid: 55,
    item_type: 'Toaster',
    category: { id: 10, name: 'devices.category_toasters', powered: true },
    brand: 'Acme',
    short_problem: "Won't heat",
    groupname: 'Chiswick Restarters',
    repair_status: 'Fixed',
    created_at: '2026-01-15T00:00:00Z',
    ...overrides,
  }
}

describe('components/fixometer/DevicesSearchTable', () => {
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())

    mockApi = {
      device: {
        search: vi.fn().mockResolvedValue({ data: { items: [], count: 0 } }),
        itemTypes: vi.fn().mockResolvedValue({ data: [] }),
        categories: vi.fn().mockResolvedValue({
          data: [
            { id: 10, name: 'devices.category_toasters', powered: true, cluster: 1, cluster_name: 'Kitchen' },
            { id: 20, name: 'devices.category_bikes', powered: false, cluster: 2, cluster_name: 'Outdoors' },
          ],
        }),
        categoryClusters: vi.fn().mockResolvedValue({
          data: [
            { id: 1, name: 'client.groups.cluster_computers' },
            { id: 2, name: 'client.groups.cluster_household' },
          ],
        }),
        brands: vi.fn().mockResolvedValue({ data: [] }),
        options: vi.fn().mockResolvedValue({ data: { barriers: [], spare_parts: [], next_steps: [] } }),
      },
    }

    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
  })

  it('searches on mount with the default (powered) filters', async () => {
    mountComponent()
    await flushPromises()

    expect(mockApi.device.search).toHaveBeenCalledTimes(1)
    expect(mockApi.device.search).toHaveBeenCalledWith(
      expect.objectContaining({ page: 1, size: 20, powered: true, sortBy: 'event_start_utc', sortDesc: 'DESC' })
    )
  })

  it('maps a category filter change to the category query param and resets to page 1', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    mockApi.device.search.mockClear()

    await wrapper.find('[data-testid="device-search-category"]').setValue('10')
    await flushPromises()

    expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ category: 10, page: 1 }))
  })

  it('sends brand/model only when powered, and item_type only when unpowered', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    mockApi.device.search.mockClear()

    await wrapper.find('[data-testid="device-search-brand"]').setValue('Bosch')
    await flushPromises()
    expect(mockApi.device.search).toHaveBeenLastCalledWith(expect.objectContaining({ brand: 'Bosch' }))

    mockApi.device.search.mockClear()
    await wrapper.find('[data-testid="device-search-powered-false"]').trigger('click')
    await flushPromises()

    const lastCall = mockApi.device.search.mock.calls.at(-1)[0]
    expect(lastCall.powered).toBe(false)
    expect(lastCall.brand).toBeUndefined()
    expect(lastCall.model).toBeUndefined()

    mockApi.device.search.mockClear()
    await wrapper.find('[data-testid="device-search-item-type"]').setValue('Bicycle')
    await flushPromises()
    expect(mockApi.device.search).toHaveBeenLastCalledWith(expect.objectContaining({ item_type: 'Bicycle' }))
  })

  it('maps the status filter to the numeric Device::REPAIR_STATUS_* code, not the resource string', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    mockApi.device.search.mockClear()

    await wrapper.find('[data-testid="device-search-status"]').setValue('2')
    await flushPromises()

    expect(mockApi.device.search).toHaveBeenLastCalledWith(expect.objectContaining({ status: 2 }))
  })

  it('maps from_date/to_date/group/comments filters straight through', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    mockApi.device.search.mockClear()

    await wrapper.find('[data-testid="device-search-group"]').setValue('Chiswick')
    await flushPromises()
    expect(mockApi.device.search).toHaveBeenLastCalledWith(expect.objectContaining({ group: 'Chiswick' }))

    mockApi.device.search.mockClear()
    await wrapper.find('[data-testid="device-search-from-date"]').setValue('2026-01-01')
    await flushPromises()
    expect(mockApi.device.search).toHaveBeenLastCalledWith(expect.objectContaining({ from_date: '2026-01-01' }))
  })

  it('shows an empty state when the search returns no items', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    expect(wrapper.find('[data-testid="device-search-empty"]').exists()).toBe(true)
  })

  it('still renders the table with column headers when there are no results', async () => {
    // Matches legacy b-table (show-empty): the column scaffold stays put on an
    // empty result set, rather than the whole table vanishing.
    const wrapper = mountComponent()
    await flushPromises()

    const table = wrapper.find('[data-testid="device-search-results"]')
    expect(table.exists()).toBe(true)
    // Header text now includes the sort-arrow glyphs, so match on substring.
    const headers = table.findAll('thead th').map((h) => h.text()).filter(Boolean)
    expect(headers.some((h) => h.includes('Category'))).toBe(true)
    expect(headers.some((h) => h.includes('Status'))).toBe(true)
    // the empty message sits inside the table, not instead of it
    expect(table.find('[data-testid="device-search-empty"]').exists()).toBe(true)
  })

  it('renders result rows and reveals a link to the event in the info details panel', async () => {
    mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })

    const wrapper = mountComponent()
    await flushPromises()

    const row = wrapper.find('[data-testid="device-search-row-1"]')
    expect(row.exists()).toBe(true)
    expect(row.text()).toContain('Toaster')
    expect(row.text()).toContain('Chiswick Restarters')

    // The event link lives in the 'i' info details panel (legacy row-details),
    // hidden until the icon is clicked.
    expect(wrapper.find('[data-testid="device-search-view-1"]').exists()).toBe(false)
    await wrapper.find('[data-testid="device-search-info-1"]').trigger('click')
    const link = wrapper.find('[data-testid="device-search-view-1"]')
    expect(link.attributes('href')).toBe('/party/view/55')
  })

  it('sorts by a column ascending on first click and reverses on the next', async () => {
    mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
    const wrapper = mountComponent()
    await flushPromises()
    mockApi.device.search.mockClear()

    await wrapper.find('[data-testid="device-search-sort-item_type"]').trigger('click')
    await flushPromises()
    expect(mockApi.device.search).toHaveBeenLastCalledWith(
      expect.objectContaining({ sortBy: 'item_type', sortDesc: 'ASC', page: 1 })
    )

    mockApi.device.search.mockClear()
    await wrapper.find('[data-testid="device-search-sort-item_type"]').trigger('click')
    await flushPromises()
    expect(mockApi.device.search).toHaveBeenLastCalledWith(
      expect.objectContaining({ sortBy: 'item_type', sortDesc: 'DESC' })
    )
  })

  it('exposes sort controls for exactly the legacy-sortable columns (Assessment is not sortable)', async () => {
    // powered=true by default, so the Brand column is present too.
    const wrapper = mountComponent()
    await flushPromises()

    for (const key of ['item_type', 'category', 'brand', 'groupname', 'repair_status', 'created_at']) {
      expect(wrapper.find(`[data-testid="device-search-sort-${key}"]`).exists()).toBe(true)
    }
    // Assessment renders as a header but carries no sort control (matching legacy).
    const headerTexts = wrapper.findAll('thead th').map((h) => h.text())
    expect(headerTexts.some((t) => t.includes('Assessment'))).toBe(true)
    for (const notSortable of ['assessment', 'problem', 'short_problem']) {
      expect(wrapper.find(`[data-testid="device-search-sort-${notSortable}"]`).exists()).toBe(false)
    }
  })

  it('expands details rows independently, so several can be open at once', async () => {
    mockApi.device.search.mockResolvedValue({
      data: { items: [device({ id: 1 }), device({ id: 2, item_type: 'Kettle' })], count: 2 },
    })
    const wrapper = mountComponent()
    await flushPromises()

    await wrapper.find('[data-testid="device-search-info-1"]').trigger('click')
    expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="device-search-details-2"]').exists()).toBe(false)

    // Opening the second must not close the first.
    await wrapper.find('[data-testid="device-search-info-2"]').trigger('click')
    expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="device-search-details-2"]').exists()).toBe(true)
  })

  it('toggles a read-only details row via the info icon', async () => {
    mockApi.device.search.mockResolvedValue({
      data: { items: [device({ model: 'AB-100', problem: 'Full assessment text' })], count: 1 },
    })
    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(false)

    await wrapper.find('[data-testid="device-search-info-1"]').trigger('click')
    const details = wrapper.find('[data-testid="device-search-details-1"]')
    expect(details.exists()).toBe(true)
    expect(details.text()).toContain('AB-100')
    expect(details.text()).toContain('Full assessment text')

    await wrapper.find('[data-testid="device-search-info-1"]').trigger('click')
    expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(false)
  })

  it('paginates: next/prev change the page param and are bounded', async () => {
    mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 45 } })

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="device-search-prev-page"]').attributes('disabled')).toBeDefined()

    mockApi.device.search.mockClear()
    await wrapper.find('[data-testid="device-search-next-page"]').trigger('click')
    await flushPromises()

    expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ page: 2 }))
    expect(wrapper.find('[data-testid="device-search-page-indicator"]').text()).toContain('2')

    mockApi.device.search.mockClear()
    await wrapper.find('[data-testid="device-search-prev-page"]').trigger('click')
    await flushPromises()
    expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ page: 1 }))
  })

  it('shows the search error state when the store has an error', async () => {
    // Set the error directly (as the party/index page tests do) rather than
    // rejecting the mocked API call, which would leave the component's
    // fire-and-forget onMounted() search an unhandled rejection.
    const store = useDevicesStore()
    const wrapper = mountComponent()
    await flushPromises()

    store.searchResults.error = { status: 500 }
    await flushPromises()

    expect(wrapper.find('[data-testid="device-search-error"]').exists()).toBe(true)
  })

  // Gap fix (MEDIUM): legacy FixometerRecordsTable.vue gave Administrators
  // inline edit + delete on every row; this table used to offer only the
  // read-only 'i' info toggle for every viewer, including admins.
  describe('admin edit/delete', () => {
    it('does not show edit/delete controls for a non-admin viewer', async () => {
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-edit-1"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-search-delete-1"]').exists()).toBe(false)
      // The read-only info toggle is unaffected.
      expect(wrapper.find('[data-testid="device-search-info-1"]').exists()).toBe(true)
    })

    it('shows edit/delete controls for an Administrator', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-edit-1"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="device-search-delete-1"]').exists()).toBe(true)
      // The read-only info toggle is still there too - a pure addition, not a swap.
      expect(wrapper.find('[data-testid="device-search-info-1"]').exists()).toBe(true)
    })

    it('swaps the row for DeviceForm when an admin clicks Edit, and re-runs the search on save', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-edit-1"]').trigger('click')
      expect(wrapper.find('[data-testid="device-search-row-1"]').exists()).toBe(false)
      const form = wrapper.findComponent(DeviceFormStub)
      expect(form.exists()).toBe(true)
      expect(form.props('eventId')).toBe(55)
      expect(form.props('device').id).toBe(1)
      expect(form.props('powered')).toBe(true)

      mockApi.device.search.mockClear()
      await form.vm.$emit('saved')
      await flushPromises()

      expect(mockApi.device.search).toHaveBeenCalledTimes(1)
      expect(wrapper.find('[data-testid="device-search-row-1"]').exists()).toBe(true)
    })

    it('closes the edit form on cancel without re-running the search', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-edit-1"]').trigger('click')
      mockApi.device.search.mockClear()
      await wrapper.findComponent(DeviceFormStub).vm.$emit('cancel')
      await flushPromises()

      expect(mockApi.device.search).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="device-search-row-1"]').exists()).toBe(true)
    })

    it('requires a delete confirm-modal click before calling the store, then re-runs the search', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const store = useDevicesStore()
      store.deleteDevice = vi.fn().mockResolvedValue()

      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-delete-1"]').trigger('click')
      expect(store.deleteDevice).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="device-search-delete-modal"]').exists()).toBe(true)

      mockApi.device.search.mockClear()
      await wrapper.find('[data-testid="device-search-delete-confirm"]').trigger('click')
      await flushPromises()

      expect(store.deleteDevice).toHaveBeenCalledWith(55, 1)
      expect(mockApi.device.search).toHaveBeenCalledTimes(1)
      expect(wrapper.find('[data-testid="device-search-delete-modal"]').exists()).toBe(false)
    })

    it('does not call the store when delete is cancelled', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const store = useDevicesStore()
      store.deleteDevice = vi.fn().mockResolvedValue()

      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-delete-1"]').trigger('click')
      await wrapper.find('[data-testid="device-search-delete-cancel"]').trigger('click')
      await flushPromises()

      expect(store.deleteDevice).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="device-search-delete-modal"]').exists()).toBe(false)
    })

    it('shows an error and keeps the modal open when delete fails', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const store = useDevicesStore()
      store.deleteDevice = vi.fn().mockRejectedValue({ status: 500 })

      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-delete-1"]').trigger('click')
      await wrapper.find('[data-testid="device-search-delete-confirm"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-delete-modal"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="device-search-delete-error"]').exists()).toBe(true)
    })
  })
})
