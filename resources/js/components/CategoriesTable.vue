<template>
  <div class="table-responsive table-section">
    <b-table
      :items="categories"
      :fields="fields"
      striped
      hover
      sort-icon-left
      class="table-categories"
    >
      <template #cell(name)="data">
        <a :href="`/category/edit/${data.item.idcategories}`">
          {{ __(data.item.name) }}
        </a>
      </template>

      <template #cell(cluster)="data">
        {{ data.item.cluster_name ? __(data.item.cluster_name) : 'N/A' }}
      </template>

      <template #cell(footprint_html)="data">
        <span v-html="data.value"></span>
      </template>

      <template #cell(reliability)="data">
        <span v-html="data.value"></span>
      </template>
    </b-table>
  </div>
</template>

<script>
export default {
  name: 'CategoriesTable',
  props: {
    categories: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      fields: [
        {
          key: 'name',
          label: 'Name',
          sortable: true
        },
        {
          key: 'cluster',
          label: 'Category Cluster',
          sortable: true
        },
        {
          key: 'weight',
          label: 'Weight [kg]',
          sortable: true
        },
        {
          key: 'footprint_html',
          label: 'CO₂ Footprint [kg]',
          sortable: true,
          sortByFormatted: true,
          formatter: (value, key, item) => item.footprint
        },
        {
          key: 'reliability',
          label: 'Reliability',
          sortable: false,
          tdAttr: { width: '145' }
        }
      ]
    }
  }
}
</script>
