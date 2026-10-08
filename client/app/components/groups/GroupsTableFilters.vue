<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import GroupMultiSelect from './GroupMultiSelect.vue'

// Filter bar for GroupsTable (resources/js/components/GroupsTableFilters.vue
// is the functional spec). Develop cut the bar back to what helps someone
// choose a group: name, and tags for the people who can see them. Place
// search is the map's own "Search for a place" box, country is a coarser
// version of the same question, and a network filter only means something to
// someone who already knows the networks (network pages scope their own
// lists), so none of those are here. Behind a show/hide toggle on mobile
// only; always visible at md+.
const props = defineProps({
  // Gates the Tag dropdown - only Administrators/NetworkCoordinators see it;
  // the page computes the same role check and passes it down.
  showTags: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()

const expanded = ref(false)

const filters = reactive({ name: '', tags: [] })

watch(
  filters,
  (value) => emit('update:filters', { ...value, tags: [...value.tags] }),
  { deep: true }
)

const toggleLabel = computed(() => (expanded.value ? t('groups.hide_filters') : t('groups.show_filters')))

// Tag options: GET /api/v2/groups/tags is already role-filtered
// server-side (Admin sees all tags, NetworkCoordinator sees their
// networks' tags, everyone else gets []) - same source GroupForm.vue's
// tag multiselect uses. Only fetched when the dropdown is actually shown.
const groupsStore = useGroupsStore()
const tagOptions = computed(() =>
  groupsStore.tags.data.map((tag) => ({ value: tag.id, text: tag.tag_name ?? tag.name })),
)

onMounted(() => {
  if (props.showTags) groupsStore.fetchTags().catch(() => {})
})
</script>

<template>
  <div class="groups-table-filters mb-3" data-testid="groups-table-filters">
    <!-- Legacy only hides the filters behind a show/hide toggle below md;
         at md+ they're always visible with no toggle at all (gap #13) - the
         toggle button is mobile-only, and the fields themselves stay in the
         DOM always so the "d-md-*" utility can force them visible at md+
         regardless of `expanded` (a plain v-if would remove them from the
         DOM below md, leaving nothing for the md+ breakpoint to un-hide). -->
    <button
      type="button"
      class="btn btn-outline-secondary btn-sm mb-2 d-md-none"
      data-testid="groups-table-filters-toggle"
      @click="expanded = !expanded"
    >
      {{ toggleLabel }}
    </button>

    <div class="row g-2 d-md-flex" :class="{ 'd-none': !expanded }" data-testid="groups-table-filters-fields">
      <div class="col-12 col-md">
        <input
          v-model="filters.name"
          type="search"
          class="form-control"
          :placeholder="t('groups.search_name_placeholder')"
          :aria-label="t('groups.search_name_placeholder')"
          data-testid="groups-table-filter-name"
        >
      </div>
      <div v-if="showTags" class="col-12 col-md">
        <GroupMultiSelect
          v-model="filters.tags"
          :options="tagOptions"
          testid="groups-table-filter-tags"
          :placeholder="t('groups.search_tags_placeholder')"
        />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
// Legacy's inputs/multiselects in this bar carry a thick 2px near-black
// border (resources/sass/_edit.scss's global ".form-control { border-width:
// 2px }" plus GroupsTable.vue's ".multiselect__tags { border: 2px solid
// #222 !important }") - Bootstrap 5's default form-control/form-select
// border is thin and light by comparison (parity-v2 gap: filter inputs).
.groups-table-filters {
  :deep(.form-control),
  :deep(.form-select) {
    border: 2px solid #222;
  }
}
</style>
