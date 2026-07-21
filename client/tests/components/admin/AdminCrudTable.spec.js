import { flushPromises, mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AdminCrudTable from '../../../app/components/admin/AdminCrudTable.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// The table announces its sortable columns, so it needs translations.
const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

// Generic component test (design.md §6.2 Phase D task D4 brief: "AdminCrudTable
// generic behaviour (one thorough suite)"). Exercised against a made-up
// "widgets" resource shape (text + select + textarea + number fields) rather
// than any one real resource - the five thin pages (brands/skills/tags/
// category) each get their own, much smaller, config-only spec instead of
// repeating all of this.
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
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

const GLOBAL_STUBS = {
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
  BFormSelect: BFormSelectStub,
  BModal: BModalStub,
}

const TABLE_FIELDS = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'kind', label: 'Kind', sortable: true, formatter: (value) => (value === 1 ? 'Alpha' : 'Beta') },
  { key: 'notes', label: 'Notes', sortable: false },
]

const FORM_FIELDS = [
  { key: 'name', label: 'Name', type: 'text', required: true, maxLength: 255 },
  { key: 'kind', label: 'Kind', type: 'select', required: true, options: [{ value: 1, text: 'Alpha' }, { value: 2, text: 'Beta' }] },
  { key: 'notes', label: 'Notes', type: 'textarea', required: false, nullIfEmpty: true },
  { key: 'weight', label: 'Weight', type: 'number', required: false, nullIfEmpty: true },
]

const LABELS = {
  title: 'Widgets',
  createButton: 'Create widget',
  editTitle: 'Edit widget',
  saveButton: 'Save widget',
  deleteButton: 'Delete',
  cancel: 'Cancel',
  emptyText: 'No widgets yet.',
  confirmDeleteTitle: 'Are you sure?',
  loadError: "Couldn't load widgets.",
  retry: 'Try again',
  createSuccess: 'Widget created!',
  updateSuccess: 'Widget updated!',
  deleteSuccess: 'Widget deleted!',
  createError: 'Could not create widget.',
  updateError: 'Could not save widget.',
  deleteError: 'Could not delete widget.',
  formatConfirmDelete: (item) => `Delete "${item.name}"?`,
  deleteWarning: (item) => (item.inUse ? `${item.name} is in use.` : ''),
}

const ROW = { id: 1, name: 'Widget One', kind: 1, notes: 'Some notes', weight: 2.5, inUse: false }
const ROW_IN_USE = { id: 2, name: 'Widget Two', kind: 2, notes: '', weight: null, inUse: true }

function mountTable(props = {}, slots = {}) {
  return mount(AdminCrudTable, {
    props: {
      items: [ROW, ROW_IN_USE],
      displayKey: 'name',
      tableFields: TABLE_FIELDS,
      formFields: FORM_FIELDS,
      labels: LABELS,
      testidPrefix: 'widgets',
      fetchItems: vi.fn().mockResolvedValue(undefined),
      createItem: vi.fn().mockResolvedValue({ id: 3, name: 'New', kind: 1, notes: null, weight: null }),
      updateItem: vi.fn().mockResolvedValue({ id: 1, name: 'Widget One (edited)', kind: 1, notes: 'Some notes', weight: 2.5 }),
      deleteItem: vi.fn().mockResolvedValue(undefined),
      ...props,
    },
    slots,
    global: { plugins: [i18n], stubs: GLOBAL_STUBS },
  })
}

