<template>
  <AdminCrudPage
    api-base="/api/v2/categories"
    :api-token="apiToken"
    :initial-items="initialCategories"
    :initial-edit-id="initialEditId"
    display-key="name"
    :table-fields="tableFields"
    :form-fields="formFields"
    :labels="labels"
    testid-prefix="categories"
    :sort-items="sortByName"
    :allow-create="false"
    :allow-delete="false"
  />
</template>

<script>
import AdminCrudPage from './AdminCrudPage.vue'

export default {
  name: 'CategoriesPage',
  components: { AdminCrudPage },
  props: {
    initialCategories: {
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
    },
    clusters: {
      type: Array,
      required: true
    },
    reliabilityOptions: {
      type: Object,
      required: true
    }
  },
  computed: {
    clusterOptions() {
      return [
        { value: null, text: '—' },
        ...this.clusters.map((c) => ({ value: c.id, text: c.name }))
      ]
    },
    reliabilityOptionsList() {
      return Object.entries(this.reliabilityOptions).map(([value, text]) => ({
        value: Number(value),
        text
      }))
    },
    reliabilityLookup() {
      return this.reliabilityOptions
    },
    clusterLookup() {
      const out = {}
      this.clusters.forEach((c) => {
        out[c.id] = c.name
      })
      return out
    },
    tableFields() {
      return [
        { key: 'name', label: this.__('admin.category_name'), sortable: true },
        {
          key: 'cluster',
          label: this.__('admin.category_cluster'),
          sortable: true,
          formatter: (value) => (value != null ? (this.clusterLookup[value] || '') : '')
        },
        { key: 'weight', label: this.__('admin.weight'), sortable: true },
        { key: 'footprint', label: this.__('admin.co2_footprint'), sortable: true },
        {
          key: 'footprint_reliability',
          label: this.__('admin.reliability'),
          sortable: true,
          formatter: (value) => (value != null ? (this.reliabilityLookup[value] || '') : '')
        }
      ]
    },
    formFields() {
      return [
        { key: 'name', label: this.__('admin.category_name'), type: 'text', required: true, maxLength: 255 },
        { key: 'weight', label: this.__('admin.weight'), type: 'number', required: false },
        { key: 'footprint', label: this.__('admin.co2_footprint'), type: 'number', required: false },
        {
          key: 'footprint_reliability',
          label: this.__('admin.reliability'),
          type: 'select',
          required: false,
          options: this.reliabilityOptionsList
        },
        {
          key: 'cluster',
          label: this.__('admin.category_cluster'),
          type: 'select',
          required: false,
          options: this.clusterOptions
        },
        {
          key: 'description_short',
          label: this.__('admin.description'),
          type: 'textarea',
          required: false,
          rows: 4,
          nullIfEmpty: true
        }
      ]
    },
    labels() {
      return {
        title: this.__('admin.categories'),
        createButton: '',
        editTitle: this.__('admin.edit-category'),
        saveButton: this.__('admin.save-category'),
        deleteButton: '',
        cancel: this.__('partials.cancel'),
        emptyText: this.__('admin.no-categories'),
        confirmDeleteTitle: this.__('partials.are_you_sure'),
        createSuccess: '',
        updateSuccess: this.__('category.update_success'),
        deleteSuccess: '',
        createError: '',
        updateError: this.__('category.update_error'),
        deleteError: ''
      }
    }
  },
  methods: {
    sortByName(a, b) {
      return a.name.localeCompare(b.name)
    }
  }
}
</script>
