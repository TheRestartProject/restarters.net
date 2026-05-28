<template>
  <section class="admin">
    <div class="container">
      <b-alert
        :show="!!feedback"
        :variant="feedback ? feedback.variant : 'success'"
        dismissible
        @dismissed="feedback = null"
      >
        {{ feedback ? feedback.message : '' }}
      </b-alert>

      <div class="row mb-30">
        <div class="col-12">
          <div class="d-flex align-items-center">
            <h1 class="mb-0 mr-30">{{ labels.title }}</h1>
            <b-button
              v-if="allowCreate"
              variant="primary"
              class="btn-save ml-auto"
              :data-testid="`${testidPrefix}-add-button`"
              @click="openCreateModal"
            >
              {{ labels.createButton }}
            </b-button>
          </div>
        </div>
      </div>

      <br>

      <div class="row">
        <div class="col-12">
          <div class="table-responsive table-section">
            <b-table
              :items="items"
              :fields="resolvedTableFields"
              striped
              hover
              sort-icon-left
              :empty-text="labels.emptyText"
              show-empty
              :data-testid="`${testidPrefix}-table`"
            >
              <template #cell(__primary__)="data">
                <a
                  href="#"
                  :data-testid="`${testidPrefix}-edit-link-${data.item[primaryKey]}`"
                  @click.prevent="openEditModal(data.item)"
                >
                  {{ data.item[displayKey] }}
                </a>
              </template>
              <template v-if="allowDelete" #cell(__actions__)="data">
                <b-button
                  size="sm"
                  variant="outline-danger"
                  :data-testid="`${testidPrefix}-delete-${data.item[primaryKey]}`"
                  @click="confirmDelete(data.item)"
                >
                  {{ labels.deleteButton }}
                </b-button>
              </template>
            </b-table>
          </div>
        </div>
      </div>

      <b-modal
        :id="`${testidPrefix}-create-modal`"
        v-model="showCreate"
        :title="labels.createButton"
        :ok-title="labels.createButton"
        :cancel-title="labels.cancel"
        :ok-disabled="!createValid || saving"
        @ok.prevent="createItem"
        @hidden="resetCreateForm"
      >
        <b-form @submit.prevent="createItem">
          <b-form-group
            v-for="field in formFields"
            :key="`create-${field.key}`"
            :label="field.label + ':'"
            :label-for="`${testidPrefix}-create-${field.key}`"
          >
            <b-form-input
              v-if="field.type !== 'textarea'"
              :id="`${testidPrefix}-create-${field.key}`"
              v-model="createForm[field.key]"
              :type="field.type || 'text'"
              :maxlength="field.maxLength"
              :data-testid="`${testidPrefix}-create-${field.key}`"
              :state="fieldErrors[field.key] ? false : null"
            />
            <b-form-textarea
              v-else
              :id="`${testidPrefix}-create-${field.key}`"
              v-model="createForm[field.key]"
              :rows="field.rows || 3"
              :maxlength="field.maxLength"
              :data-testid="`${testidPrefix}-create-${field.key}`"
              :state="fieldErrors[field.key] ? false : null"
            />
            <b-form-invalid-feedback :state="!fieldErrors[field.key]">
              {{ fieldErrors[field.key] }}
            </b-form-invalid-feedback>
          </b-form-group>
          <p v-if="createError && !hasFieldErrors" class="text-danger">{{ createError }}</p>
        </b-form>
      </b-modal>

      <b-modal
        :id="`${testidPrefix}-edit-modal`"
        v-model="showEdit"
        :title="labels.editTitle"
        :ok-title="labels.saveButton"
        :cancel-title="labels.cancel"
        :ok-disabled="!editValid || saving"
        @ok.prevent="saveItem"
        @hidden="resetEditForm"
      >
        <b-form @submit.prevent="saveItem">
          <b-form-group
            v-for="field in formFields"
            :key="`edit-${field.key}`"
            :label="field.label + ':'"
            :label-for="`${testidPrefix}-edit-${field.key}`"
          >
            <b-form-input
              v-if="field.type !== 'textarea'"
              :id="`${testidPrefix}-edit-${field.key}`"
              v-model="editForm[field.key]"
              :type="field.type || 'text'"
              :maxlength="field.maxLength"
              :data-testid="`${testidPrefix}-edit-${field.key}`"
              :state="fieldErrors[field.key] ? false : null"
            />
            <b-form-textarea
              v-else
              :id="`${testidPrefix}-edit-${field.key}`"
              v-model="editForm[field.key]"
              :rows="field.rows || 3"
              :maxlength="field.maxLength"
              :data-testid="`${testidPrefix}-edit-${field.key}`"
              :state="fieldErrors[field.key] ? false : null"
            />
            <b-form-invalid-feedback :state="!fieldErrors[field.key]">
              {{ fieldErrors[field.key] }}
            </b-form-invalid-feedback>
          </b-form-group>
          <p v-if="editError && !hasFieldErrors" class="text-danger">{{ editError }}</p>
        </b-form>
      </b-modal>

      <ConfirmModal
        v-if="allowDelete"
        ref="confirmDelete"
        :title="labels.confirmDeleteTitle"
        :message="confirmDeleteMessage"
        @confirm="deleteItem"
      />
    </div>
  </section>
