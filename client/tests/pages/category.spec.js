import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import CategoryPage from '../../app/pages/category.vue'
import { useAdminRefdataStore } from '../../app/stores/adminRefdata.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const AdminCrudTableStub = {
  props: ['items', 'displayKey', 'tableFields', 'formFields', 'labels', 'testidPrefix', 'allowCreate', 'allowDelete', 'editId', 'fetchItems', 'createItem', 'updateItem', 'deleteItem'],
  template: '<div data-testid="stub-admin-crud-table" />',
}

// lang/en/admin.php's co2_footprint gained a real Unicode subscript in
// place of raw "<sub>2</sub>" markup (the table header/form label render it
// as plain text, so the old value showed the literal tags on screen) but
// client/i18n/locales/en.json is a generated, checked-in artifact this
// change intentionally leaves untouched (php artisan translations:export-client)
// - overlay the changed key here so the spec doesn't depend on regenerating it.
const messages = {
  en: {
    ...en,
    ...clientEn,
    admin: { ...en.admin, co2_footprint: 'CO₂ Footprint (kg)' },
  },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(CategoryPage, {
    global: {
      plugins: [i18n],
      stubs: { AdminCrudTable: AdminCrudTableStub },
    },
  })
}

describe('pages/category', () => {
  let adminStore

  beforeEach(() => {
    setActivePinia(createPinia())
    adminStore = useAdminRefdataStore()
    adminStore.fetchClusters = vi.fn().mockResolvedValue([])
    vi.stubGlobal('useRoute', () => ({ query: {} }))
  })


  it('is list+update only: no create/delete affordances or wired actions', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('allowCreate')).toBe(false)
    expect(table.props('allowDelete')).toBe(false)
    expect(table.props('createItem')).toBeUndefined()
    expect(table.props('deleteItem')).toBeUndefined()
    expect(table.props('updateItem')).toBe(adminStore.updateCategory)
  })

  it('fetches the cluster list on mount (for the edit form dropdown)', () => {
    mountPage()
    expect(adminStore.fetchClusters).toHaveBeenCalledTimes(1)
  })

  it('reads cluster_name directly off each row rather than building a client-side lookup', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)
    const clusterColumn = table.props('tableFields').find((f) => f.key === 'cluster_name')

    expect(clusterColumn).toBeDefined()
    expect(clusterColumn.formatter).toBeUndefined()
  })

  it("the cluster select field's options update once fetchClusters resolves", async () => {
    let resolveClusters
    adminStore.fetchClusters = vi.fn().mockReturnValue(new Promise((resolve) => { resolveClusters = resolve }))

    const wrapper = mountPage()
    let table = wrapper.findComponent(AdminCrudTableStub)
    let clusterField = table.props('formFields').find((f) => f.key === 'cluster')
    expect(clusterField.options).toEqual([{ value: null, text: '—' }])

    resolveClusters()
    adminStore.clusters.data = [{ id: 1, name: 'Computers and Home Office' }]
    await flushPromises()
    await wrapper.vm.$nextTick()

    table = wrapper.findComponent(AdminCrudTableStub)
    clusterField = table.props('formFields').find((f) => f.key === 'cluster')
    expect(clusterField.options).toEqual([{ value: null, text: '—' }, { value: 1, text: 'Computers and Home Office' }])
  })

  it('builds the reliability select from the six admin.reliability-N translations', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)
    const reliabilityField = table.props('formFields').find((f) => f.key === 'footprint_reliability')

    expect(reliabilityField.options).toEqual([
      { value: 1, text: 'Very poor' },
      { value: 2, text: 'Poor' },
      { value: 3, text: 'Fair' },
      { value: 4, text: 'Good' },
      { value: 5, text: 'Very good' },
      { value: 6, text: 'N/A' },
    ])
  })

  it('renders the CO2 footprint label as plain text with a real subscript, not literal <sub> markup', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    const footprintColumn = table.props('tableFields').find((f) => f.key === 'footprint')
    const footprintField = table.props('formFields').find((f) => f.key === 'footprint')

    expect(footprintColumn.label).toBe('CO₂ Footprint (kg)')
    expect(footprintField.label).toBe('CO₂ Footprint (kg)')
    expect(footprintColumn.label).not.toContain('<sub>')
    expect(footprintField.label).not.toContain('<sub>')
  })

  it('wires items and fetchItems to the adminRefdata store', () => {
    adminStore.categories.data = [{ id: 1, name: 'Laptop', cluster_name: null }]

    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('items')).toEqual([{ id: 1, name: 'Laptop', cluster_name: null }])
    expect(table.props('fetchItems')).toBe(adminStore.fetchCategories)
    expect(table.props('testidPrefix')).toBe('category')
  })
})
