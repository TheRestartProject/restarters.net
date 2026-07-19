<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import { useDevicesStore } from '../../stores/devices.js'
import { deviceStatusKey, deviceStatusVariant } from '../../composables/useDeviceDisplay.js'
import FixometerSortHeader from './FixometerSortHeader.vue'

// Paginated/filterable device search table for /device/search
// (api-contracts-phase-c.md C6a; design.md §6.2 C6 task brief). The legacy
// /device/search route (DeviceController::search) is dead
// (routes/web.php:340 wires it, but per the contract doc this page is a
// fresh build against GET /api/v2/devices, not a markup port) - filter set
// and column set are ported from resources/js/components/
// FixometerFilters.vue + FixometerRecordsTable.vue (the embedded-in-
// /fixometer table this replaces as a standalone page), same query param
// names.
//
// One deliberate correction vs the legacy filter: FixometerFilters.vue's
// status <select> uses constants.js's FIXED/REPAIRABLE/END_OF_LIFE, which
// are the device *resource's* string enum values ('Fixed'/'Repairable'/
// 'End of life') - but ApiController::getDevices()/listDevicesv2() compare
// `status` against the `repair_status` SQL column, which stores
// Device::REPAIR_STATUS_FIXED/REPAIRABLE/ENDOFLIFE (ints 1/2/3). Passing
// the string into that int-typed filter is a pre-existing legacy bug (the
// comparison never matches); this component uses the correct int codes
// instead of reproducing it - see docs/nuxt-migration/api-gaps.md.
const STATUS_FIXED = 1
const STATUS_REPAIRABLE = 2
const STATUS_END_OF_LIFE = 3

const PAGE_SIZE = 20

// Optional POWERED (n)/UNPOWERED (n) counts for the toggle that isn't
// currently active (FixometerPage.vue's b-tabs titles - "POWERED (123)").
// The active toggle always shows the live search `count` below; the other
// one falls back to this prop when supplied (fixometer.vue passes
// impactData.total_powered/total_unpowered, the same aggregate GET
// /api/homepage_data figures FixometerGlobalImpact.vue's grid already
// uses) rather than firing a second search just to get a count. Omitted
// entirely (no parens) when not supplied, e.g. on the standalone
// /device/search page.
const props = defineProps({
  poweredCount: {
    type: Number,
    default: null,
  },
  unpoweredCount: {
    type: Number,
    default: null,
  },
})

const { t } = useI18n()
const devicesStore = useDevicesStore()

// FixometerFilters.vue's two collapsible sections (ITEM & REPAIR INFO /
// EVENT INFO), ported as a lightweight expand/collapse rather than
// b-collapse. Default to COLLAPSED to match the legacy default (the sections
// only started open when the URL carried a matching filter query param); a
// prior version defaulted to expanded, which was a visible parity divergence.
const itemInfoExpanded = ref(false)
const eventInfoExpanded = ref(false)

const filters = reactive({
  powered: true,
  category: null,
  brand: '',
  model: '',
  item_type: '',
  status: null,
  comments: '',
  group: '',
  from_date: '',
  to_date: '',
})

const page = ref(1)

// Server-side sort (GET /api/v2/devices sortBy/sortDesc). Default matches the
// legacy fixometer table: the repair-event date, newest first. Only the
// columns whitelisted in DeviceController::listDevicesv2 are accepted; the
// keys here are exactly those whitelist keys.
const sortBy = ref('event_start_utc')
const sortDesc = ref(true)

// Clicking a header sorts by that column ascending; clicking the active column
// again reverses the direction - the affordance the legacy table's helper text
// ("click a column head to sort... click again to reverse") describes.
function toggleSort(key) {
  if (sortBy.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortBy.value = key
    sortDesc.value = false
  }
}

