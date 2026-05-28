<template>
  <AdminCrudPage
    api-base="/api/v2/skills"
    :api-token="apiToken"
    :initial-items="initialSkills"
    :initial-edit-id="initialEditId"
    display-key="skill_name"
    :table-fields="tableFields"
    :form-fields="formFields"
    :labels="labels"
    testid-prefix="skills"
    :sort-items="sortByName"
  />
</template>

<script>
import AdminCrudPage from './AdminCrudPage.vue'

export default {
  name: 'SkillsPage',
  components: { AdminCrudPage },
  props: {
    initialSkills: {
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
    skillCategories: {
      type: Object,
      required: true
    }
  },
  computed: {
    categoryOptions() {
      return Object.entries(this.skillCategories).map(([value, text]) => ({
        value: Number(value),
        text
      }))
    },
    categoryLookup() {
      return this.skillCategories
    },
    tableFields() {
      return [
        { key: 'skill_name', label: this.__('admin.skill-name'), sortable: true },
        {
          key: 'category',
          label: this.__('admin.category'),
          sortable: true,
          formatter: (value) => (value != null ? (this.categoryLookup[value] || value) : '')
        },
        { key: 'description', label: this.__('admin.description'), sortable: false }
      ]
    },
    formFields() {
      return [
        { key: 'skill_name', label: this.__('admin.skill-name'), type: 'text', required: true, maxLength: 255 },
        {
          key: 'category',
          label: this.__('admin.category'),
          type: 'select',
          required: true,
          options: this.categoryOptions
        },
        { key: 'description', label: this.__('admin.description'), type: 'textarea', required: false, maxLength: 255, rows: 4, nullIfEmpty: true }
      ]
    },
    labels() {
      return {
        title: this.__('admin.skills'),
        createButton: this.__('admin.create-new-skill'),
        editTitle: this.__('admin.edit-skill'),
        saveButton: this.__('admin.save-skill'),
        deleteButton: this.__('admin.delete-skill'),
        cancel: this.__('partials.cancel'),
        emptyText: this.__('admin.no-skills'),
        confirmDeleteTitle: this.__('partials.are_you_sure'),
        createSuccess: this.__('skills.create_success'),
        updateSuccess: this.__('skills.update_success'),
        deleteSuccess: this.__('skills.delete_success'),
        createError: this.__('skills.create_error'),
        updateError: this.__('skills.update_error'),
        deleteError: this.__('skills.delete_error'),
        formatConfirmDelete: (item) =>
          this.__('admin.confirm_delete_skill', { name: item.skill_name })
      }
    }
  },
  methods: {
    sortByName(a, b) {
      return a.skill_name.localeCompare(b.skill_name)
    }
  }
}
</script>
