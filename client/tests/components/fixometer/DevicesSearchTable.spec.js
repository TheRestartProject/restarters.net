import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import moment from 'moment'
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
  props: ['eventId', 'device', 'powered', 'readonly', 'deleteButton', 'cancelButton', 'showInfo'],
  emits: ['saved', 'cancel', 'deleted'],
  template:
    '<div data-testid="device-form-stub" :data-readonly="readonly" :data-delete-button="deleteButton" :data-cancel-button="cancelButton">{{ eventId }}:{{ device && device.id }}:{{ powered }}</div>',
}
// bootstrap-vue-next's BPagination is only stubbed to keep the paging
// assertions independent of its internal page-number rendering; the
// data-testid on <BPagination> in the real template falls through onto
// this stub's single root element automatically.
const BPaginationStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template:
    '<div><span data-testid="pagination-page">{{ modelValue }}</span>' +
    '<button type="button" data-testid="pagination-next" @click="$emit(\'update:modelValue\', modelValue + 1)">next</button>' +
    '<button type="button" data-testid="pagination-prev" @click="$emit(\'update:modelValue\', modelValue - 1)">prev</button></div>',
}

// The query string the page was opened with, and the router the filters write back to.
let routeQuery = {}
let routerReplace

function mountComponent() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn, devices: { ...en.devices, category_toasters: 'Toasters' } } },
  })

  return mount(DevicesSearchTable, {
    global: {
      plugins: [i18n],
      stubs: {
        NuxtLink: NuxtLinkStub,
        BBadge: BBadgeStub,
        BButton: BButtonStub,
        BModal: BModalStub,
        DeviceForm: DeviceFormStub,
        BPagination: BPaginationStub,
      },
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
    event_date: '2026-01-15T10:00:00Z',
    created_at: '2026-02-01T00:00:00Z',
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

    routeQuery = {}
    routerReplace = vi.fn()
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))
    vi.stubGlobal('useRoute', () => ({ query: routeQuery, hash: '' }))
    vi.stubGlobal('useRouter', () => ({ replace: routerReplace }))
  })

  it('searches on mount for both powered and unpowered items, most recent event first', async () => {
    mountComponent()
    await flushPromises()

    expect(mockApi.device.search).toHaveBeenCalledTimes(1)
    const params = mockApi.device.search.mock.calls[0][0]
    expect(params).toEqual(expect.objectContaining({ page: 1, size: 20, sortDesc: 'DESC' }))
    expect(params.powered).toBeUndefined()
    expect(params.sortBy).toBeUndefined()
  })

  it('maps a category filter change to the category query param and resets to page 1', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    mockApi.device.search.mockClear()

    await wrapper.find('[data-testid="device-search-category"]').setValue('10')
    await flushPromises()

    expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ category: 10, page: 1 }))
  })

  describe('filters', () => {
    it('lists the filters in order, with no tabs, info icons or table intro', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      const ids = wrapper.findAll('[data-testid="device-search-filters"] label').map((l) => l.attributes('for'))
      expect(ids).toEqual([
        'device-search-powered',
        'device-search-category',
        'device-search-item-type',
        'device-search-brand',
        'device-search-model',
        'device-search-comments',
        'device-search-status',
      ])
      const labels = wrapper.findAll('[data-testid="device-search-filters"] label').map((l) => l.text())
      expect(labels[0]).toBe(en.devices.powered_or_unpowered)
      expect(labels[2]).toBe(en.devices.model_or_type)
      expect(labels[6]).toBe(en.devices.repair_status)
      expect(wrapper.find('[data-testid="device-search-item-type"]').attributes('placeholder')).toBe(en.devices.item_type)
      expect(wrapper.findComponent({ name: 'FieldInfoPopover' }).exists()).toBe(false)
      expect(wrapper.find('.nav-tabs').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-search-table-intro"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').exists()).toBe(false)
    })

    it('offers Both, Powered and Unpowered, defaulting to Both', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      const select = wrapper.find('[data-testid="device-search-powered"]')
      expect(select.findAll('option').map((o) => o.text())).toEqual(['Both', 'Powered', 'Unpowered'])
      expect(select.element.selectedIndex).toBe(0)
    })

    it('sends powered=true or false for the selected kind, and nothing for Both', async () => {
      const wrapper = mountComponent()
      await flushPromises()
      const select = wrapper.find('[data-testid="device-search-powered"]')

      mockApi.device.search.mockClear()
      await select.setValue('false')
      await flushPromises()
      expect(mockApi.device.search.mock.calls.at(-1)[0].powered).toBe(false)

      mockApi.device.search.mockClear()
      await select.setValue('true')
      await flushPromises()
      expect(mockApi.device.search.mock.calls.at(-1)[0].powered).toBe(true)

      mockApi.device.search.mockClear()
      await select.setValue('')
      await flushPromises()
      expect(mockApi.device.search.mock.calls.at(-1)[0].powered).toBeUndefined()
    })

    it('offers the categories of the selected kind, or all of them for Both, and clears the category on a change', async () => {
      const wrapper = mountComponent()
      await flushPromises()
      const category = () => wrapper.find('[data-testid="device-search-category"]')
      const optionTexts = () => category().findAll('option').map((o) => o.text())

      expect(optionTexts()).toEqual(expect.arrayContaining(['Toasters']))
      expect(optionTexts().length).toBe(3)

      await category().setValue('10')
      await wrapper.find('[data-testid="device-search-powered"]').setValue('false')
      await flushPromises()
      expect(optionTexts()).not.toContain('Toasters')
      expect(category().element.value).not.toBe('10')
      expect(mockApi.device.search.mock.calls.at(-1)[0].category).toBeUndefined()
    })

    it('sends brand, model and item type whichever kind is selected', async () => {
      const wrapper = mountComponent()
      await flushPromises()
      await wrapper.find('[data-testid="device-search-powered"]').setValue('false')
      mockApi.device.search.mockClear()

      await wrapper.find('[data-testid="device-search-brand"]').setValue('Bosch')
      await wrapper.find('[data-testid="device-search-model"]').setValue('X1')
      await wrapper.find('[data-testid="device-search-item-type"]').setValue('Bicycle')
      await flushPromises()

      expect(mockApi.device.search).toHaveBeenLastCalledWith(
        expect.objectContaining({ brand: 'Bosch', model: 'X1', item_type: 'Bicycle', powered: false })
      )
    })
  })

  // The same custom suggestion panel as DeviceForm.vue's brand field, capped at 5.
  it('suggests known brands via a suggestion panel sourced from the brands store, capped at 5', async () => {
    mockApi.device.brands.mockResolvedValue({
      data: [
        { id: 1, brand_name: 'Bosch' },
        { id: 2, brand_name: 'Dyson' },
        { id: 3, brand_name: 'Miele' },
        { id: 4, brand_name: 'Hoover' },
        { id: 5, brand_name: 'Vax' },
        { id: 6, brand_name: 'Beko' },
      ],
    })

    const wrapper = mountComponent()
    await flushPromises()

    const input = wrapper.find('[data-testid="device-search-brand"]')
    expect(input.attributes('list')).toBeUndefined()
    expect(wrapper.find('[data-testid="device-search-brand-suggestions"]').exists()).toBe(false)

    await input.trigger('focus')
    const panel = wrapper.find('[data-testid="device-search-brand-suggestions"]')
    expect(panel.exists()).toBe(true)
    const options = panel.findAll('li')
    expect(options).toHaveLength(5)
    expect(options.map((o) => o.text())).toEqual(['Bosch', 'Dyson', 'Miele', 'Hoover', 'Vax'])
  })

  it('filters brand suggestions live as the user types, and selecting one fills the field and closes the panel', async () => {
    mockApi.device.brands.mockResolvedValue({
      data: [
        { id: 1, brand_name: 'Bosch' },
        { id: 2, brand_name: 'Dyson' },
      ],
    })

    const wrapper = mountComponent()
    await flushPromises()

    const input = wrapper.find('[data-testid="device-search-brand"]')
    await input.trigger('focus')
    await input.setValue('bos')
    await flushPromises()

    const panel = wrapper.find('[data-testid="device-search-brand-suggestions"]')
    expect(panel.findAll('li').map((o) => o.text())).toEqual(['Bosch'])

    await wrapper.find('[data-testid="device-search-brand-option-Bosch"]').trigger('mousedown')
    await flushPromises()

    expect(wrapper.find('[data-testid="device-search-brand"]').element.value).toBe('Bosch')
    expect(wrapper.find('[data-testid="device-search-brand-suggestions"]').exists()).toBe(false)
  })

  it('sends the repair status by name, as the filtered download does', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    mockApi.device.search.mockClear()

    const status = wrapper.find('[data-testid="device-search-status"]')
    expect(status.findAll('option').map((o) => o.text())).toEqual(['Any status', 'Fixed', 'Repairable', 'End-of-life'])
    await status.setValue('End of life')
    await flushPromises()

    expect(mockApi.device.search).toHaveBeenLastCalledWith(expect.objectContaining({ status: 'End of life' }))
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

  it('renders result rows with the event date as a link to the repair on the event page', async () => {
    mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })

    const wrapper = mountComponent()
    await flushPromises()

    const row = wrapper.find('[data-testid="device-search-row-1"]')
    expect(row.exists()).toBe(true)
    expect(row.text()).toContain('Toaster')
    expect(row.text()).toContain('Chiswick Restarters')
    // The event's date, not the date the record was created.
    expect(row.text()).toContain('15/01/2026')
    expect(row.text()).not.toContain('01/02/2026')

    const link = wrapper.find('[data-testid="device-search-view-1"]')
    expect(link.attributes('href')).toBe('/party/view/55#devices-section')
    expect(link.attributes('title')).toBe(en.devices.view_event_for_repair)
  })

  it('has the live column order and always shows Brand', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const headers = wrapper.findAll('thead th').map((h) => h.text())
    const order = ['Item', 'Category', 'Brand', 'Assessment', 'Status', 'Group', 'Date']
    const positions = order.map((label) => headers.findIndex((h) => h.includes(label)))
    expect(positions.every((p) => p >= 0)).toBe(true)
    expect([...positions].sort((a, b) => a - b)).toEqual(positions)
  })

  it('has a white records panel and no powered/unpowered descriptions', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('.records.bg-white').exists()).toBe(true)
    expect(wrapper.find('[data-testid="device-search-powered-description"]').exists()).toBe(false)
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

  it('exposes sort controls for the sortable columns only (Assessment is not sortable)', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    for (const key of ['item_type', 'category', 'brand', 'groupname', 'repair_status', 'event_date']) {
      expect(wrapper.find(`[data-testid="device-search-sort-${key}"]`).exists()).toBe(true)
    }
    const headerTexts = wrapper.findAll('thead th').map((h) => h.text())
    expect(headerTexts.some((t) => t.includes('Assessment'))).toBe(true)
    for (const notSortable of ['assessment', 'problem', 'short_problem', 'created_at']) {
      expect(wrapper.find(`[data-testid="device-search-sort-${notSortable}"]`).exists()).toBe(false)
    }
  })

  it('sorts by the event date through the event_date key', async () => {
    const wrapper = mountComponent()
    await flushPromises()
    mockApi.device.search.mockClear()

    await wrapper.find('[data-testid="device-search-sort-event_date"]').trigger('click')
    await flushPromises()

    expect(mockApi.device.search).toHaveBeenLastCalledWith(expect.objectContaining({ sortBy: 'event_date', sortDesc: 'ASC' }))
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

  // The expanded row is the real DeviceForm: read-only for non-admins, editable with a delete for admins, with no
  // help icons and the device's own powered flag.
  it('toggles a readonly DeviceForm details row via the info icon, for a non-admin', async () => {
    mockApi.device.search.mockResolvedValue({
      data: { items: [device({ model: 'AB-100', problem: 'Full assessment text' })], count: 1 },
    })
    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(false)

    await wrapper.find('[data-testid="device-search-info-1"]').trigger('click')
    const details = wrapper.find('[data-testid="device-search-details-1"]')
    expect(details.exists()).toBe(true)
    const form = details.findComponent(DeviceFormStub)
    expect(form.props('device').id).toBe(1)
    expect(form.props('readonly')).toBe(true)
    expect(form.props('deleteButton')).toBe(false)
    expect(form.props('cancelButton')).toBe(false)
    expect(form.props('showInfo')).toBe(false)

    await wrapper.find('[data-testid="device-search-info-1"]').trigger('click')
    expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(false)
  })

  it('passes the device\'s own powered flag to its form, whatever the filter says', async () => {
    mockApi.device.search.mockResolvedValue({
      data: { items: [device({ id: 2, category: { id: 20, name: 'devices.category_bikes', powered: false } })], count: 1 },
    })
    const wrapper = mountComponent()
    await flushPromises()

    await wrapper.find('[data-testid="device-search-info-2"]').trigger('click')
    expect(wrapper.findComponent(DeviceFormStub).props('powered')).toBe(false)
  })

  // A numbered pager, only above one page.
  describe('pagination', () => {
    it('is hidden when there is only one page of results', async () => {
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 5 } })
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-pagination"]').exists()).toBe(false)
    })

    it('shows a numbered pager above one page, and changing it re-runs the search', async () => {
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 45 } })
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-pagination"]').exists()).toBe(true)

      mockApi.device.search.mockClear()
      await wrapper.find('[data-testid="pagination-next"]').trigger('click')
      await flushPromises()

      expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ page: 2 }))
    })
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

  // Every row has one toggle: an edit pencil for admins, an info icon for everyone else.
  describe('admin edit/delete', () => {
    it('shows only the info icon (no edit-pencil) for a non-admin viewer', async () => {
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-info-1"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="device-search-edit-1"]').exists()).toBe(false)
    })

    it('shows only the edit-pencil icon (no separate info toggle) for an Administrator', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-edit-1"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="device-search-info-1"]').exists()).toBe(false)
    })

    it('opens the row into an editable, deletable DeviceForm for an admin, and re-runs the search on save', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-edit-1"]').trigger('click')
      // A pure addition alongside the row, not a swap - the summary row stays.
      expect(wrapper.find('[data-testid="device-search-row-1"]').exists()).toBe(true)

      const form = wrapper.findComponent(DeviceFormStub)
      expect(form.exists()).toBe(true)
      expect(form.props('eventId')).toBe(55)
      expect(form.props('device').id).toBe(1)
      expect(form.props('powered')).toBe(true)
      expect(form.props('readonly')).toBe(false)
      expect(form.props('deleteButton')).toBe(true)
      expect(form.props('cancelButton')).toBe(false)

      mockApi.device.search.mockClear()
      await form.vm.$emit('saved')
      await flushPromises()

      expect(mockApi.device.search).toHaveBeenCalledTimes(1)
      expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(false)
    })

    it('re-runs the search and closes the row when DeviceForm emits deleted', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-edit-1"]').trigger('click')
      mockApi.device.search.mockClear()
      await wrapper.findComponent(DeviceFormStub).vm.$emit('deleted')
      await flushPromises()

      expect(mockApi.device.search).toHaveBeenCalledTimes(1)
      expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(false)
    })

    it('re-clicking the edit-pencil closes the row without re-running the search', async () => {
      setAdmin()
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1 } })
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-edit-1"]').trigger('click')
      mockApi.device.search.mockClear()
      await wrapper.find('[data-testid="device-search-edit-1"]').trigger('click')
      await flushPromises()

      expect(mockApi.device.search).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(false)
    })
  })

  // The sections expand and collapse with an SVG icon image.
  describe('filter section expand/collapse icons', () => {
    it('swaps the item-info toggle between add-icon-brand.svg and minus-icon-brand.svg', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      const toggle = wrapper.find('[data-testid="device-search-item-info-toggle"]')
      expect(toggle.find('img').attributes('src')).toBe('/images/add-icon-brand.svg')

      await toggle.trigger('click')
      expect(wrapper.find('[data-testid="device-search-item-info-toggle"] img').attributes('src')).toBe(
        '/images/minus-icon-brand.svg'
      )
    })
  })

  describe('the query string', () => {
    it('reads the filters from the query and opens the sections that hold them', async () => {
      routeQuery = { powered: 'false', category: '20', status: 'Fixed', brand: 'Acme', model: 'M1', item_type: 'Bike', comments: 'chain', group: 'Chiswick', from_date: '2026-01-01', to_date: '2026-02-01' }
      const wrapper = mountComponent()
      await flushPromises()

      expect(mockApi.device.search).toHaveBeenCalledWith(
        expect.objectContaining({ powered: false, category: 20, status: 'Fixed', brand: 'Acme', model: 'M1', item_type: 'Bike', comments: 'chain', group: 'Chiswick', from_date: '2026-01-01', to_date: '2026-02-01' })
      )
      expect(wrapper.find('[data-testid="device-search-filters"]').element.style.display === 'none').toBe(false)
      expect(wrapper.find('[data-testid="device-search-event-info"] fieldset').element.style.display === 'none').toBe(false)
    })

    it('leaves the sections closed when the query has no filters', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-filters"]').element.style.display === 'none').toBe(true)
      expect(wrapper.find('[data-testid="device-search-event-info"] fieldset').element.style.display === 'none').toBe(true)
    })

    it('opens only the section whose filter is set', async () => {
      routeQuery = { group: 'Chiswick' }
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-filters"]').element.style.display === 'none').toBe(true)
      expect(wrapper.find('[data-testid="device-search-event-info"] fieldset').element.style.display === 'none').toBe(false)
    })

    it('understands the older per-kind category links', async () => {
      routeQuery = { category_unpowered: '20' }
      mountComponent()
      await flushPromises()

      expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ powered: false, category: 20 }))
    })

    it('writes the filters back as they change, omitting a to date of today', async () => {
      const wrapper = mountComponent()
      await flushPromises()
      routerReplace.mockClear()

      await wrapper.find('[data-testid="device-search-powered"]').setValue('true')
      await wrapper.find('[data-testid="device-search-brand"]').setValue('Acme')
      await flushPromises()

      expect(routerReplace).toHaveBeenLastCalledWith({ query: { powered: 'true', brand: 'Acme' }, hash: '' })
    })
  })

  describe('the total', () => {
    it('reports the count of the list once a search lands', async () => {
      mockApi.device.search.mockResolvedValue({ data: { items: [device()], count: 1234 } })
      const wrapper = mountComponent()
      await flushPromises()

      expect(wrapper.emitted('update:total')?.at(-1)).toEqual([1234])
    })
  })

  describe('clearing filters and the filtered download ', () => {
    const today = () => moment().format('YYYY-MM-DD')
    const lastDownload = (wrapper) => wrapper.emitted('update:filteredDownloadUrl')?.at(-1)?.[0]

    it('shows repairs up to today by default, since later ones are almost always mistyped dates', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ to_date: today() }))
      expect(lastDownload(wrapper)).toBeNull()
    })

    it('clears the item filters, including the powered selection', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-powered"]').setValue('false')
      await wrapper.find('[data-testid="device-search-brand"]').setValue('Acme')
      await wrapper.find('[data-testid="device-search-comments"]').setValue('fuse')
      await wrapper.find('[data-testid="device-search-group"]').setValue('Chiswick')
      await flushPromises()
      mockApi.device.search.mockClear()

      await wrapper.find('[data-testid="device-search-clear-items"]').trigger('click')
      await flushPromises()

      const params = mockApi.device.search.mock.calls.at(-1)[0]
      expect(params.brand).toBeUndefined()
      expect(params.comments).toBeUndefined()
      expect(params.powered).toBeUndefined()
      // The event filters are left alone.
      expect(params.group).toBe('Chiswick')
    })

    it('clears the event filters and puts the to date back to today', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-group"]').setValue('Chiswick')
      await wrapper.find('[data-testid="device-search-from-date"]').setValue('2026-01-01')
      await wrapper.find('[data-testid="device-search-to-date"]').setValue('2026-02-01')
      await flushPromises()
      mockApi.device.search.mockClear()

      await wrapper.find('[data-testid="device-search-clear-events"]').trigger('click')
      await flushPromises()

      const params = mockApi.device.search.mock.calls.at(-1)[0]
      expect(params.group).toBeUndefined()
      expect(params.from_date).toBeUndefined()
      expect(params.to_date).toBe(today())
      expect(lastDownload(wrapper)).toBeNull()
    })

    it('offers a download of just the filtered records, with the filters and the to date', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-powered"]').setValue('false')
      await wrapper.find('[data-testid="device-search-brand"]').setValue('Acme')
      await wrapper.find('[data-testid="device-search-status"]').setValue('Fixed')
      await flushPromises()

      const url = new URL(lastDownload(wrapper), 'http://x')
      expect(url.pathname).toBe('/export/devices')
      expect(url.searchParams.get('brand')).toBe('Acme')
      expect(url.searchParams.get('powered')).toBe('false')
      expect(url.searchParams.get('status')).toBe('Fixed')
      expect(url.searchParams.get('to_date')).toBe(today())
      expect(url.searchParams.has('page')).toBe(false)
      expect(url.searchParams.has('sortBy')).toBe(false)
    })

    it('does not offer a download for the default list, or for both kinds with only the to date', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      expect(lastDownload(wrapper)).toBeNull()
    })

    it('offers a download once the to date is changed', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-to-date"]').setValue('2020-01-01')
      await flushPromises()

      expect(new URL(lastDownload(wrapper), 'http://x').searchParams.get('to_date')).toBe('2020-01-01')
    })
  })
})
