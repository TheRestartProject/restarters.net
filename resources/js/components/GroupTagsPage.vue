<template>
  <AdminCrudPage
    api-base="/api/v2/group-tags"
    :api-token="apiToken"
    :initial-items="initialTags"
    :initial-edit-id="initialEditId"
    display-key="name"
    :table-fields="tableFields"
    :form-fields="formFields"
    :labels="labels"
    testid-prefix="group-tags"
    :sort-items="sortByName"
  />
</template>

<script>
import AdminCrudPage from './AdminCrudPage.vue'

export default {
  name: 'GroupTagsPage',
  components: { AdminCrudPage },
  props: {
    initialTags: {
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
        { key: 'name', label: this.__('admin.tag-name'), sortable: true },
        {
          key: 'description',
          label: this.__('admin.description'),
          sortable: false,
          formatter: (value) => this.truncate(this.stripTags(value), 150)
        }
      ]
    },
    formFields() {
      return [
        { key: 'name', label: this.__('admin.tag-name'), type: 'text', required: true, maxLength: 255 },
        {
          key: 'description',
          label: this.__('admin.description'),
          type: 'textarea',
          required: false,
          rows: 6,
          maxLength: 1000,
          nullIfEmpty: true
        }
      ]
    },
    labels() {
      return {
        title: this.__('admin.group-tags'),
        createButton: this.__('admin.create-new-tag'),
        editTitle: this.__('admin.edit-tag'),
        saveButton: this.__('admin.save-tag'),
        deleteButton: this.__('admin.delete-tag'),
        cancel: this.__('partials.cancel'),
        emptyText: this.__('admin.no-group-tags'),
        confirmDeleteTitle: this.__('partials.are_you_sure'),
        createSuccess: this.__('group-tags.create_success'),
        updateSuccess: this.__('group-tags.update_success'),
        deleteSuccess: this.__('group-tags.delete_success'),
        createError: this.__('group-tags.create_error'),
        updateError: this.__('group-tags.update_error'),
        deleteError: this.__('group-tags.delete_error'),
        formatConfirmDelete: (item) =>
          this.__('admin.confirm_delete_group_tag', { name: item.name })
      }
    }
  },
  methods: {
    sortByName(a, b) {
      return a.name.localeCompare(b.name)
    },
    stripTags(value) {
      if (value == null) return ''
      return String(value).replace(/<\/?[^>]+>/g, '')
    },
    truncate(value, length) {
      if (value == null) return ''
      return value.length > length ? value.substring(0, length) + '...' : value
    }
  }
}
</script>