// The legacy per-row 'i' info icon toggled an inline details panel; a Set lets
// several be open at once, as bootstrap-vue's row-details did.
const expanded = ref(new Set())
function toggleDetails(id) {
  const next = new Set(expanded.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  expanded.value = next
}

const clusters = computed(() => devicesStore.clusters)
// Only offer categories that match the selected powered/unpowered toggle -
// DeviceCategorySelect.vue's :powered prop does the same filtering
// server-props-side in the legacy app.
const filteredClusters = computed(() =>
  clusters.value
    .map((cluster) => ({
      ...cluster,
      categories: (cluster.categories || []).filter((c) => c.powered === filters.powered),
    }))
    .filter((cluster) => cluster.categories.length)
)

function buildParams() {
  return {
    page: page.value,
    size: PAGE_SIZE,
    sortBy: sortBy.value,
    sortDesc: sortDesc.value ? 'DESC' : 'ASC',
    powered: filters.powered,
    category: filters.category || undefined,
    brand: filters.powered ? filters.brand || undefined : undefined,
    model: filters.powered ? filters.model || undefined : undefined,
    item_type: !filters.powered ? filters.item_type || undefined : undefined,
    status: filters.status || undefined,
    comments: filters.comments || undefined,
    group: filters.group || undefined,
    from_date: filters.from_date || undefined,
    to_date: filters.to_date || undefined,
  }
}

function runSearch() {
  devicesStore.searchDevices(buildParams())
}

// A filter change always resets to page 1. If we're already on page 1,
// changing `page.value` wouldn't fire the page watcher below, so search
// directly in that case - otherwise let the page watcher (below) be the
// single place that triggers the fetch, to avoid firing twice.
watch(
  filters,
  () => {
    if (page.value !== 1) {
      page.value = 1
    } else {
      runSearch()
    }
  },
  { deep: true }
)

watch(page, () => runSearch())

// A sort change resets to page 1 (same single-fetch guard as the filter watch).
watch([sortBy, sortDesc], () => {
  if (page.value !== 1) {
    page.value = 1
  } else {
    runSearch()
  }
})

onMounted(() => {
  devicesStore.ensureMetaLoaded()
  runSearch()
})

const devices = computed(() => devicesStore.searchResults.data)
const count = computed(() => devicesStore.searchResults.count)
const loading = computed(() => devicesStore.searchResults.loading)
const error = computed(() => devicesStore.searchResults.error)
const totalPages = computed(() => Math.max(1, Math.ceil(count.value / PAGE_SIZE)))

function toggleLabel(label, n) {
  return n === null || n === undefined ? label : `${label} (${n.toLocaleString()})`
}

const poweredLabel = computed(() =>
  toggleLabel(t('devices.title_powered'), filters.powered ? count.value : props.poweredCount)
)
const unpoweredLabel = computed(() =>
  toggleLabel(t('devices.title_unpowered'), !filters.powered ? count.value : props.unpoweredCount)
)

function statusLabel(device) {
  const key = deviceStatusKey(device)
  return key ? t(key) : null
}

function formatDate(value) {
  return value ? moment(value).format('DD/MM/YYYY') : ''
}

function previousPage() {
  if (page.value > 1) page.value -= 1
}

function nextPage() {
  if (page.value < totalPages.value) page.value += 1
}
</script>

<template>
  <div data-testid="devices-search-table">
    <!-- Legacy FixometerPage.vue's two-column Repair Records card: the filter
         accordions form a narrow left rail, the Powered/Unpowered tabs + table
         a wide right column, both inside one teal-bordered container. Single
         column on narrow viewports. -->
    <div class="device-search-layout">
      <div class="device-search-layout__filters">
        <div class="device-search-section mb-3" data-testid="device-search-item-info">
      <button
        type="button"
        class="device-search-section__header"
        data-testid="device-search-item-info-toggle"
        @click="itemInfoExpanded = !itemInfoExpanded"
      >
        <span class="device-search-section__title">{{ t('devices.item_and_repair_info') }}</span>
        <span class="device-search-section__toggle" aria-hidden="true">{{ itemInfoExpanded ? '−' : '+' }}</span>
      </button>
      <fieldset v-show="itemInfoExpanded" class="device-search-section__body" data-testid="device-search-filters">
        <legend class="visually-hidden">{{ t('devices.item_and_repair_info') }}</legend>

        <div class="row g-3">
          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-category">{{ t('devices.category') }}</label>
            <select id="device-search-category" v-model.number="filters.category" class="form-select" data-testid="device-search-category">
              <option :value="null">{{ t('client.devices.any_category') }}</option>
              <optgroup v-for="cluster in filteredClusters" :key="cluster.id" :label="t(cluster.name)">
                <option v-for="c in cluster.categories" :key="c.idcategories" :value="c.idcategories">
                  {{ t(c.name) }}
                </option>
              </optgroup>
            </select>
          </div>

          <div v-if="filters.powered" class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-brand">{{ t('devices.brand') }}</label>
            <input id="device-search-brand" v-model="filters.brand" type="text" class="form-control" data-testid="device-search-brand">
          </div>

          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-model">
              {{ filters.powered ? t('devices.model') : t('devices.model_or_type') }}
            </label>
            <input
              v-if="filters.powered"
              id="device-search-model"
              v-model="filters.model"
              type="text"
              class="form-control"
              data-testid="device-search-model"
            >
            <input
              v-else
              id="device-search-model"
              v-model="filters.item_type"
              type="text"
              class="form-control"
              data-testid="device-search-item-type"
            >
          </div>

          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-status">{{ t('devices.status') }}</label>
            <select id="device-search-status" v-model.number="filters.status" class="form-select" data-testid="device-search-status">
              <option :value="null">{{ t('client.devices.any_status') }}</option>
              <option :value="STATUS_FIXED">{{ t('partials.fixed') }}</option>
              <option :value="STATUS_REPAIRABLE">{{ t('partials.repairable') }}</option>
              <option :value="STATUS_END_OF_LIFE">{{ t('partials.end') }}</option>
            </select>
          </div>

          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-comments">{{ t('devices.search_assessment_comments') }}</label>
            <input id="device-search-comments" v-model="filters.comments" type="text" class="form-control" data-testid="device-search-comments">
          </div>
        </div>
      </fieldset>
    </div>

    <div class="device-search-section mb-3" data-testid="device-search-event-info">
      <button
        type="button"
        class="device-search-section__header"
        data-testid="device-search-event-info-toggle"
        @click="eventInfoExpanded = !eventInfoExpanded"
      >
        <span class="device-search-section__title">{{ t('devices.event_info') }}</span>
        <span class="device-search-section__toggle" aria-hidden="true">{{ eventInfoExpanded ? '−' : '+' }}</span>
      </button>
      <fieldset v-show="eventInfoExpanded" class="device-search-section__body">
        <legend class="visually-hidden">{{ t('devices.event_info') }}</legend>

        <div class="row g-3">
          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-group">{{ t('devices.group') }}</label>
            <input id="device-search-group" v-model="filters.group" type="text" class="form-control" data-testid="device-search-group">
          </div>

          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-from-date">{{ t('devices.from_date') }}</label>
            <input id="device-search-from-date" v-model="filters.from_date" type="date" class="form-control" data-testid="device-search-from-date">
          </div>

          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-to-date">{{ t('devices.to_date') }}</label>
            <input id="device-search-to-date" v-model="filters.to_date" type="date" class="form-control" data-testid="device-search-to-date">
          </div>
        </div>
      </fieldset>
    </div>
      </div>

      <div class="device-search-layout__results">
        <div class="device-search-tabs mb-3" role="group" data-testid="device-search-powered-toggle">
          <button
            type="button"
            class="device-search-tabs__tab"
            :class="{ 'device-search-tabs__tab--active': filters.powered }"
            data-testid="device-search-powered-true"
            @click="filters.powered = true"
          >
            {{ poweredLabel }}
          </button>
          <button
            type="button"
            class="device-search-tabs__tab"
            :class="{ 'device-search-tabs__tab--active': !filters.powered }"
            data-testid="device-search-powered-false"
            @click="filters.powered = false"
          >
            {{ unpoweredLabel }}
          </button>
        </div>

        <p class="text-brand small" data-testid="device-search-powered-description">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="filters.powered ? t('devices.description_powered') : t('devices.description_unpowered')" />
        </p>

    <div v-if="loading" data-testid="device-search-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 8rem" />
      </div>
    </div>

    <div v-else-if="error" class="text-danger" data-testid="device-search-error">
      {{ t('client.devices.load_error') }}
    </div>

    <template v-else>
      <p data-testid="device-search-count">
        {{ t('client.devices.results_count', { count }, count) }}
      </p>

      <!-- Legacy devices.table_intro: how to use the 'i' info icons + sortable
           column heads. -->
      <p class="text-brand small" data-testid="device-search-table-intro">
        {{ t('devices.table_intro') }}
      </p>

      <!-- Always render the table scaffold (column headers) - even with no
           results - matching the legacy b-table (show-empty). The empty state
           is a row inside the table, not a replacement for it. A prior version
           hid the whole table when empty, so the columns disappeared.
           Sortable heads (item/category/brand/group/status/date) mirror the
           legacy FixometerRecordsTable; Assessment was not sortable there. -->
      <div class="table-responsive">
        <table class="table" data-testid="device-search-results">
          <thead>
            <tr>
              <th><FixometerSortHeader :label="t('devices.model_or_type')" sort-key="item_type" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th><FixometerSortHeader :label="t('devices.category')" sort-key="category" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th v-if="filters.powered"><FixometerSortHeader :label="t('devices.brand')" sort-key="brand" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th><FixometerSortHeader :label="t('devices.assessment')" /></th>
              <th><FixometerSortHeader :label="t('devices.group')" sort-key="groupname" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th><FixometerSortHeader :label="t('devices.status')" sort-key="repair_status" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th><FixometerSortHeader :label="t('devices.devices_date')" sort-key="created_at" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-if="!devices.length" data-testid="device-search-empty">
              <td :colspan="filters.powered ? 8 : 7" class="text-muted text-center">
                {{ t('client.devices.no_results') }}
              </td>
            </tr>
            <template v-for="device in devices" :key="device.id">
              <tr :data-testid="`device-search-row-${device.id}`">
                <td>{{ device.item_type || '-' }}</td>
                <td>{{ device.category ? t(device.category.name) : '-' }}</td>
                <td v-if="filters.powered">{{ device.brand || '-' }}</td>
                <td>{{ device.short_problem || '-' }}</td>
                <td>{{ device.groupname || '-' }}</td>
                <td>
                  <BBadge v-if="statusLabel(device)" :variant="deviceStatusVariant(device)" :data-testid="`device-search-status-${device.id}`">
                    {{ statusLabel(device) }}
                  </BBadge>
                </td>
                <td>{{ formatDate(device.created_at) }}</td>
                <td class="text-end">
                  <button
                    type="button"
                    class="device-search-info"
                    :class="{ 'device-search-info--on': expanded.has(device.id) }"
                    :aria-expanded="expanded.has(device.id)"
                    :data-testid="`device-search-info-${device.id}`"
                    @click="toggleDetails(device.id)"
                  >
                    <span aria-hidden="true">i</span>
                    <span class="visually-hidden">{{ t('devices.table_intro') }}</span>
                  </button>
                </td>
              </tr>
              <tr
                v-if="expanded.has(device.id)"
                class="device-search-details"
                :data-testid="`device-search-details-${device.id}`"
              >
                <td :colspan="filters.powered ? 8 : 7">
                  <dl class="device-search-details__grid">
                    <div>
                      <dt>{{ t('devices.model') }}</dt>
                      <dd>{{ device.model || '-' }}</dd>
                    </div>
                    <div>
                      <dt>{{ t('devices.age') }}</dt>
                      <dd>{{ device.age ?? '-' }}</dd>
                    </div>
                    <div v-if="device.spare_parts">
                      <dt>{{ t('devices.spare_parts') }}</dt>
                      <dd>{{ device.spare_parts }}</dd>
                    </div>
                    <div class="device-search-details__wide">
                      <dt>{{ t('devices.assessment') }}</dt>
                      <dd>{{ device.problem || '-' }}</dd>
                    </div>
                  </dl>
                  <NuxtLink :to="`/party/view/${device.eventid}`" :data-testid="`device-search-view-${device.id}`">
                    {{ t('client.events.view_event') }}
                  </NuxtLink>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div v-if="devices.length" class="d-flex justify-content-between align-items-center mt-3">
        <button
          type="button"
          class="btn btn-secondary"
          :disabled="page <= 1"
          data-testid="device-search-prev-page"
          @click="previousPage"
        >
          {{ t('client.devices.previous_page') }}
        </button>
        <span data-testid="device-search-page-indicator">
          {{ t('client.devices.page_of', { page, total: totalPages }) }}
        </span>
        <button
          type="button"
          class="btn btn-secondary"
          :disabled="page >= totalPages"
          data-testid="device-search-next-page"
          @click="nextPage"
        >
          {{ t('client.devices.next_page') }}
        </button>
      </div>
    </template>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
// Two-column Repair Records card (legacy FixometerPage.vue): a narrow filter
// rail beside the tabbed results, together in one teal-bordered container.
// Stacks to a single column below the lg breakpoint.
.device-search-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #0394a6;

  @media (min-width: 992px) {
    grid-template-columns: minmax(220px, 300px) 1fr;
    align-items: start;
  }
}