describe('components/admin/AdminCrudTable', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('calls fetchItems on mount', () => {
    const fetchItems = vi.fn().mockResolvedValue(undefined)
    mountTable({ fetchItems })

    expect(fetchItems).toHaveBeenCalledTimes(1)
  })

  it('shows a loading state until fetchItems resolves', async () => {
    let resolveFetch
    const fetchItems = vi.fn().mockReturnValue(new Promise((resolve) => { resolveFetch = resolve }))

    const wrapper = mountTable({ fetchItems })
    expect(wrapper.find('[data-testid="widgets-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="widgets-table"]').exists()).toBe(false)

    resolveFetch()
    await flushPromises()

    expect(wrapper.find('[data-testid="widgets-loading"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="widgets-table"]').exists()).toBe(true)
  })

  it('shows an error state with a retry button that calls fetchItems again', async () => {
    const fetchItems = vi.fn().mockRejectedValue(new Error('boom'))
    const wrapper = mountTable({ fetchItems })
    await flushPromises()

    expect(wrapper.find('[data-testid="widgets-load-error"]').exists()).toBe(true)
    expect(wrapper.text()).toContain("Couldn't load widgets.")

    await wrapper.find('[data-testid="widgets-retry"]').trigger('click')
    await flushPromises()

    expect(fetchItems).toHaveBeenCalledTimes(2)
  })

  it('shows the empty-text row when items is empty', async () => {
    const wrapper = mountTable({ items: [] })
    await flushPromises()

    expect(wrapper.find('[data-testid="widgets-table-empty"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('No widgets yet.')
  })

  it('renders a row per item, applying column formatters', async () => {
    const wrapper = mountTable()
    await flushPromises()

    const row = wrapper.find('[data-testid="widgets-row-1"]')
    expect(row.exists()).toBe(true)
    expect(row.text()).toContain('Widget One')
    expect(row.text()).toContain('Alpha') // formatter(kind=1) -> 'Alpha'
    expect(row.text()).toContain('Some notes')
  })

  describe('custom cell rendering (#cell-<key> slot)', () => {
    // category.vue's coloured reliability badge is the motivating case
    // (design.md gap: reliability was plain text, not the legacy
    // colour-coded badge) - this covers the generic mechanism, backward-
    // compatibility is what the widgets-shape default-rendering tests above
    // already pin down.
    it('falls back to the plain formatted value when no matching slot is provided', async () => {
      const wrapper = mountTable()
      await flushPromises()

      const row = wrapper.find('[data-testid="widgets-row-1"]')
      expect(row.text()).toContain('Alpha') // TABLE_FIELDS' kind formatter, unaffected by the slot mechanism
    })

    it('renders a caller-supplied #cell-<key> slot instead, passed the item and the formatted value', async () => {
      const wrapper = mountTable({}, {
        'cell-kind': '<template #cell-kind="{ item, value }"><span data-testid="custom-kind">{{ value }}-#{{ item.id }}</span></template>',
      })
      await flushPromises()

      const row = wrapper.find('[data-testid="widgets-row-1"]')
      expect(row.find('[data-testid="custom-kind"]').text()).toBe('Alpha-#1')
    })

    it('leaves other columns on the same row rendering the default way when only one column has a slot', async () => {
      const wrapper = mountTable({}, {
        'cell-kind': '<template #cell-kind="{ value }"><span data-testid="custom-kind">{{ value }}</span></template>',
      })
      await flushPromises()

      const row = wrapper.find('[data-testid="widgets-row-1"]')
      expect(row.text()).toContain('Some notes') // notes column, no slot supplied for it
    })
  })

  it('hides the add button when allowCreate is false', async () => {
    const wrapper = mountTable({ allowCreate: false })
    await flushPromises()

    expect(wrapper.find('[data-testid="widgets-add-button"]').exists()).toBe(false)
  })

  it('hides delete buttons when allowDelete is false', async () => {
    const wrapper = mountTable({ allowDelete: false })
    await flushPromises()

    // Open the editor first - with delete moved in there, asserting absence
    // without opening it would pass whether or not allowDelete is honoured.
    await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')

    expect(wrapper.find('[data-testid="widgets-delete-1"]').exists()).toBe(false)
  })

  describe('sorting', () => {
    it('sorts ascending on first click of a sortable column', async () => {
      const wrapper = mountTable()
      await flushPromises()

      await wrapper.find('[data-testid="widgets-table-sort-name"]').trigger('click')

      const rows = wrapper.findAll('tbody tr')
      expect(rows[0].attributes('data-testid')).toBe('widgets-row-1') // 'Widget One' < 'Widget Two'
    })

    it('toggles to descending on a second click', async () => {
      const wrapper = mountTable()
      await flushPromises()

      await wrapper.find('[data-testid="widgets-table-sort-name"]').trigger('click')
      await wrapper.find('[data-testid="widgets-table-sort-name"]').trigger('click')

      const rows = wrapper.findAll('tbody tr')
      expect(rows[0].attributes('data-testid')).toBe('widgets-row-2')
    })

    it('has no sort control for a non-sortable column', async () => {
      const wrapper = mountTable()
      await flushPromises()

      expect(wrapper.find('[data-testid="widgets-table-sort-notes"]').exists()).toBe(false)
    })
  })

  describe('edit modal', () => {
    it('opens pre-filled when the display-key link is clicked', async () => {
      const wrapper = mountTable()
      await flushPromises()

      await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')

      const modal = wrapper.find('[data-testid="widgets-edit-modal"]')
      expect(modal.exists()).toBe(true)
      expect(wrapper.find('[data-testid="widgets-edit-name"]').element.value).toBe('Widget One')
    })

    it('submits the id and trimmed payload via updateItem, shows success feedback and closes', async () => {
      const updateItem = vi.fn().mockResolvedValue({ id: 1, name: 'Widget One (edited)', kind: 1, notes: 'Some notes', weight: 2.5 })
      const wrapper = mountTable({ updateItem })
      await flushPromises()

      await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')
      await wrapper.find('[data-testid="widgets-edit-name"]').setValue('  Widget One (edited)  ')
      await wrapper.find('[data-testid="widgets-edit-form"]').trigger('submit')
      await flushPromises()

      expect(updateItem).toHaveBeenCalledWith(1, { name: 'Widget One (edited)', kind: 1, notes: 'Some notes', weight: 2.5 })
      expect(wrapper.find('[data-testid="widgets-feedback"]').text()).toBe('Widget updated!')
      expect(wrapper.find('[data-testid="widgets-edit-modal"]').exists()).toBe(false)
    })

    it('renders a generic error message when updateItem rejects without field errors', async () => {
      const updateItem = vi.fn().mockRejectedValue({ status: 500, data: {} })
      const wrapper = mountTable({ updateItem })
      await flushPromises()

      await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')
      await wrapper.find('[data-testid="widgets-edit-form"]').trigger('submit')
      await flushPromises()

      expect(wrapper.find('[data-testid="widgets-edit-error"]').text()).toBe('Could not save widget.')
      expect(wrapper.find('[data-testid="widgets-edit-modal"]').exists()).toBe(true) // stays open on failure
    })

    it('surfaces a 422 duplicate-name error under the matching field, not as a generic message', async () => {
      const updateItem = vi.fn().mockRejectedValue({
        status: 422,
        data: { message: 'The given data was invalid.', errors: { name: ['The name has already been taken.'] } },
      })
      const wrapper = mountTable({ updateItem })
      await flushPromises()

      await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')
      await wrapper.find('[data-testid="widgets-edit-form"]').trigger('submit')
      await flushPromises()

      expect(wrapper.find('[data-testid="widgets-edit-name-error"]').text()).toBe('The name has already been taken.')
      // The generic <p> error is suppressed once a field-level error exists.
      expect(wrapper.find('[data-testid="widgets-edit-error"]').exists()).toBe(false)
    })
  })

  describe('create modal', () => {
    it('opens with a blank form on the add button', async () => {
      const wrapper = mountTable()
      await flushPromises()

      await wrapper.find('[data-testid="widgets-add-button"]').trigger('click')

      expect(wrapper.find('[data-testid="widgets-create-modal"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="widgets-create-name"]').element.value).toBe('')
    })

    it('disables submit while a required field is empty', async () => {
      const wrapper = mountTable()
      await flushPromises()
      await wrapper.find('[data-testid="widgets-add-button"]').trigger('click')

      expect(wrapper.find('[data-testid="widgets-create-submit"]').attributes('disabled')).toBeDefined()

      await wrapper.find('[data-testid="widgets-create-name"]').setValue('New widget')
      await wrapper.find('[data-testid="widgets-create-kind"]').setValue('1')

      expect(wrapper.find('[data-testid="widgets-create-submit"]').attributes('disabled')).toBeUndefined()
    })

    it('sends an empty optional field as null (nullIfEmpty), not an empty string', async () => {
      const createItem = vi.fn().mockResolvedValue({ id: 3, name: 'New widget', kind: 1, notes: null, weight: null })
      const wrapper = mountTable({ createItem })
      await flushPromises()

      await wrapper.find('[data-testid="widgets-add-button"]').trigger('click')
      await wrapper.find('[data-testid="widgets-create-name"]').setValue('New widget')
      await wrapper.find('[data-testid="widgets-create-kind"]').setValue('1')
      await wrapper.find('[data-testid="widgets-create-form"]').trigger('submit')
      await flushPromises()

      expect(createItem).toHaveBeenCalledWith({ name: 'New widget', kind: 1, notes: null, weight: null })
      expect(wrapper.find('[data-testid="widgets-feedback"]').text()).toBe('Widget created!')
    })
  })

  describe('delete flow', () => {
    it('shows the confirm message and an in-use warning when present', async () => {
      const wrapper = mountTable()
      await flushPromises()

      // Delete lives in the edit form now, matching develop's edit page.

      await wrapper.find('[data-testid="widgets-edit-link-2"]').trigger('click')

      await wrapper.find('[data-testid="widgets-delete-2"]').trigger('click')

      const modal = wrapper.find('[data-testid="widgets-delete-modal"]')
      expect(modal.text()).toContain('Delete "Widget Two"?')
      expect(wrapper.find('[data-testid="widgets-delete-warning"]').text()).toBe('Widget Two is in use.')
    })

    it('shows no warning for an item not in use', async () => {
      const wrapper = mountTable()
      await flushPromises()

      // Delete lives in the edit form now, matching develop's edit page.

      await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')

      await wrapper.find('[data-testid="widgets-delete-1"]').trigger('click')

      expect(wrapper.find('[data-testid="widgets-delete-warning"]').exists()).toBe(false)
    })

    it('does not call deleteItem when cancelled', async () => {
      const deleteItem = vi.fn().mockResolvedValue(undefined)
      const wrapper = mountTable({ deleteItem })
      await flushPromises()

      // Delete lives in the edit form now, matching develop's edit page.

      await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')

      await wrapper.find('[data-testid="widgets-delete-1"]').trigger('click')
      await wrapper.find('[data-testid="widgets-delete-cancel"]').trigger('click')

      expect(deleteItem).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="widgets-delete-modal"]').exists()).toBe(false)
    })

    it('calls deleteItem with the primary key and shows success feedback on confirm', async () => {
      const deleteItem = vi.fn().mockResolvedValue(undefined)
      const wrapper = mountTable({ deleteItem })
      await flushPromises()

      // Delete lives in the edit form now, matching develop's edit page.

      await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')

      await wrapper.find('[data-testid="widgets-delete-1"]').trigger('click')
      await wrapper.find('[data-testid="widgets-delete-confirm"]').trigger('click')
      await flushPromises()

      expect(deleteItem).toHaveBeenCalledWith(1)
      expect(wrapper.find('[data-testid="widgets-feedback"]').text()).toBe('Widget deleted!')
      expect(wrapper.find('[data-testid="widgets-delete-modal"]').exists()).toBe(false)
    })

    it('keeps the confirm modal open and shows an error on failure', async () => {
      const deleteItem = vi.fn().mockRejectedValue({ status: 500, data: {} })
      const wrapper = mountTable({ deleteItem })
      await flushPromises()

      // Delete lives in the edit form now, matching develop's edit page.

      await wrapper.find('[data-testid="widgets-edit-link-1"]').trigger('click')

      await wrapper.find('[data-testid="widgets-delete-1"]').trigger('click')
      await wrapper.find('[data-testid="widgets-delete-confirm"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="widgets-delete-modal"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="widgets-feedback"]').text()).toBe('Could not delete widget.')
    })
  })

  describe('editId deep link', () => {
    it('auto-opens the edit modal for the matching item once loaded', async () => {
      const wrapper = mountTable({ editId: 2 })
      await flushPromises()

      const modal = wrapper.find('[data-testid="widgets-edit-modal"]')
      expect(modal.exists()).toBe(true)
      expect(wrapper.find('[data-testid="widgets-edit-name"]').element.value).toBe('Widget Two')
    })

    it('does nothing when editId matches no item', async () => {
      const wrapper = mountTable({ editId: 999 })
      await flushPromises()

      expect(wrapper.find('[data-testid="widgets-edit-modal"]').exists()).toBe(false)
    })
  })
})
