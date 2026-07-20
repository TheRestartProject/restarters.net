<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import AdminCrudFormField from './AdminCrudFormField.vue'

// Generic reference-data CRUD table, built from PR #863's
// resources/js/components/AdminCrudPage.vue prop contract (design.md §6.2
// Phase D task D4) - five thin pages (brands/skills/tags/category) configure
// this one component instead of each re-implementing list+create+edit+
// delete. (The fifth reference-data page, /role, is read-mostly with a
// permissions-checkbox matrix rather than a text-field-per-row form, and is
// built bespoke on top of stores/adminRefdata.js instead - see
// pages/role.vue's own doc comment.)
//
// Unlike the legacy component (a server-rendered Blade page passing
// `initial-items`/`initial-edit-id` props from PHP), this is a pure SPA
// component: it always fetches on mount via `fetchItems`, and every
// mutation is delegated to caller-supplied async functions
// (createItem/updateItem/deleteItem) rather than a hardcoded `apiBase` +
// axios call - the caller's Pinia store (stores/adminRefdata.js) owns the
// actual API calls and keeps its list state sorted, this component only
// renders whatever `items` array it's handed and re-calls `fetchItems`/
// `createItem`/`updateItem`/`deleteItem` as the user interacts.
//
// `editId` reproduces the legacy "pre-open the edit modal" bookmark
// behaviour, but as a query param on the same page (`/brands?editId=5`)
// rather than legacy's separate path route (`/brands/edit/5`) - a
// deliberate routing simplification for the SPA, documented in
// docs/nuxt-migration/api-gaps.md Phase D rather than silently diverging.
const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  primaryKey: {
    type: String,
    default: 'id',
  },
  displayKey: {
    type: String,
    required: true,
  },
  tableFields: {
    type: Array,
    required: true,
  },
  formFields: {
    type: Array,
    required: true,
  },
  labels: {
    type: Object,
    required: true,
  },
  testidPrefix: {
    type: String,
    required: true,
  },
  allowCreate: {
    type: Boolean,
    default: true,
  },
  allowDelete: {
    type: Boolean,
    default: true,
  },
  editId: {
    type: Number,
    default: null,
  },
  fetchItems: {
    type: Function,
    required: true,
  },
  createItem: {
    type: Function,
    default: null,
  },
  updateItem: {
    type: Function,
    default: null,
  },
  deleteItem: {
    type: Function,
    default: null,
  },
})

const listLoading = ref(true)
const listError = ref(null)

const showCreate = ref(false)
const showEdit = ref(false)
const showDeleteConfirm = ref(false)

const createForm = reactive(blankForm())
const editForm = reactive(blankForm())
const editingItem = ref(null)
const itemPendingDelete = ref(null)

const saving = ref(false)
const deleting = ref(false)
const createError = ref('')
const editError = ref('')
const deleteError = ref('')
const createFieldErrors = ref({})
const editFieldErrors = ref({})

const feedback = ref('')
const feedbackVariant = ref('success')

const localSortKey = ref(null)
const localSortDesc = ref(false)

function blankForm() {
  const out = {}
  props.formFields.forEach((field) => {
    out[field.key] = ''
  })
  return out
}

const displayItems = computed(() => {
  if (!localSortKey.value) return props.items

  const key = localSortKey.value
  const sorted = [...props.items].sort((a, b) => {
    const av = a[key]
    const bv = b[key]
    if (av == null && bv == null) return 0
    if (av == null) return -1
    if (bv == null) return 1
    if (typeof av === 'number' && typeof bv === 'number') return av - bv
    return String(av).localeCompare(String(bv))
  })
  return localSortDesc.value ? sorted.reverse() : sorted
})

function sortIndicator(field) {
  if (localSortKey.value !== field.key) return ''
  return localSortDesc.value ? '▼' : '▲'
}

function sortByColumn(field) {
  if (!field.sortable) return

  if (localSortKey.value === field.key) {
    localSortDesc.value = !localSortDesc.value
  } else {
    localSortKey.value = field.key
    localSortDesc.value = false
  }
}

