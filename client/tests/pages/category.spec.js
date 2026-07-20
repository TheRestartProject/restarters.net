import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import CategoryPage from '../../app/pages/category.vue'
import { useAdminRefdataStore } from '../../app/stores/adminRefdata.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const AdminCrudTableStub = {
  props: ['items', 'displayKey', 'tableFields', 'formFields', 'labels', 'testidPrefix', 'allowCreate', 'allowDelete', 'editTwoColumn', 'editId', 'fetchItems', 'createItem', 'updateItem', 'deleteItem'],
  template: '<div data-testid="stub-admin-crud-table" />',
}

// Bootstrap-vue-next primitives AdminCrudTable itself renders - only needed
// by the reliability-badge integration suite below, which mounts the real
// AdminCrudTable rather than AdminCrudTableStub (same stub set as
// tests/components/admin/AdminCrudTable.spec.js).
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot /></div>' }
const BFormSelectStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<select :value="modelValue" v-bind="$attrs" @change="$emit(\'update:modelValue\', Number($event.target.value))"><slot /></select>',
}
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-modal-title="title"><slot /></div>',
}
const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

// lang/en/admin.php's co2_footprint gained a real Unicode subscript in
// place of raw "<sub>2</sub>" markup (the table header/form label render it
// as plain text, so the old value showed the literal tags on screen), and
// not_applicable is new alongside this Nuxt work (gap 21), but
// client/i18n/locales/en.json is a generated, checked-in artifact this
// change intentionally leaves untouched (php artisan translations:export-client)
// - overlay the changed/new keys here so the spec doesn't depend on regenerating it.
const messages = {
  en: {
    ...en,
    ...clientEn,
    admin: { ...en.admin, co2_footprint: 'CO₂ Footprint (kg)', not_applicable: 'N/A' },
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

// Mounts with the real AdminCrudTable (only its own bootstrap-vue-next
// children stubbed) so the #cell-footprint_reliability slot this page
// supplies actually renders, for the reliability-badge suite below.
function mountPageWithRealTable() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(CategoryPage, {
    global: {
      plugins: [i18n],
      stubs: {
        BAlert: BAlertStub,
        BButton: BButtonStub,
        BForm: BFormStub,
        BFormGroup: BFormGroupStub,
        BFormSelect: BFormSelectStub,
        BModal: BModalStub,
        NuxtLink: NuxtLinkStub,
      },
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

  it('reads cluster_name directly off each row, falling back to N/A when empty (gap 21)', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)
    const clusterColumn = table.props('tableFields').find((f) => f.key === 'cluster_name')

    expect(clusterColumn).toBeDefined()
    expect(clusterColumn.formatter('Computers and Home Office')).toBe('Computers and Home Office')
    expect(clusterColumn.formatter(null)).toBe('N/A')
    expect(clusterColumn.formatter('')).toBe('N/A')
  })

  // Gap 20: CategoriesTable.vue hardcodes its rendered column header to the
  // literal English "Name", ignoring admin.category_name (which stays the
  // create/edit form field's label, unaffected by this gap).
  it('labels the name table column "Name", distinct from the form field\'s "Category name"', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('tableFields').find((f) => f.key === 'name').label).toBe('Name')
    expect(table.props('formFields').find((f) => f.key === 'name').label).toBe('Category name')
  })

  // Gap 19: legacy's CategoriesTable.vue explicitly sets sortable: false on
  // this column (it holds pre-rendered badge HTML, not a sortable value).
  it('marks the reliability column non-sortable', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('tableFields').find((f) => f.key === 'footprint_reliability').sortable).toBe(false)
  })

  // Gap 12: category/edit.blade.php groups Name/Weight/CO2/Reliability/
  // Cluster in one Bootstrap column beside Description in another.
  it('opts into AdminCrudTable\'s two-column edit layout, with description in the right column', () => {
    const wrapper = mountPage()
    const table = wrapper.findComponent(AdminCrudTableStub)

    expect(table.props('editTwoColumn')).toBe(true)
    expect(table.props('formFields').find((f) => f.key === 'description_short').column).toBe('right')
    expect(table.props('formFields').find((f) => f.key === 'name').column).toBeUndefined()
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

  describe('reliability badge', () => {
    // Legacy parity: CategoryController::index built
    // `<span class="badge indicator-N" style="background-color:#...">` per
    // footprint_reliability level - red for "Very poor" up to green for
    // "Very good". Mounts the real AdminCrudTable (via mountPageWithRealTable)
    // so the #cell-footprint_reliability slot this page supplies actually
    // renders, rather than just checking the stub's props.
    beforeEach(() => {
      // The real AdminCrudTable calls fetchItems (adminStore.fetchCategories)
      // on mount; the unmocked store action reaches for useNuxtApp()/$api,
      // which isn't available here and would leave the table in its
      // load-error state. Stub it out - it's irrelevant to badge rendering,
      // which reads categories.data (seeded per test below) directly.
      adminStore.fetchCategories = vi.fn().mockResolvedValue(undefined)
    })

    it.each([
      [1, '#AD2C1C', 'Very poor'],
      [2, '#FF1B00', 'Poor'],
      [3, '#FFBA00', 'Fair'],
      [4, '#43B136', 'Good'],
      [5, '#26781C', 'Very good'],
      [6, '#FFBA00', 'N/A'],
    ])('level %i renders a %s badge labelled %j', async (level, color) => {
      adminStore.categories.data = [{ id: 1, name: 'Laptop', cluster_name: null, footprint_reliability: level }]

      const wrapper = mountPageWithRealTable()
      await flushPromises()

      const badge = wrapper.find('[data-testid="category-row-1"] .badge')
      expect(badge.exists()).toBe(true)
      expect(badge.attributes('style')).toContain(`background-color: ${color}`)
      expect(badge.text()).toBe(en.admin[`reliability-${level}`])
    })

    it('falls back to the level-6 amber for a null reliability, matching the legacy default', async () => {
      adminStore.categories.data = [{ id: 1, name: 'Laptop', cluster_name: null, footprint_reliability: null }]

      const wrapper = mountPageWithRealTable()
      await flushPromises()

      const badge = wrapper.find('[data-testid="category-row-1"] .badge')
      expect(badge.attributes('style')).toContain('background-color: #FFBA00')
    })
  })
})
