<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import { useDevicesStore } from '../../stores/devices.js'
import { deviceStatusKey, deviceStatusVariant } from '../../composables/useDeviceDisplay.js'

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
// b-collapse - default to expanded (the legacy default was collapsed
// unless the URL carried a matching query param) so the fields are usable
// immediately without an extra click.
const itemInfoExpanded = ref(true)
const eventInfoExpanded = ref(true)

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
    sortBy: 'event_start_utc',
    sortDesc: 'DESC',
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
    <div class="btn-group mb-3" role="group" data-testid="device-search-powered-toggle">
      <button
        type="button"
        class="btn btn-sm"
        :class="filters.powered ? 'btn-primary' : 'btn-outline-primary'"
        data-testid="device-search-powered-true"
        @click="filters.powered = true"
      >
        {{ poweredLabel }}
      </button>
      <button
        type="button"
        class="btn btn-sm"
        :class="!filters.powered ? 'btn-primary' : 'btn-outline-primary'"
        data-testid="device-search-powered-false"
        @click="filters.powered = false"
      >
        {{ unpoweredLabel }}
      </button>
    </div>

    <p
      class="text-brand small"
      data-testid="device-search-powered-description"
    >
      <!-- eslint-disable-next-line vue/no-v-html -->
      <span v-html="filters.powered ? t('devices.description_powered') : t('devices.description_unpowered')" />
    </p>

    <div class="device-search-section mb-3" data-testid="device-search-item-info">
      <button
        type="button"
        class="device-search-section__header"
        data-testid="device-search-item-info-toggle"
        @click="itemInfoExpanded = !itemInfoExpanded"
      >
        <span class="device-search-section__title">{{ t('devices.item_and_repair_info') }}</span>
        <img :src="itemInfoExpanded ? '/images/dropdown-arrow-up.svg' : '/images/dropdown-arrow.svg'" alt="">
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
        <img :src="eventInfoExpanded ? '/images/dropdown-arrow-up.svg' : '/images/dropdown-arrow.svg'" alt="">
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

      <div v-if="!devices.length" class="text-muted" data-testid="device-search-empty">
        {{ t('client.devices.no_results') }}
      </div>

      <div v-else class="table-responsive">
        <table class="table" data-testid="device-search-results">
          <thead>
            <tr>
              <th>{{ t('devices.model_or_type') }}</th>
              <th>{{ t('devices.category') }}</th>
              <th v-if="filters.powered">{{ t('devices.brand') }}</th>
              <th>{{ t('devices.assessment') }}</th>
              <th>{{ t('devices.group') }}</th>
              <th>{{ t('devices.status') }}</th>
              <th>{{ t('devices.devices_date') }}</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="device in devices" :key="device.id" :data-testid="`device-search-row-${device.id}`">
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
              <td>
                <NuxtLink :to="`/party/view/${device.eventid}`" :data-testid="`device-search-view-${device.id}`">
                  {{ t('client.events.view_event') }}
                </NuxtLink>
              </td>
            </tr>
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
</template>

<style scoped lang="scss">
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

  img {
    width: 24px;
  }
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
</style>