</template>

<script>
import ConfirmModal from './ConfirmModal.vue'

export default {
  name: 'AdminCrudPage',
  components: { ConfirmModal },
  props: {
    apiBase: {
      type: String,
      required: true
    },
    apiToken: {
      type: String,
      required: true
    },
    initialItems: {
      type: Array,
      default: () => []
    },
    initialEditId: {
      type: Number,
      default: null
    },
    primaryKey: {
      type: String,
      default: 'id'
    },
    displayKey: {
      type: String,
      required: true
    },
    tableFields: {
      type: Array,
      required: true
    },
    formFields: {
      type: Array,
      required: true
    },
    labels: {
      type: Object,
      required: true
    },
    testidPrefix: {
      type: String,
      required: true
    },
    allowCreate: {
      type: Boolean,
      default: true
    },
    allowDelete: {
      type: Boolean,
      default: true
    },
    sortItems: {
      type: Function,
      default: null
    }
  },
  data() {
    return {
      items: this.sortIfNeeded([...this.initialItems]),
      showCreate: false,
      showEdit: false,
      createForm: this.blankForm(),
      editForm: this.blankForm(),
      editingItem: null,
      itemPendingDelete: null,
      createError: null,
      editError: null,
      fieldErrors: {},
      saving: false,
      feedback: null
    }
  },
  computed: {
    resolvedTableFields() {
      const fields = this.tableFields.map((f) => {
        if (f.key === this.displayKey) {
          return { ...f, key: '__primary__', label: f.label, sortable: f.sortable }
        }
        return f
      })
      if (this.allowDelete) {
        fields.push({
          key: '__actions__',
          label: '',
          class: 'text-right',
          tdClass: 'text-right'
        })
      }
      return fields
    },
    createValid() {
      return this.formFields.every((f) => !f.required || String(this.createForm[f.key] || '').trim().length > 0)
    },
    editValid() {
      return this.formFields.every((f) => !f.required || String(this.editForm[f.key] || '').trim().length > 0)
    },
    hasFieldErrors() {
      return Object.keys(this.fieldErrors).length > 0
    },
    confirmDeleteMessage() {
      if (!this.itemPendingDelete) return ''
      if (typeof this.labels.formatConfirmDelete === 'function') {
        return this.labels.formatConfirmDelete(this.itemPendingDelete)
      }
      return this.labels.confirmDelete || ''
    }
  },
  methods: {
    blankForm() {
      const out = {}
      ;(this.formFields || []).forEach((f) => { out[f.key] = '' })
      return out
    },
    sortIfNeeded(arr) {
      return this.sortItems ? [...arr].sort(this.sortItems) : arr
    },
    openCreateModal() {
      this.resetCreateForm()
      this.showCreate = true
    },
    resetCreateForm() {
      this.createForm = this.blankForm()
      this.createError = null
      this.fieldErrors = {}
    },
    openEditModal(item) {
      this.editingItem = item
      this.editForm = this.blankForm()
      this.formFields.forEach((f) => {
        this.editForm[f.key] = item[f.key] != null ? item[f.key] : ''
      })
      this.editError = null
      this.fieldErrors = {}
      this.showEdit = true
    },
    resetEditForm() {
      this.editingItem = null
      this.editForm = this.blankForm()
      this.editError = null
      this.fieldErrors = {}
    },
    confirmDelete(item) {
      this.itemPendingDelete = item
      this.$refs.confirmDelete.show()
    },
    trimForm(form) {
      const out = {}
      this.formFields.forEach((f) => {
        const raw = form[f.key]
        if (raw == null || raw === '') {
          out[f.key] = f.nullIfEmpty ? null : ''
        } else if (typeof raw === 'string') {
          out[f.key] = raw.trim()
          if (out[f.key] === '' && f.nullIfEmpty) out[f.key] = null
        } else {
          out[f.key] = raw
        }
      })
      return out
    },
    async createItem() {
      if (!this.createValid) return
      this.saving = true
      this.createError = null
      this.fieldErrors = {}
      try {
        const payload = this.trimForm(this.createForm)
        const { data } = await axios.post(
          `${this.apiBase}?api_token=${this.apiToken}`,
          payload
        )
        this.items = this.sortIfNeeded([...this.items, data.data])
        this.feedback = { variant: 'success', message: this.labels.createSuccess }
        this.showCreate = false
        this.$emit('created', data.data)
      } catch (err) {
        this.handleApiError(err, 'create')
      } finally {
        this.saving = false
      }
    },
    async saveItem() {
      if (!this.editingItem || !this.editValid) return
      this.saving = true
      this.editError = null
      this.fieldErrors = {}
      try {
        const payload = this.trimForm(this.editForm)
        const id = this.editingItem[this.primaryKey]
        const { data } = await axios.put(
          `${this.apiBase}/${id}?api_token=${this.apiToken}`,
          payload
        )
        const idx = this.items.findIndex((i) => i[this.primaryKey] === id)
        if (idx !== -1) this.$set(this.items, idx, data.data)
        this.items = this.sortIfNeeded(this.items)
        this.feedback = { variant: 'success', message: this.labels.updateSuccess }
        this.showEdit = false
        this.$emit('updated', data.data)
      } catch (err) {
        this.handleApiError(err, 'update')
      } finally {
        this.saving = false
      }
    },
    async deleteItem() {
      if (!this.itemPendingDelete) return
      const target = this.itemPendingDelete
      const id = target[this.primaryKey]
      try {
        await axios.delete(`${this.apiBase}/${id}?api_token=${this.apiToken}`)
        this.items = this.items.filter((i) => i[this.primaryKey] !== id)
        this.feedback = { variant: 'success', message: this.labels.deleteSuccess }
        this.$emit('deleted', target)
      } catch (err) {
        this.feedback = {
          variant: 'danger',
          message: this.extractErrorMessage(err) || this.labels.deleteError
        }
      } finally {
        this.itemPendingDelete = null
      }
    },
    handleApiError(err, mode) {
      const body = err && err.response ? err.response.data : null
      if (body && body.errors) {
        // Laravel-style validation errors keyed by field
        const errs = {}
        Object.entries(body.errors).forEach(([k, v]) => {
          errs[k] = Array.isArray(v) ? v[0] : String(v)
        })
        this.fieldErrors = errs
      }
      const message = this.extractErrorMessage(err) || (mode === 'create' ? this.labels.createError : this.labels.updateError)
      if (mode === 'create') this.createError = message
      else this.editError = message
    },
    extractErrorMessage(err) {
      if (err && err.response && err.response.data) {
        const body = err.response.data
        if (body.message && typeof body.message === 'string') return body.message
      }
      return null
    }
  },
  async mounted() {
    if (!this.initialItems || this.initialItems.length === 0) {
      try {
        const { data } = await axios.get(this.apiBase)
        this.items = this.sortIfNeeded(data.data || [])
      } catch (err) {
        console.error(`Failed to load ${this.testidPrefix}`, err)
      }
    }
    if (this.initialEditId) {
      const target = this.items.find((i) => i[this.primaryKey] === this.initialEditId)
      if (target) this.openEditModal(target)
    }
  }
}
</script>
