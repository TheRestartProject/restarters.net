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
            <h1 class="mb-0 mr-30">{{ __('admin.brand') }}</h1>
            <b-button
              variant="primary"
              class="btn-save ml-auto"
              data-testid="brands-add-button"
              @click="openCreateModal"
            >
              {{ __('admin.create-new-brand') }}
            </b-button>
          </div>
        </div>
      </div>

      <br>

      <div class="row">
        <div class="col-12">
          <div class="table-responsive table-section">
            <b-table
              :items="brands"
              :fields="fields"
              striped
              hover
              sort-icon-left
              :empty-text="__('admin.no-brands') || 'No brands yet.'"
              show-empty
              data-testid="brands-table"
            >
              <template #cell(brand_name)="data">
                <a
                  href="#"
                  :data-testid="`brand-edit-link-${data.item.id}`"
                  @click.prevent="openEditModal(data.item)"
                >
                  {{ data.item.brand_name }}
                </a>
              </template>
              <template #cell(actions)="data">
                <b-button
                  size="sm"
                  variant="outline-danger"
                  :data-testid="`brand-delete-${data.item.id}`"
                  @click="confirmDelete(data.item)"
                >
                  {{ __('partials.delete') }}
                </b-button>
              </template>
            </b-table>
          </div>
        </div>
      </div>

      <b-modal
        id="brand-create-modal"
        v-model="showCreate"
        :title="__('admin.create-new-brand')"
        :ok-title="__('admin.create-new-brand')"
        :cancel-title="__('partials.cancel')"
        :ok-disabled="!newName.trim() || saving"
        @ok.prevent="createBrand"
        @hidden="resetCreateForm"
      >
        <b-form-group
          :label="__('admin.brand-name') + ':'"
          label-for="brand-create-name"
        >
          <b-form-input
            id="brand-create-name"
            v-model="newName"
            data-testid="brand-create-name"
            :state="createError ? false : null"
            @keyup.enter="createBrand"
          />
          <b-form-invalid-feedback :state="!createError">{{ createError }}</b-form-invalid-feedback>
        </b-form-group>
      </b-modal>

      <b-modal
        id="brand-edit-modal"
        v-model="showEdit"
        :title="__('admin.edit-brand')"
        :ok-title="__('admin.save-brand')"
        :cancel-title="__('partials.cancel')"
        :ok-disabled="!editName.trim() || saving"
        @ok.prevent="saveBrand"
        @hidden="resetEditForm"
      >
        <b-form-group
          :label="__('admin.brand-name') + ':'"
          label-for="brand-edit-name"
        >
          <b-form-input
            id="brand-edit-name"
            v-model="editName"
            data-testid="brand-edit-name"
            :state="editError ? false : null"
            @keyup.enter="saveBrand"
          />
          <b-form-invalid-feedback :state="!editError">{{ editError }}</b-form-invalid-feedback>
        </b-form-group>
      </b-modal>

      <ConfirmModal
        ref="confirmDelete"
        :title="__('partials.are_you_sure')"
        :message="confirmDeleteMessage"
        @confirm="deleteBrand"
      />
    </div>
  </section>
</template>

<script>
import ConfirmModal from './ConfirmModal.vue'

export default {
  name: 'BrandsPage',
  components: { ConfirmModal },
  props: {
    initialBrands: {
      type: Array,
      default: () => []
    },
    apiToken: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      brands: [...this.initialBrands],
      showCreate: false,
      showEdit: false,
      newName: '',
      editName: '',
      editingBrand: null,
      brandPendingDelete: null,
      createError: null,
      editError: null,
      saving: false,
      feedback: null
    }
  },
  computed: {
    fields() {
      return [
        { key: 'brand_name', label: this.__('admin.brand-name'), sortable: true },
        { key: 'actions', label: '', class: 'text-right', tdClass: 'text-right' }
      ]
    },
    confirmDeleteMessage() {
      if (!this.brandPendingDelete) return ''
      return this.__('admin.confirm_delete_brand', { name: this.brandPendingDelete.brand_name })
        || `Delete ${this.brandPendingDelete.brand_name}?`
    },
    apiBase() {
      return `/api/v2/brands`
    }
  },
  methods: {
    openCreateModal() {
      this.resetCreateForm()
      this.showCreate = true
    },
    resetCreateForm() {
      this.newName = ''
      this.createError = null
    },
    openEditModal(brand) {
      this.editingBrand = brand
      this.editName = brand.brand_name
      this.editError = null
      this.showEdit = true
    },
    resetEditForm() {
      this.editingBrand = null
      this.editName = ''
      this.editError = null
    },
    confirmDelete(brand) {
      this.brandPendingDelete = brand
      this.$refs.confirmDelete.show()
    },
    async createBrand() {
      const name = this.newName.trim()
      if (!name) return
      this.saving = true
      this.createError = null
      try {
        const { data } = await axios.post(
          `${this.apiBase}?api_token=${this.apiToken}`,
          { brand_name: name }
        )
        this.brands.push(data.data)
        this.brands.sort((a, b) => a.brand_name.localeCompare(b.brand_name))
        this.feedback = { variant: 'success', message: this.__('brands.create_success') }
        this.showCreate = false
      } catch (err) {
        this.createError = this.extractError(err) || this.__('brands.create_error') || 'Could not create brand.'
      } finally {
        this.saving = false
      }
    },
    async saveBrand() {
      if (!this.editingBrand) return
      const name = this.editName.trim()
      if (!name) return
      this.saving = true
      this.editError = null
      try {
        const { data } = await axios.put(
          `${this.apiBase}/${this.editingBrand.id}?api_token=${this.apiToken}`,
          { brand_name: name }
        )
        const idx = this.brands.findIndex((b) => b.id === this.editingBrand.id)
        if (idx !== -1) this.$set(this.brands, idx, data.data)
        this.brands.sort((a, b) => a.brand_name.localeCompare(b.brand_name))
        this.feedback = { variant: 'success', message: this.__('brands.update_success') }
        this.showEdit = false
      } catch (err) {
        this.editError = this.extractError(err) || this.__('brands.update_error') || 'Could not save brand.'
      } finally {
        this.saving = false
      }
    },
    async deleteBrand() {
      if (!this.brandPendingDelete) return
      const target = this.brandPendingDelete
      try {
        await axios.delete(`${this.apiBase}/${target.id}?api_token=${this.apiToken}`)
        this.brands = this.brands.filter((b) => b.id !== target.id)
        this.feedback = { variant: 'success', message: this.__('brands.delete_success') }
      } catch (err) {
        this.feedback = {
          variant: 'danger',
          message: this.extractError(err) || this.__('brands.delete_error') || 'Could not delete brand.'
        }
      } finally {
        this.brandPendingDelete = null
      }
    },
    extractError(err) {
      if (err && err.response && err.response.data) {
        const body = err.response.data
        if (body.errors) {
          const first = Object.values(body.errors)[0]
          if (Array.isArray(first) && first.length) return first[0]
        }
        if (body.message) return body.message
      }
      return null
    }
  },
  async mounted() {
    if (!this.initialBrands || this.initialBrands.length === 0) {
      try {
        const { data } = await axios.get(this.apiBase)
        this.brands = data.data || []
      } catch (err) {
        console.error('Failed to load brands', err)
      }
    }
  }
}
</script>
