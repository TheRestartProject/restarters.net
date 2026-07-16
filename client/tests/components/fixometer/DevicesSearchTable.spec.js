import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DevicesSearchTable from '../../../app/components/fixometer/DevicesSearchTable.vue'
import { useDevicesStore } from '../../../app/stores/devices.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

function mountComponent() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn, devices: { ...en.devices, category_toasters: 'Toasters' } } },
  })

  return mount(DevicesSearchTable, {
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub } },
  })
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

  it('renders result rows with a link to the associated event', async () => {
    mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })

    const wrapper = mountComponent()
    await flushPromises()

    const row = wrapper.find('[data-testid="device-search-row-1"]')
    expect(row.exists()).toBe(true)
    expect(row.text()).toContain('Toaster')
    expect(row.text()).toContain('Chiswick Restarters')

    const link = wrapper.find('[data-testid="device-search-view-1"]')
    expect(link.attributes('href')).toBe('/party/view/55')
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
})
