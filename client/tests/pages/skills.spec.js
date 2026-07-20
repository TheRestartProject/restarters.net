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

// admin.description_optional is new alongside this Nuxt work (gap 22) but
// client/i18n/locales/en.json is a generated, checked-in artifact this
// change intentionally leaves untouched (php artisan translations:export-client)
// - overlay it here so the spec doesn't depend on regenerating it. Same
// convention as tests/pages/category.spec.js's co2_footprint overlay.
const messages = {
  en: { ...en, ...clientEn, admin: { ...en.admin, description_optional: 'Description (optional)' } },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

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
    expect(descriptionField.required).toBe(false)
    expect(descriptionField.nullIfEmpty).toBe(true)
  })

  // Gap 5: develop's skills table has only two columns (Skill name,
  // Description) - category is editable on the create/edit form only, never
  // shown in the list.
  it('lists only skill_name and description columns, not category', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('tableFields').map((f) => f.key)).toEqual(['skill_name', 'description'])
  })

  // Gap 22: legacy's create-skill modal labels this "Description (optional):",
  // not the plain "Description:" every other description field uses.
  it('labels the description field "Description (optional)"', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('formFields').find((f) => f.key === 'description').label).toBe('Description (optional)')
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
