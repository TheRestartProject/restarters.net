<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

// Filter bar for GroupsTable (port of the legacy resources/js/components/
// GroupsTableFilters.vue). Free-text filters for name / location / country /
// tags, behind a show/hide toggle, emitting the current criteria so the table
// can filter its rows client-side.
const emit = defineEmits(['update:filters'])

const { t } = useI18n()

const expanded = ref(false)
const filters = reactive({ name: '', location: '', country: '', tags: '' })

watch(
  filters,
  (value) => emit('update:filters', { ...value }),
  { deep: true }
)

const toggleLabel = computed(() => (expanded.value ? t('groups.hide_filters') : t('groups.show_filters')))
</script>

<template>
  <div class="groups-table-filters mb-3" data-testid="groups-table-filters">
    <button
      type="button"
      class="btn btn-outline-secondary btn-sm mb-2"
      data-testid="groups-table-filters-toggle"
      @click="expanded = !expanded"
    >
      {{ toggleLabel }}
    </button>

    <div v-if="expanded" class="row g-2" data-testid="groups-table-filters-fields">
      <div class="col-12 col-md-3">
        <input
          v-model="filters.name"
          type="search"
          class="form-control"
          :placeholder="t('groups.search_name_placeholder')"
          data-testid="groups-table-filter-name"
        >
      </div>
      <div class="col-12 col-md-3">
        <input
          v-model="filters.location"
          type="search"
          class="form-control"
          :placeholder="t('groups.search_location_placeholder')"
          data-testid="groups-table-filter-location"
        >
      </div>
      <div class="col-12 col-md-3">
        <input
          v-model="filters.country"
          type="search"
          class="form-control"
          :placeholder="t('groups.search_country_placeholder')"
          data-testid="groups-table-filter-country"
        >
      </div>
      <div class="col-12 col-md-3">
        <input
          v-model="filters.tags"
          type="search"
          class="form-control"
          :placeholder="t('groups.search_tags_placeholder')"
          data-testid="groups-table-filter-tags"
        >
      </div>
    </div>
  </div>
</template>