.device-search-layout__results {
  min-width: 0;
}

// In the two-column layout the filter rail is narrow, so its fields (a
// col-sm-6 col-md-4 grid tuned for a full-width row) stack one-per-row rather
// than cramming three across. Below lg the rail is full width and the original
// grid applies.
@media (min-width: 992px) {
  .device-search-layout__filters :deep(.col-sm-6),
  .device-search-layout__filters :deep(.col-md-4) {
    flex: 0 0 100%;
    max-width: 100%;
    width: 100%;
  }
}

// Powered/Unpowered toggle - legacy FixometerPage.vue's b-tabs: both tabs sit
// in a brand-teal bordered strip, white-backed, the active one picked out in
// teal (rather than the earlier btn-primary/btn-outline pair, which the theme
// rendered as a jarring solid-black inactive block).
.device-search-tabs {
  display: inline-flex;
  border: 1px solid #0394a6;
}

.device-search-tabs__tab {
  background: #fff;
  border: 0;
  border-right: 1px solid #0394a6;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #6c757d;

  &:last-child {
    border-right: 0;
  }

  &--active {
    color: #0394a6;
    box-shadow: inset 0 -3px 0 0 #0394a6;
  }
}

// FixometerFilters.vue's collapsible section chrome (border/shadow in the
// lighter brand teal, uppercase clickable header) - kept scoped, same
// reasoning as ImpactStats.vue's stat-card grid.
.device-search-section {
  border: 1px solid #4aaebc;
  box-shadow: 4px 4px 0 0 #4aaebc;
}

