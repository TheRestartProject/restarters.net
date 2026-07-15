<template>
  <AdminCrudPage
    api-base="/api/v2/brands"
    :api-token="apiToken"
    :initial-items="initialBrands"
    :initial-edit-id="initialEditId"
    display-key="brand_name"
    :table-fields="tableFields"
    :form-fields="formFields"
    :labels="labels"
    testid-prefix="brands"
    :sort-items="sortByName"
  />
</template>

<script>
import AdminCrudPage from './AdminCrudPage.vue'

export default {
  name: 'BrandsPage',
  components: { AdminCrudPage },
  props: {
    initialBrands: {
      type: Array,
      default: () => []
    },
    apiToken: {
      type: String,
      required: true
    },
    initialEditId: {
      type: Number,
      default: null
    }
  },
  computed: {
    tableFields() {
      return [
        { key: 'brand_name', label: this.__('admin.brand-name'), sortable: true }
      ]
    },
    formFields() {
      return [
        { key: 'brand_name', label: this.__('admin.brand-name'), type: 'text', required: true, maxLength: 255 }
      ]
    },
    labels() {
      return {
        title: this.__('admin.brand'),
        createButton: this.__('admin.create-new-brand'),
        editTitle: this.__('admin.edit-brand'),
        saveButton: this.__('admin.save-brand'),
        deleteButton: this.__('partials.delete'),
        cancel: this.__('partials.cancel'),
        emptyText: this.__('admin.no-brands'),
        confirmDeleteTitle: this.__('partials.are_you_sure'),
        createSuccess: this.__('brands.create_success'),
        updateSuccess: this.__('brands.update_success'),
        deleteSuccess: this.__('brands.delete_success'),
        createError: this.__('brands.create_error'),
        updateError: this.__('brands.update_error'),
        deleteError: this.__('brands.delete_error'),
        formatConfirmDelete: (item) =>
          this.__('admin.confirm_delete_brand', { name: item.brand_name })
      }
    }
  },
  methods: {
    sortByName(a, b) {
      return a.brand_name.localeCompare(b.brand_name)
    }
  }
}
</script>