function cellValue(item, field) {
  const raw = item[field.key]
  return field.formatter ? field.formatter(raw, item) : raw
}

const createValid = computed(() => isValid(createForm))
const editValid = computed(() => isValid(editForm))

function isValid(form) {
  return props.formFields.every((field) => !field.required || String(form[field.key] ?? '').trim().length > 0)
}

function fieldError(errors, key) {
  return errors[key] || ''
}

function openCreateModal() {
  resetCreateForm()
  showCreate.value = true
}

function resetCreateForm() {
  Object.assign(createForm, blankForm())
  createError.value = ''
  createFieldErrors.value = {}
}

function openEditModal(item) {
  editingItem.value = item
  const next = blankForm()
  props.formFields.forEach((field) => {
    next[field.key] = item[field.key] != null ? item[field.key] : ''
  })
  Object.assign(editForm, next)
  editError.value = ''
  editFieldErrors.value = {}
  showEdit.value = true
}

function resetEditForm() {
  editingItem.value = null
  Object.assign(editForm, blankForm())
  editError.value = ''
  editFieldErrors.value = {}
}

function trimForm(form) {
  const out = {}
  props.formFields.forEach((field) => {
    const raw = form[field.key]
    if (raw === '' || raw === null || raw === undefined) {
      out[field.key] = field.nullIfEmpty ? null : ''
    } else if (typeof raw === 'string') {
      const trimmed = raw.trim()
      out[field.key] = trimmed === '' && field.nullIfEmpty ? null : trimmed
    } else {
      out[field.key] = raw
    }
  })
  return out
}

function applyApiError(err, fieldErrorsRef, fallbackMessage) {
  const errors = err?.data?.errors
  if (errors) {
    const mapped = {}
    Object.entries(errors).forEach(([key, value]) => {
      mapped[key] = Array.isArray(value) ? value[0] : String(value)
    })
    fieldErrorsRef.value = mapped
  }
  return err?.data?.message || fallbackMessage
}

async function submitCreate() {
  if (!createValid.value || !props.createItem) return

  saving.value = true
  createError.value = ''
  createFieldErrors.value = {}

  try {
    await props.createItem(trimForm(createForm))
    feedback.value = props.labels.createSuccess
    feedbackVariant.value = 'success'
    showCreate.value = false
  } catch (err) {
    createError.value = applyApiError(err, createFieldErrors, props.labels.createError)
  } finally {
    saving.value = false
  }
}

async function submitEdit() {
  if (!editingItem.value || !editValid.value || !props.updateItem) return

  saving.value = true
  editError.value = ''
  editFieldErrors.value = {}

  try {
    const id = editingItem.value[props.primaryKey]
    await props.updateItem(id, trimForm(editForm))
    feedback.value = props.labels.updateSuccess
    feedbackVariant.value = 'success'
    showEdit.value = false
  } catch (err) {
    editError.value = applyApiError(err, editFieldErrors, props.labels.updateError)
  } finally {
    saving.value = false
  }
}

function confirmDelete(item) {
  itemPendingDelete.value = item
  deleteError.value = ''
  showDeleteConfirm.value = true
}

function cancelDelete() {
  itemPendingDelete.value = null
  showDeleteConfirm.value = false
}

const confirmDeleteMessage = computed(() => {
  const item = itemPendingDelete.value
  if (!item) return ''
  if (typeof props.labels.formatConfirmDelete === 'function') {
    return props.labels.formatConfirmDelete(item)
  }
  return props.labels.confirmDelete || ''
})

const deleteWarningMessage = computed(() => {
  const item = itemPendingDelete.value
  if (!item || typeof props.labels.deleteWarning !== 'function') return ''
  return props.labels.deleteWarning(item) || ''
})

async function performDelete() {
  const item = itemPendingDelete.value
  if (!item || !props.deleteItem) return

  deleting.value = true

  try {
    await props.deleteItem(item[props.primaryKey])
    feedback.value = props.labels.deleteSuccess
    feedbackVariant.value = 'success'
    showDeleteConfirm.value = false
    itemPendingDelete.value = null
  } catch (err) {
    deleteError.value = err?.data?.message || props.labels.deleteError
    feedback.value = deleteError.value
    feedbackVariant.value = 'danger'
  } finally {
    deleting.value = false
  }
}

