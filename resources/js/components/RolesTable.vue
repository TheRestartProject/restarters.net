<template>
  <div class="table-responsive table-section">
    <b-table
      :items="roles"
      :fields="fields"
      striped
      hover
      sort-icon-left
      class="table-roles"
    >
      <template #cell(role)="data">
        <a :href="`/role/edit/${data.item.id}`" :title="'edit role permissions'">
          {{ data.item.role }}
        </a>
      </template>

      <template #cell(permissions_list)="data">
        {{ data.value }}
      </template>
    </b-table>
  </div>
</template>

<script>
export default {
  name: 'RolesTable',
  props: {
    roles: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      fields: [
        {
          key: 'id',
          label: 'ID',
          sortable: true
        },
        {
          key: 'role',
          label: 'Role',
          sortable: true
        },
        {
          key: 'permissions_list',
          label: 'Permissions',
          sortable: true
        }
      ]
    }
  }
}
</script>
