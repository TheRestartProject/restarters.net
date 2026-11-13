import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import BrandsPage from '../../app/pages/brands.vue'
import { useAdminRefdataStore } from '../../app/stores/adminRefdata.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

// Config-only spec (design.md §6.2 Phase D task D4 brief: "thin per-page
// config tests") - AdminCrudTable.vue's own generic behaviour is covered by
// tests/components/admin/AdminCrudTable.spec.js; this only checks that
// pages/brands.vue wires the right props/store actions/i18n labels into it.
const AdminCrudTableStub = {
  props: ['items', 'displayKey', 'tableFields', 'formFields', 'labels', 'testidPrefix', 'allowCreate', 'allowDelete', 'editId', 'fetchItems', 'createItem', 'updateItem', 'deleteItem'],
  template: '<div data-testid="stub-admin-crud-table" />',
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(BrandsPage, {
    global: {
      plugins: [i18n],
      stubs: { AdminCrudTable: AdminCrudTableStub },
    },
  })
}

describe('pages/brands', () => {
  let adminStore

  beforeEach(() => {
    setActivePinia(createPinia())
    adminStore = useAdminRefdataStore()
    vi.stubGlobal('useRoute', () => ({ query: {} }))
  })


  it('passes brand_name as the display key and a single required text form field', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('displayKey')).toBe('brand_name')
    expect(table.props('formFields')).toEqual([{ key: 'brand_name', label: 'Brand name', type: 'text', required: true, maxLength: 255 }])
    expect(table.props('testidPrefix')).toBe('brands')
    // allowCreate/allowDelete aren't passed explicitly - like the live
    // BrandsPage.vue (07e6abd7cc^, this branch's pre-Phase-F baseline - not
    // develop's older Blade, which never got a delete UI), this page relies
    // on AdminCrudTable's own defaults (both true), covered by that
    // component's own generic suite.
  })

  it('wires items and the four CRUD actions to the adminRefdata store', () => {
    adminStore.brands.data = [{ id: 1, brand_name: 'Sony' }]

    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('items')).toEqual([{ id: 1, brand_name: 'Sony' }])
    expect(table.props('fetchItems')).toBe(adminStore.fetchBrands)
    expect(table.props('createItem')).toBe(adminStore.createBrand)
    expect(table.props('updateItem')).toBe(adminStore.updateBrand)
    expect(table.props('deleteItem')).toBe(adminStore.deleteBrand)
  })

  it('formats the duplicate-name confirm-delete message with the brand name', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('labels').formatConfirmDelete({ brand_name: 'Sony' })).toBe('Are you sure you want to delete the brand "Sony"?')
  })

  it('reads ?editId= from the route as a number', () => {
    vi.stubGlobal('useRoute', () => ({ query: { editId: '7' } }))

    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('editId')).toBe(7)
  })

  it('leaves editId null when absent from the query', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('editId')).toBeNull()
  })
})
