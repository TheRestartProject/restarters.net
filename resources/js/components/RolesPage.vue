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
          <h1 class="mb-0">{{ __('admin.roles') }}</h1>
        </div>
      </div>

      <br>

      <div class="row">
        <div class="col-12">
          <div class="table-responsive table-section">
            <b-table
              :items="roles"
              :fields="fields"
              striped
              hover
              data-testid="roles-table"
            >
              <template #cell(name)="data">
                <a
                  href="#"
                  :data-testid="`roles-edit-link-${data.item.id}`"
                  @click.prevent="openEditModal(data.item)"
                >
                  {{ data.item.name }}
                </a>
              </template>
              <template #cell(permissions_list)="data">
                <span class="text-muted">{{ data.item.permissions_list }}</span>
              </template>
            </b-table>
          </div>
        </div>
      </div>

      <b-modal
        id="roles-edit-modal"
        v-model="showEdit"
        :title="editTitle"
        :ok-title="__('admin.save-role')"
        :cancel-title="__('partials.cancel')"
        :ok-disabled="saving"
        size="lg"
        @ok.prevent="saveRolePermissions"
        @hidden="resetEditState"
      >
        <p v-if="editError" class="text-danger">{{ editError }}</p>
        <div v-if="editingRole">
          <p>{{ __('admin.role_permissions_help') }}</p>
          <b-form-checkbox-group
            v-model="selectedPermissions"
            stacked
            :options="permissionOptions"
            data-testid="roles-edit-permissions"
          />
        </div>
      </b-modal>
    </div>
  </section>
</template>

<script>
export default {
  name: 'RolesPage',
  props: {
    initialRoles: {
      type: Array,
      default: () => []
    },
    initialPermissions: {
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
  data() {
    return {
      roles: [...this.initialRoles],
      permissions: [...this.initialPermissions],
      showEdit: false,
      editingRole: null,
      selectedPermissions: [],
      editError: null,
      saving: false,
      feedback: null
    }
  },
  computed: {
    fields() {
      return [
        { key: 'id', label: this.__('admin.role_id'), sortable: true, class: 'col-id' },
        { key: 'name', label: this.__('admin.role'), sortable: true },
        { key: 'permissions_list', label: this.__('admin.role_permissions'), sortable: false }
      ]
    },
    permissionOptions() {
      return this.permissions.map((p) => ({ value: p.id, text: p.name }))
    },
    editTitle() {
      if (!this.editingRole) return this.__('admin.edit-role')
      return this.__('admin.edit-role') + ': ' + this.editingRole.name
    }
  },
  methods: {
    openEditModal(role) {
      this.editingRole = role
      this.selectedPermissions = [...(role.permissions || [])]
      this.editError = null
      this.showEdit = true
    },
    resetEditState() {
      this.editingRole = null
      this.selectedPermissions = []
      this.editError = null
    },
    async saveRolePermissions() {
      if (!this.editingRole) return
      this.saving = true
      this.editError = null
      try {
        const { data } = await axios.put(
          `/api/v2/roles/${this.editingRole.id}/permissions?api_token=${this.apiToken}`,
          { permissions: this.selectedPermissions }
        )
        const updated = data.data
        const idx = this.roles.findIndex((r) => r.id === updated.id)
        if (idx !== -1) this.$set(this.roles, idx, updated)
        this.feedback = { variant: 'success', message: this.__('admin.role_update_success') }
        this.showEdit = false
      } catch (err) {
        const body = err && err.response ? err.response.data : null
        this.editError = (body && body.message)
          ? body.message
          : this.__('admin.role_update_error')
      } finally {
        this.saving = false
      }
    }
  },
  async mounted() {
    if (!this.initialPermissions || this.initialPermissions.length === 0) {
      try {
        const { data } = await axios.get(`/api/v2/permissions?api_token=${this.apiToken}`)
        this.permissions = data.data || []
      } catch (err) {
        console.error('Failed to load permissions', err)
      }
    }
    if (this.initialEditId) {
      const target = this.roles.find((r) => r.id === this.initialEditId)
      if (target) this.openEditModal(target)
    }
  }
}
</script>

<style scoped>
.col-id {
  width: 4rem;
}
</style>