async function load() {
  listLoading.value = true
  listError.value = null

  try {
    await props.fetchItems()
    maybeOpenFromEditId()
  } catch (err) {
    listError.value = err
  } finally {
    listLoading.value = false
  }
}

function maybeOpenFromEditId() {
  if (props.editId == null) return
  const target = props.items.find((item) => item[props.primaryKey] === props.editId)
  if (target) openEditModal(target)
}

function retry() {
  load()
}

onMounted(load)
</script>

<template>
  <div :data-testid="`${testidPrefix}-admin`">
    <BAlert
      v-if="feedback"
      :model-value="true"
      :variant="feedbackVariant"
      dismissible
      :data-testid="`${testidPrefix}-feedback`"
      @dismissed="feedback = ''"
    >
      {{ feedback }}
    </BAlert>

    <div class="d-flex align-items-center mb-3">
      <h1 class="mb-0 me-auto">{{ labels.title }}</h1>
      <BButton
        v-if="allowCreate"
        variant="primary"
        :data-testid="`${testidPrefix}-add-button`"
        @click="openCreateModal"
      >
        {{ labels.createButton }}
      </BButton>
    </div>

    <div v-if="listLoading" :data-testid="`${testidPrefix}-loading`">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 6rem" />
      </div>
    </div>

    <BAlert v-else-if="listError" :model-value="true" variant="danger" :data-testid="`${testidPrefix}-load-error`">
      <p>{{ labels.loadError }}</p>
      <BButton variant="danger" :data-testid="`${testidPrefix}-retry`" @click="retry">
        {{ labels.retry }}
      </BButton>
    </BAlert>

    <!-- CategoriesTable.vue:2 / RolesTable.vue:2 / brands|skills|tags
         index.blade: `class="table-responsive table-section"`.
         `.table-section` is _tables.scss's white/20px-padding/1px-border/
         6px-shadow panel - already ported here, just never applied, so
         every admin table rendered flat on the page background. -->
    <div v-else class="table-responsive table-section">
      <table class="table table-striped table-hover" :data-testid="`${testidPrefix}-table`">
        <thead>
          <tr>
            <th v-for="field in tableFields" :key="field.key">
              <button
                v-if="field.sortable"
                type="button"
                class="sort-header"
                :data-testid="`${testidPrefix}-table-sort-${field.key}`"
                @click="sortByColumn(field)"
              >
                {{ field.label }} {{ sortIndicator(field) }}
              </button>
              <template v-else>{{ field.label }}</template>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!displayItems.length" :data-testid="`${testidPrefix}-table-empty`">
            <td :colspan="tableFields.length">{{ labels.emptyText }}</td>
          </tr>
          <tr v-for="item in displayItems" :key="item[primaryKey]" :data-testid="`${testidPrefix}-row-${item[primaryKey]}`">
            <td v-for="field in tableFields" :key="field.key">
              <a
                v-if="field.key === displayKey"
                href="#"
                :data-testid="`${testidPrefix}-edit-link-${item[primaryKey]}`"
                @click.prevent="openEditModal(item)"
              >
                {{ cellValue(item, field) }}
              </a>
              <!-- Optional per-column custom rendering: a caller can provide
                   #cell-<field.key>="{ item, value }" to render a column's
                   cell itself (e.g. category.vue's coloured reliability
                   badge). Falls back to the plain formatted value when no
                   such slot is filled, so every page that doesn't opt in
                   (brands/skills/tags/role) renders exactly as before. -->
              <slot v-else :name="`cell-${field.key}`" :item="item" :value="cellValue(item, field)">
                {{ cellValue(item, field) }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BModal
      :model-value="showCreate"
      :title="labels.createButton"
      no-footer
      :data-testid="`${testidPrefix}-create-modal`"
      @hide="showCreate = false; resetCreateForm()"
    >
      <BForm :data-testid="`${testidPrefix}-create-form`" @submit.prevent="submitCreate">
        <AdminCrudFormField
          v-for="field in formFields"
          :key="`create-${field.key}`"
          :field="field"
          :model-value="createForm[field.key]"
          :error="fieldError(createFieldErrors, field.key)"
          :testid="`${testidPrefix}-create-${field.key}`"
          @update:model-value="createForm[field.key] = $event"
        />
        <p v-if="createError && !Object.keys(createFieldErrors).length" class="text-danger" :data-testid="`${testidPrefix}-create-error`">
          {{ createError }}
        </p>
        <div class="d-flex justify-content-end gap-2 mt-3">
          <BButton variant="outline-secondary" @click="showCreate = false">{{ labels.cancel }}</BButton>
          <BButton type="submit" variant="primary" :disabled="!createValid || saving" :data-testid="`${testidPrefix}-create-submit`">
            {{ labels.createButton }}
          </BButton>
        </div>
      </BForm>
    </BModal>

    <BModal
      :model-value="showEdit"
      :title="labels.editTitle"
      no-footer
      :data-testid="`${testidPrefix}-edit-modal`"
      @hide="showEdit = false; resetEditForm()"
    >
      <BForm :data-testid="`${testidPrefix}-edit-form`" @submit.prevent="submitEdit">
        <AdminCrudFormField
          v-for="field in formFields"
          :key="`edit-${field.key}`"
          :field="field"
          :model-value="editForm[field.key]"
          :error="fieldError(editFieldErrors, field.key)"
          :testid="`${testidPrefix}-edit-${field.key}`"
          @update:model-value="editForm[field.key] = $event"
        />
        <p v-if="editError && !Object.keys(editFieldErrors).length" class="text-danger" :data-testid="`${testidPrefix}-edit-error`">
          {{ editError }}
        </p>
        <div class="d-flex justify-content-end gap-2 mt-3">
          <!-- develop puts Delete on the EDIT page (tags/edit.blade.php:55),
               not on every table row. Kept as a button with a confirm step
               rather than develop's `<a href="/tags/delete/{id}">`: that is a
               GET request that deletes, which is CSRF-able and can fire from a
               prefetch or a crawler. Copying the placement is parity; copying
               a destructive GET is importing a vulnerability, the same line
               already drawn over updateQuantity's IDOR. -->
          <BButton
            v-if="allowDelete && editingItem"
            variant="outline-danger"
            class="me-auto"
            :data-testid="`${testidPrefix}-delete-${editingItem[primaryKey]}`"
            @click="confirmDelete(editingItem)"
          >
            {{ labels.deleteButton }}
          </BButton>
          <BButton variant="outline-secondary" @click="showEdit = false">{{ labels.cancel }}</BButton>
          <BButton type="submit" variant="primary" :disabled="!editValid || saving" :data-testid="`${testidPrefix}-edit-submit`">
            {{ labels.saveButton }}
          </BButton>
        </div>
      </BForm>
    </BModal>

    <BModal
      v-if="allowDelete"
      :model-value="showDeleteConfirm"
      :title="labels.confirmDeleteTitle"
      no-footer
      :data-testid="`${testidPrefix}-delete-modal`"
      @hide="cancelDelete"
    >
      <p>{{ confirmDeleteMessage }}</p>
      <p v-if="deleteWarningMessage" class="text-warning" :data-testid="`${testidPrefix}-delete-warning`">
        {{ deleteWarningMessage }}
      </p>
      <div class="d-flex justify-content-end gap-2">
        <BButton variant="outline-secondary" :data-testid="`${testidPrefix}-delete-cancel`" @click="cancelDelete">
          {{ labels.cancel }}
        </BButton>
        <BButton variant="danger" :disabled="deleting" :data-testid="`${testidPrefix}-delete-confirm`" @click="performDelete">
          {{ labels.deleteButton }}
        </BButton>
      </div>
    </BModal>
  </div>
</template>

<style scoped lang="scss">
.sort-header {
  background: none;
  border: 0;
  padding: 0;
  font-weight: bold;
  cursor: pointer;
}
</style>
