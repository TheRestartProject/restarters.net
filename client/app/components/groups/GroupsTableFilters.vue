<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useGroupsStore } from '~/stores/groups.js'
import { useNetworksStore } from '~/stores/networks.js'
import GroupMultiSelect from './GroupMultiSelect.vue'

// Filter bar for GroupsTable (port of the legacy resources/js/components/
// GroupsTableFilters.vue). Name/tag/location/country/network filters,
// behind a show/hide toggle on mobile only (legacy: always visible at
// md+ - parity-v2/groups-lists.md gap #13), emitting the current criteria
// so the table can filter its rows client-side. Field order matches
// legacy exactly: name, tag, location, country, network.
//
// Country/Network are native <select>s rather than legacy's vue-multiselect,
// matching the plain-control idiom already used for filters elsewhere in
// this client (EventFilters.vue's country <select>, DevicesSearchTable.vue's
// status/category <select>s) - both are single-select in legacy too
// (:multiple="false"). Tag is the one field legacy's multiselect allows
// multiple selections on (:multiple="true"), so it uses GroupMultiSelect.vue
// (chip/dropdown control, findings/parity-v2/group-forms.md #6) bound to an
// array instead.
const props = defineProps({
  // Full (unfiltered) row list, used only to derive the Country options -
  // same source legacy's GroupsTableFilters.vue computes them from
  // (resources/js/components/GroupsTableFilters.vue's countryOptions), so
  // the option list doesn't shrink to match whatever's currently filtered.
  groups: {
    type: Array,
    default: () => [],
  },
  // Gates the Tag dropdown - legacy only shows it to Administrators/
  // NetworkCoordinators (GroupController::indexVariations' $show_tags);
  // the page computes the same role check and passes it down.
  showTags: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()

const expanded = ref(false)

// Seeded from ?network= so a link can land here pre-filtered. develop's
// network page links to /group/network/{id} - a dedicated route this client
// doesn't have - and the absence of any query-param handling here is why that
// link was previously considered unreproducible, and the whole groups browser
// embedded into the network page instead. Expanded by default when a filter
// arrives this way, so the applied filter is visible rather than hidden behind
// the collapsed panel.
const route = useRoute()
const initialNetwork = route.query.network ? String(route.query.network) : ''

const filters = reactive({ name: '', tags: [], location: '', country: '', network: initialNetwork })

if (initialNetwork) {
  expanded.value = true
}

watch(
  filters,
  (value) => emit('update:filters', { ...value }),
  { deep: true }
)

const toggleLabel = computed(() => (expanded.value ? t('groups.hide_filters') : t('groups.show_filters')))

// Network options: GET /api/v2/networks has no auth/role gate (unlike the
// /networks pages themselves) - legacy's group.index controller likewise
// passes Network::all() to every user, not just Admin/NC
// (app/Http/Controllers/GroupController.php's indexVariations()).
const networksStore = useNetworksStore()
const networkOptions = computed(() => networksStore.list.data)

// Tag options: GET /api/v2/groups/tags is already role-filtered
// server-side (Admin sees all tags, NetworkCoordinator sees their
// networks' tags, everyone else gets []) - same source GroupForm.vue's
// tag multiselect uses. Only fetched when the dropdown is actually shown.
const groupsStore = useGroupsStore()
const tagOptions = computed(() =>
  groupsStore.tags.data.map((tag) => ({ value: tag.id, text: tag.tag_name ?? tag.name })),
)

onMounted(() => {
  networksStore.fetchList().catch(() => {})
  if (props.showTags) groupsStore.fetchTags().catch(() => {})
})

const countryOptions = computed(() => {
  const seen = new Set()
  const options = []

  for (const group of props.groups) {
    const country = group.location?.country

    if (country && !seen.has(country)) {
      seen.add(country)
      options.push(country)
    }
  }

  return options.sort((a, b) => a.localeCompare(b))
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
      <div class="col-12 col-md">
        <input
          v-model="filters.location"
          type="search"
          class="form-control"
          :placeholder="t('groups.search_location_placeholder')"
          :aria-label="t('groups.search_location_placeholder')"
          data-testid="groups-table-filter-location"
        >
      </div>
      <div class="col-12 col-md">
        <select v-model="filters.country" class="form-select" :aria-label="t('groups.search_country_placeholder')" data-testid="groups-table-filter-country">
          <option value="">{{ t('groups.search_country_placeholder') }}</option>
          <option v-for="country in countryOptions" :key="country" :value="country">{{ country }}</option>
        </select>
      </div>
      <div class="col-12 col-md">
        <select v-model="filters.network" class="form-select" :aria-label="t('networks.network')" data-testid="groups-table-filter-network">
          <option value="">{{ t('networks.network') }}</option>
          <option v-for="network in networkOptions" :key="network.id" :value="network.id">{{ network.name }}</option>
        </select>
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
