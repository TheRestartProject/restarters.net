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
  props: ['eventId', 'device', 'powered', 'readonly', 'deleteButton', 'cancelButton'],
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

  // Gap fix: DeviceCategorySelect.vue's v-b-popover.html.left tooltip
  // (devices.tooltip_category), same key/pattern DeviceForm.vue's category
  // field already ports via FieldInfoPopover.
  it('shows a category tooltip with the legacy tooltip_category copy', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    const popover = wrapper.findComponent({ name: 'FieldInfoPopover' })
    expect(popover.exists()).toBe(true)
    expect(popover.props('content')).toBe(en.devices.tooltip_category)
  })

  // Gap fix: DeviceBrand.vue's vue-typeahead-bootstrap suggestions, ported
  // as a <datalist> the same way DeviceForm.vue's own brand field is.
  it('suggests known brands via a datalist sourced from the brands store', async () => {
    mockApi.device.brands.mockResolvedValue({
      data: [
        { id: 1, brand_name: 'Bosch' },
        { id: 2, brand_name: 'Dyson' },
      ],
    })

    const wrapper = mountComponent()
    await flushPromises()

    const input = wrapper.find('[data-testid="device-search-brand"]')
    expect(input.attributes('list')).toBe('device-search-brand-list')
    const options = wrapper.find('#device-search-brand-list').findAll('option')
    expect(options.map((o) => o.attributes('value'))).toEqual(['Bosch', 'Dyson'])
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

  // Gap fix: a prior version also rendered a "N items found" count line
  // above the table - legacy's FixometerRecordsTable.vue has no such line
  // (just devices.table_intro's "Press the 'i' icons..." instructions).
  it('does not render a results-count line - only the "i" icon instructions', async () => {
    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.find('[data-testid="device-search-count"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="device-search-table-intro"]').exists()).toBe(true)
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

  // Gap fix (HIGH): the expanded row renders the real (read-only) DeviceForm
  // now, not an abbreviated <dl> - field-level assertions belong to
  // DeviceForm.spec.js, this just checks the row wires the right device
  // through in the right (readonly, no delete/cancel) mode.
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

    await wrapper.find('[data-testid="device-search-info-1"]').trigger('click')
    expect(wrapper.find('[data-testid="device-search-details-1"]').exists()).toBe(false)
  })

  // Gap fix (HIGH): legacy's b-pagination, a numbered page-jump control,
  // only rendered above one page - not a prev/next bar that stays visible
  // with a single page.
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

  // Gap fix (HIGH): legacy FixometerRecordsTable.vue gives every row exactly
  // ONE toggle - edit-pencil for admins, info for everyone else - opening
  // the row into the SAME DeviceForm, readonly for non-admins or editable
  // with an inline delete for admins. A prior version showed the read-only
  // info toggle PLUS separate always-visible Edit/Delete text links for
  // admins - three controls instead of one.
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

  // Gap fix: legacy's b-tabs is `ourtabs ourtabs-brand` (assets/css/
  // _tabs.scss's already-ported nav-tabs chrome, same pattern
  // devices/EventDevicesPanel.vue's desktop tab strip uses) - Bootstrap
  // nav/nav-tabs/nav-link markup, not a bespoke pill button-group.
  describe('desktop Powered/Unpowered tabs (ourtabs pattern)', () => {
    it('renders the toggle as nav-tabs, with the active tab carrying the nav-link active class', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      const poweredTab = wrapper.find('[data-testid="device-search-powered-true"]')
      const unpoweredTab = wrapper.find('[data-testid="device-search-powered-false"]')
      expect(poweredTab.classes()).toEqual(expect.arrayContaining(['nav-link', 'active']))
      expect(unpoweredTab.classes()).toContain('nav-link')
      expect(unpoweredTab.classes()).not.toContain('active')

      await unpoweredTab.trigger('click')
      expect(wrapper.find('[data-testid="device-search-powered-false"]').classes()).toContain('active')
      expect(wrapper.find('[data-testid="device-search-powered-true"]').classes()).not.toContain('active')
    })
  })

  // Gap fix: FixometerFilters.vue expands/collapses with an SVG icon image
  // (add-icon-brand.svg/minus-icon-brand.svg), not a text +/− glyph.
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

  // Gap fix (mobile parity, 05-fixometer mobile screenshot): legacy's
  // mobile Repair Records UI (FixometerPage.vue's `d-block d-md-none`
  // block) is a collapsed Powered/Unpowered accordion, not the desktop
  // tabs+description+populated-table shown unconditionally at every width -
  // a prior version never diverged for mobile at all.
  describe('mobile Powered/Unpowered accordion (parity with legacy mobile)', () => {
    it('renders the filter rail without a desktop-only class - legacy shows it at every width', () => {
      const wrapper = mountComponent()

      expect(wrapper.find('.device-search-layout__filters').classes()).not.toContain('d-none')
    })

    it('starts with both mobile sections collapsed and the shared results body hidden', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      const poweredToggle = wrapper.find('[data-testid="device-search-mobile-powered-toggle"]')
      const unpoweredToggle = wrapper.find('[data-testid="device-search-mobile-unpowered-toggle"]')
      expect(poweredToggle.attributes('aria-expanded')).toBe('false')
      expect(unpoweredToggle.attributes('aria-expanded')).toBe('false')
      expect(wrapper.find('[data-testid="device-search-results-body"]').classes()).toContain('d-none')
    })

    it('expanding the mobile Powered section reveals the results body (powered is already the default tab)', async () => {
      const wrapper = mountComponent()
      await flushPromises()

      await wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').attributes('aria-expanded')).toBe('true')
      expect(wrapper.find('[data-testid="device-search-results-body"]').classes()).not.toContain('d-none')
    })

    it('switching from Unpowered back to Powered re-runs the search for powered items', async () => {
      const wrapper = mountComponent()
      await flushPromises()
      await wrapper.find('[data-testid="device-search-mobile-unpowered-toggle"]').trigger('click')
      await flushPromises()
      mockApi.device.search.mockClear()

      await wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').attributes('aria-expanded')).toBe('true')
      expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ powered: true }))
    })

    it('expanding Unpowered switches the shared search to unpowered and collapses Powered', async () => {
      const wrapper = mountComponent()
      await flushPromises()
      await wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').trigger('click')
      await flushPromises()
      mockApi.device.search.mockClear()

      await wrapper.find('[data-testid="device-search-mobile-unpowered-toggle"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').attributes('aria-expanded')).toBe('false')
      expect(wrapper.find('[data-testid="device-search-mobile-unpowered-toggle"]').attributes('aria-expanded')).toBe('true')
      expect(mockApi.device.search).toHaveBeenCalledWith(expect.objectContaining({ powered: false }))
    })

    it('re-clicking an expanded mobile section collapses it and hides the results body again', async () => {
      const wrapper = mountComponent()
      await flushPromises()
      await wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').trigger('click')
      await flushPromises()

      await wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="device-search-mobile-powered-toggle"]').attributes('aria-expanded')).toBe('false')
      expect(wrapper.find('[data-testid="device-search-results-body"]').classes()).toContain('d-none')
    })
  })
})
