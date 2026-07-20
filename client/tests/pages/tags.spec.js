import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TagsPage from '../../app/pages/tags.vue'
import { useAdminRefdataStore } from '../../app/stores/adminRefdata.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const AdminCrudTableStub = {
  props: ['items', 'displayKey', 'tableFields', 'formFields', 'labels', 'testidPrefix', 'allowCreate', 'allowDelete', 'editTwoColumn', 'editId', 'fetchItems', 'createItem', 'updateItem', 'deleteItem'],
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

  return mount(TagsPage, {
    global: {
      plugins: [i18n],
      stubs: { AdminCrudTable: AdminCrudTableStub },
    },
  })
}

describe('pages/tags', () => {
  let adminStore

  beforeEach(() => {
    setActivePinia(createPinia())
    adminStore = useAdminRefdataStore()
    vi.stubGlobal('useRoute', () => ({ query: {} }))
  })


  it('uses "name" as the display key and configures name/description form fields', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('displayKey')).toBe('name')
    expect(table.props('formFields').map((f) => f.key)).toEqual(['name', 'description'])
    expect(table.props('testidPrefix')).toBe('tags')
  })

  it('strips HTML and truncates the description column to 150 characters', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)
    const descriptionColumn = table.props('tableFields').find((f) => f.key === 'description')

    const long = '<p>' + 'a'.repeat(200) + '</p>'
    const result = descriptionColumn.formatter(long)

    expect(result).not.toContain('<p>')
    expect(result.endsWith('...')).toBe(true)
    expect(result.length).toBe(153) // 150 chars + '...'
  })

  it('shows an in-use warning naming the group count when deleting a tag applied to groups', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('labels').deleteWarning({ name: 'Scotland', groups_count: 3 })).toBe(
      'This tag is currently applied to 3 group(s) and will be removed from them too.',
    )
  })

  it('shows no warning for a tag applied to no groups', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('labels').deleteWarning({ name: 'Scotland', groups_count: 0 })).toBe('')
  })

  it('wires items and CRUD actions to the adminRefdata store', () => {
    adminStore.groupTags.data = [{ id: 1, name: 'Scotland', description: null, groups_count: 0 }]

    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('items')).toEqual([{ id: 1, name: 'Scotland', description: null, groups_count: 0 }])
    expect(table.props('fetchItems')).toBe(adminStore.fetchGroupTags)
    expect(table.props('createItem')).toBe(adminStore.createGroupTag)
    expect(table.props('updateItem')).toBe(adminStore.updateGroupTag)
    expect(table.props('deleteItem')).toBe(adminStore.deleteGroupTag)
  })

  // Gap 22: legacy's create-tag modal labels this "Description (optional):",
  // not the plain "Description:" every other description field uses.
  it('labels the description field "Description (optional)"', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('formFields').find((f) => f.key === 'description').label).toBe('Description (optional)')
  })

  // Gap 12: tags/edit.blade.php groups name in one Bootstrap column beside
  // description in another.
  it('opts into AdminCrudTable\'s two-column edit layout, with description in the right column', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('editTwoColumn')).toBe(true)
    expect(table.props('formFields').find((f) => f.key === 'description').column).toBe('right')
    expect(table.props('formFields').find((f) => f.key === 'name').column).toBeUndefined()
  })
})