.device-search-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.5rem 0.75rem;
  background-color: #dce3ec;
  border: 0;
  text-align: left;
}

// Legacy used a "+"/"−" expand glyph (not a chevron) on these filter bars.
.device-search-section__toggle {
  font-size: 1.5rem;
  font-weight: bold;
  line-height: 1;
  color: #0394a6;
}

.device-search-section__title {
  text-transform: uppercase;
  font-weight: bold;
  font-size: 0.9rem;
}

.device-search-section__body {
  border: 0;
  padding: 0.75rem;
  margin: 0;
}

// Per-row 'i' info toggle - a small circular brand-teal badge (legacy used
// info_ico_green.svg); filled when its details row is open.
.device-search-info {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  padding: 0;
  border: 1px solid #0394a6;
  border-radius: 50%;
  background: #fff;
  color: #0394a6;
  font-style: italic;
  font-weight: bold;
  font-family: Georgia, 'Times New Roman', serif;
  line-height: 1;
  cursor: pointer;

  &--on {
    background: #0394a6;
    color: #fff;
  }
}

.device-search-details > td {
  background: #f5f7fa;
}

.device-search-details__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.25rem 1.5rem;
  margin: 0 0 0.5rem;

  dt {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6c757d;
    margin: 0;
  }

  dd {
    margin: 0 0 0.5rem;
  }
}

.device-search-details__wide {
  grid-column: 1 / -1;
}
</style>

