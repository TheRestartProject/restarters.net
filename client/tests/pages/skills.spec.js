import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import SkillsPage from '../../app/pages/skills.vue'
import { useAdminRefdataStore } from '../../app/stores/adminRefdata.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const AdminCrudTableStub = {
  props: ['items', 'displayKey', 'tableFields', 'formFields', 'labels', 'testidPrefix', 'allowCreate', 'allowDelete', 'editId', 'fetchItems', 'createItem', 'updateItem', 'deleteItem'],
  template: '<div data-testid="stub-admin-crud-table" />',
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(SkillsPage, {
    global: {
      plugins: [i18n],
      stubs: { AdminCrudTable: AdminCrudTableStub },
    },
  })
}

describe('pages/skills', () => {
  let adminStore

  beforeEach(() => {
    setActivePinia(createPinia())
    adminStore = useAdminRefdataStore()
    vi.stubGlobal('useRoute', () => ({ query: {} }))
  })


  it('configures skill_name/category/description form fields, category as a select of the two hardcoded categories', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    const formFields = table.props('formFields')
    expect(formFields.map((f) => f.key)).toEqual(['skill_name', 'category', 'description'])

    const categoryField = formFields.find((f) => f.key === 'category')
    expect(categoryField.type).toBe('select')
    expect(categoryField.required).toBe(true)
    expect(categoryField.options).toEqual([
      { value: 1, text: 'Organising skills - please select at least one if you’d like to host events' },
      { value: 2, text: 'Technical skills' },
    ])

    const descriptionField = formFields.find((f) => f.key === 'description')
    expect(descriptionField.label).toBe('Description')
    expect(descriptionField.required).toBe(false)
    expect(descriptionField.nullIfEmpty).toBe(true)
  })

  // live SkillsPage.vue (07e6abd7cc^) is the baseline (not develop's Blade):
  // 3 table columns (skill_name/category/description), category formatted
  // through the same hardcoded category labels as the form select.
  it("lists skill_name/category/description columns, category formatted through the same category labels", () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)
    const categoryColumn = table.props('tableFields').find((f) => f.key === 'category')

    expect(table.props('tableFields').map((f) => f.key)).toEqual(['skill_name', 'category', 'description'])
    expect(categoryColumn.formatter(2)).toBe('Technical skills')
    expect(categoryColumn.formatter(null)).toBe('')
  })

  it('wires items and CRUD actions to the adminRefdata store', () => {
    adminStore.skills.data = [{ id: 1, skill_name: 'First aid', category: 1, description: null }]

    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('items')).toEqual([{ id: 1, skill_name: 'First aid', category: 1, description: null }])
    expect(table.props('fetchItems')).toBe(adminStore.fetchSkills)
    expect(table.props('createItem')).toBe(adminStore.createSkill)
    expect(table.props('updateItem')).toBe(adminStore.updateSkill)
    expect(table.props('deleteItem')).toBe(adminStore.deleteSkill)
    expect(table.props('testidPrefix')).toBe('skills')
  })

  it('the delete-confirmation warns that removing a skill also removes it from volunteers', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('labels').formatConfirmDelete({ skill_name: 'Soldering' })).toContain('any volunteers who have selected it')
  })
})
