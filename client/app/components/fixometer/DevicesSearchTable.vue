<script setup>
import LoadingPlaceholder from '~/components/LoadingPlaceholder.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import { useDevicesStore } from '../../stores/devices.js'
import { useAuth } from '../../composables/useAuth.js'
import { deviceStatusKey, deviceStatusVariant } from '../../composables/useDeviceDisplay.js'
import FixometerSortHeader from './FixometerSortHeader.vue'
import { sortAriaValue } from '../../composables/useSortAria.js'
import DeviceForm from '../devices/DeviceForm.vue'
import DatePicker from 'vue-datepicker-next'
import 'vue-datepicker-next/index.css'

// The Fixometer's Repair records: one filterable, sortable, paged list of powered and unpowered items
// (GET /api/v2/devices), with the filters in a rail beside it. Also used standalone by /device/search.
//
// The filters mirror the page's query string: it is read on load and written back as they change, so a filtered
// list can be linked to. The same filters narrow the "Download filtered data" export, whose URL is emitted.

// The repair status names the API and the export both accept.
const STATUS_OPTIONS = [
  { value: 'Fixed', label: 'partials.fixed' },
  { value: 'Repairable', label: 'partials.repairable' },
  { value: 'End of life', label: 'partials.end_of_life' },
]

const PAGE_SIZE = 20

const emit = defineEmits(['update:filteredDownloadUrl', 'update:total'])

const { t } = useI18n()
const devicesStore = useDevicesStore()
const { hasRole } = useAuth()
const route = useRoute()
const router = useRouter()

// Every row has one toggle, an edit pencil for admins and an info icon for everyone else, which opens the row's
// device form: read-only for non-admins, editable with a delete for admins.
const isAdmin = computed(() => hasRole('Administrator'))

function today() {
  return moment().format('YYYY-MM-DD')
}

function queryValue(query, key) {
  const value = query[key]

  return Array.isArray(value) ? value[0] : value
}

// The filters and which sections start open (a non-default filter opens its section) from the query string.
function readQuery(query) {
  const filters = {
    // null lists both powered and unpowered items.
    powered: null,
    category: null,
    brand: '',
    model: '',
    item_type: '',
    status: null,
    comments: '',
    wiki: false,
    group: '',
    from_date: '',
    // Records from future events are almost always events entered with the wrong date, so by default we only show
    // repairs up to today.  The to date can still be changed to see them.
    to_date: today(),
  }
  let items = false
  let events = false
  const has = (key) => queryValue(query, key) !== undefined
  const get = (key) => queryValue(query, key)
  const categoryId = (key) => {
    const id = Number.parseInt(get(key))

    return Number.isNaN(id) ? null : id
  }

  if (has('powered')) {
    filters.powered = get('powered') === 'true'
    items = true
  }

  if (has('category')) {
    filters.category = categoryId('category')
    items = true
  }

  // Older links name the category separately for powered and unpowered items.
  if (has('category_powered')) {
    filters.powered = true
    filters.category = categoryId('category_powered')
    items = true
  }

  if (has('category_unpowered')) {
    filters.powered = false
    filters.category = categoryId('category_unpowered')
    items = true
  }

  for (const key of ['status', 'brand', 'model', 'item_type', 'comments']) {
    if (has(key)) {
      filters[key] = get(key)
      items = true
    }
  }

  if (has('wiki')) {
    filters.wiki = ['true', '1'].includes(get('wiki'))
    items = true
  }

  for (const key of ['group', 'from_date', 'to_date']) {
    if (has(key)) {
      filters[key] = get(key)
      events = true
    }
  }

  return { filters, items, events }
}

const initial = readQuery(route.query)
const filters = reactive(initial.filters)
const itemInfoExpanded = ref(initial.items)
const eventInfoExpanded = ref(initial.events)

const page = ref(1)

// Clearing the item filters also goes back to both powered and unpowered.
function clearItems() {
  Object.assign(filters, { powered: null, category: null, brand: '', model: '', item_type: '', status: null, comments: '' })
}

function clearEvents() {
  Object.assign(filters, { group: '', from_date: '', to_date: today() })
}

// Server-side sort (GET /api/v2/devices sortBy/sortDesc); the keys are the API's sort whitelist. With no column
// chosen the API lists the most recent events first.
const sortBy = ref(null)
const sortDesc = ref(true)

// Current sort state for the column headers - belongs on <th>, not the button.
const sortAria = (key) => sortAriaValue(key, sortBy.value, sortDesc.value)

// Clicking a header sorts by that column ascending; clicking the active column again reverses the direction.
function toggleSort(key) {
  if (sortBy.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortBy.value = key
    sortDesc.value = false
  }
}

// Several rows' details can be open at once.
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

// Search results aren't cached per event in the store, so a save or delete from a row's form closes the row and
// re-runs the current search.
function onRowClosed(id) {
  expanded.value = new Set([...expanded.value].filter((x) => x !== id))
  runSearch()
}

const clusters = computed(() => devicesStore.clusters)

// The brand filter is a typeahead like the device form's brand field: a plain input plus a styled suggestion panel.
const brands = computed(() => devicesStore.brands.data)
const brandSuggestionsOpen = ref(false)
const brandNames = computed(() => brands.value.map((b) => b.brand_name))
const brandMatches = computed(() => {
  const term = filters.brand.trim().toLowerCase()
  const list = term ? brandNames.value.filter((s) => s.toLowerCase().includes(term)) : brandNames.value
  return list.slice(0, 5)
})
function selectBrand(value) {
  filters.brand = value
  brandSuggestionsOpen.value = false
}
function onBrandFieldBlur(event) {
  // Losing focus to our own panel (an option's mousedown is prevented, so this only fires for a genuine focus move
  // elsewhere) closes it.
  if (event.currentTarget.contains(event.relatedTarget)) return
  brandSuggestionsOpen.value = false
}

// Only offer the categories of the selected kind of item; all of them for both.
const filteredClusters = computed(() =>
  clusters.value
    .map((cluster) => ({
      ...cluster,
      categories: (cluster.categories || []).filter((c) => filters.powered === null || c.powered === filters.powered),
    }))
    .filter((cluster) => cluster.categories.length)
)

// Categories belong to one kind of item, so changing powered starts again with the category.
function onPoweredChange() {
  filters.category = null
}

// The filters the list is narrowed by, without the paging and sorting.  The to date starts as today, so it only
// counts as a filter once it has been changed.
const filterParams = computed(() => {
  const params = {
    powered: filters.powered,
    category: filters.category,
    brand: filters.brand,
    model: filters.model,
    item_type: filters.item_type,
    comments: filters.comments,
    wiki: filters.wiki,
    status: filters.status,
    group: filters.group,
    from_date: filters.from_date,
    to_date: filters.to_date === today() ? null : filters.to_date,
  }
  const ret = {}

  for (const [key, value] of Object.entries(params)) {
    // Powered is a boolean where false means unpowered, so false is a real value for it only.
    if (value !== null && value !== undefined && value !== '' && (key === 'powered' || value !== false)) {
      ret[key] = value
    }
  }

  return ret
})

function buildParams() {
  const { to_date: _toDate, ...params } = filterParams.value

  return {
    page: page.value,
    size: PAGE_SIZE,
    sortBy: sortBy.value || undefined,
    sortDesc: sortBy.value && !sortDesc.value ? 'ASC' : 'DESC',
    ...params,
    to_date: filters.to_date || undefined,
  }
}

// The same export as the full download, narrowed to the records the list shows.  Null until a filter is set.  The
// to date is always sent, even when it is today, because the list does too, which keeps future-dated events out of
// the download just as they are out of the list.
const filteredDownloadUrl = computed(() => {
  if (!Object.keys(filterParams.value).length) {
    return null
  }

  const params = { ...filterParams.value }

  if (filters.to_date) {
    params.to_date = filters.to_date
  }

  return '/export/devices?' + new URLSearchParams(params).toString()
})

watch(filteredDownloadUrl, (url) => emit('update:filteredDownloadUrl', url), { immediate: true })

// The filters as the page's query string: the same params as the filtered download, in the same order as live.
function writeQuery() {
  const query = {}
  const params = filterParams.value

  for (const key of ['powered', 'category', 'status', 'brand', 'model', 'item_type', 'comments', 'wiki', 'group', 'from_date', 'to_date']) {
    if (params[key] !== undefined) {
      query[key] = String(params[key])
    }
  }

  try {
    router.replace({ query, hash: route.hash })
  } catch (error) {
    console.warn(error)
  }
}

function runSearch() {
  devicesStore.searchDevices(buildParams())
}

// A filter change always resets to page 1. If we're already on page 1, changing `page.value` wouldn't fire the page
// watcher below, so search directly in that case - otherwise let the page watcher be the single place that triggers
// the fetch, to avoid firing twice.
watch(
  filters,
  () => {
    writeQuery()

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

// The page shows the live total of the list, so report it whenever a search lands.
watch(loading, (isLoading) => {
  if (!isLoading && !error.value) {
    emit('update:total', count.value)
  }
})

function statusLabel(device) {
  const key = deviceStatusKey(device)
  return key ? t(key) : null
}

function formatDate(value) {
  return value ? moment(value).format('DD/MM/YYYY') : ''
}

function isPowered(device) {
  return Boolean(device.category && device.category.powered)
}
</script>

<template>
  <div data-testid="devices-search-table">
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
            <img
              class="device-search-section__toggle"
              :src="itemInfoExpanded ? '/images/minus-icon-brand.svg' : '/images/add-icon-brand.svg'"
              :alt="itemInfoExpanded ? 'Collapse' : 'Expand'"
            >
          </button>
          <fieldset v-show="itemInfoExpanded" class="device-search-section__body" data-testid="device-search-filters">
            <legend class="visually-hidden">{{ t('devices.item_and_repair_info') }}</legend>

            <div class="mb-3">
              <label class="form-label" for="device-search-powered">{{ t('devices.powered_or_unpowered') }}</label>
              <select
                id="device-search-powered"
                v-model="filters.powered"
                class="form-select"
                data-testid="device-search-powered"
                @change="onPoweredChange"
              >
                <option :value="null">{{ t('devices.both') }}</option>
                <option :value="true">{{ t('devices.powered') }}</option>
                <option :value="false">{{ t('devices.unpowered') }}</option>
              </select>
            </div>

            <div class="mb-3">
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

            <div class="mb-3">
              <label class="form-label" for="device-search-item-type">{{ t('devices.model_or_type') }}</label>
              <input
                id="device-search-item-type"
                v-model="filters.item_type"
                type="text"
                class="form-control"
                :placeholder="t('devices.item_type')"
                data-testid="device-search-item-type"
              >
            </div>

            <div class="mb-3">
              <label class="form-label" for="device-search-brand">{{ t('devices.brand') }}</label>
              <div class="position-relative typeahead-field">
                <input
                  id="device-search-brand"
                  v-model="filters.brand"
                  type="text"
                  class="form-control"
                  autocomplete="off"
                  data-testid="device-search-brand"
                  @focus="brandSuggestionsOpen = true"
                  @blur="onBrandFieldBlur"
                >
                <div
                  v-if="brandSuggestionsOpen && brandMatches.length"
                  class="multiselect__content-wrapper"
                  data-testid="device-search-brand-suggestions"
                >
                  <ul class="multiselect__content">
                    <li
                      v-for="b in brandMatches"
                      :key="b"
                      class="multiselect__option"
                      :data-testid="`device-search-brand-option-${b}`"
                      @mousedown.prevent="selectBrand(b)"
                    >
                      {{ b }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="device-search-model">{{ t('devices.model') }}</label>
              <input id="device-search-model" v-model="filters.model" type="text" class="form-control" data-testid="device-search-model">
            </div>

            <div class="mb-3">
              <label class="form-label" for="device-search-comments">{{ t('devices.search_assessment_comments') }}</label>
              <input id="device-search-comments" v-model="filters.comments" type="text" class="form-control" data-testid="device-search-comments">
            </div>

            <div class="mb-3">
              <label class="form-label" for="device-search-status">{{ t('devices.repair_status') }}</label>
              <select id="device-search-status" v-model="filters.status" class="form-select" data-testid="device-search-status">
                <option :value="null">{{ t('client.devices.any_status') }}</option>
                <option v-for="o in STATUS_OPTIONS" :key="o.value" :value="o.value">{{ t(o.label) }}</option>
              </select>
            </div>

            <div class="d-flex justify-content-end mt-1">
              <button type="button" class="btn clear-filters" data-testid="device-search-clear-items" @click="clearItems">
                {{ t('devices.clear_filters') }}
              </button>
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
            <img
              class="device-search-section__toggle"
              :src="eventInfoExpanded ? '/images/minus-icon-brand.svg' : '/images/add-icon-brand.svg'"
              :alt="eventInfoExpanded ? 'Collapse' : 'Expand'"
            >
          </button>
          <fieldset v-show="eventInfoExpanded" class="device-search-section__body">
            <legend class="visually-hidden">{{ t('devices.event_info') }}</legend>

            <div class="mb-3">
              <label class="form-label" for="device-search-group">{{ t('devices.group') }}</label>
              <input id="device-search-group" v-model="filters.group" type="text" class="form-control" data-testid="device-search-group">
            </div>

            <!-- vue-datepicker-next's input takes no data-testid, so the native date input shown on narrow
                 viewports (as in EventForm.vue) carries the stable test hook. -->
            <div class="mb-3 device-search-datepicker">
              <label class="form-label" for="device-search-from-date">{{ t('devices.from_date') }}</label>
              <DatePicker
                v-model:value="filters.from_date"
                type="date"
                value-type="YYYY-MM-DD"
                format="DD/MM/YYYY"
                input-class="form-control d-none d-md-block"
              />
              <input
                id="device-search-from-date"
                v-model="filters.from_date"
                type="date"
                class="form-control d-block d-md-none"
                data-testid="device-search-from-date"
              >
            </div>

            <div class="mb-3 device-search-datepicker">
              <label class="form-label" for="device-search-to-date">{{ t('devices.to_date') }}</label>
              <DatePicker
                v-model:value="filters.to_date"
                type="date"
                value-type="YYYY-MM-DD"
                format="DD/MM/YYYY"
                input-class="form-control d-none d-md-block"
              />
              <input
                id="device-search-to-date"
                v-model="filters.to_date"
                type="date"
                class="form-control d-block d-md-none"
                data-testid="device-search-to-date"
              >
            </div>

            <div class="d-flex justify-content-end mt-1">
              <button type="button" class="btn clear-filters" data-testid="device-search-clear-events" @click="clearEvents">
                {{ t('devices.clear_filters') }}
              </button>
            </div>
          </fieldset>
        </div>
      </div>

      <div class="device-search-layout__results records bg-white" data-testid="device-search-results-body">
        <div class="px-md-3">
          <div v-if="loading" data-testid="device-search-loading">
            <LoadingPlaceholder height="8rem" />
          </div>

          <div v-else-if="error" class="text-danger" data-testid="device-search-error">
            {{ t('client.devices.load_error') }}
          </div>

          <template v-else>
            <!-- The table scaffold (column headers) always renders, even with no results; the empty state is a
                 row inside it. -->
            <div class="table-responsive">
              <table class="table device-search-table" data-testid="device-search-results">
                <thead>
                  <tr>
                    <th :aria-sort="sortAria('item_type')"><FixometerSortHeader :label="t('devices.model_or_type')" sort-key="item_type" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
                    <th :aria-sort="sortAria('category')"><FixometerSortHeader :label="t('devices.category')" sort-key="category" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
                    <th class="d-none d-md-table-cell" :aria-sort="sortAria('brand')"><FixometerSortHeader :label="t('devices.brand')" sort-key="brand" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
                    <th class="d-none d-md-table-cell"><FixometerSortHeader :label="t('devices.assessment')" /></th>
                    <th :aria-sort="sortAria('repair_status')"><FixometerSortHeader :label="t('devices.status')" sort-key="repair_status" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
                    <th class="d-none d-md-table-cell" :aria-sort="sortAria('groupname')"><FixometerSortHeader :label="t('devices.group')" sort-key="groupname" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
                    <th :aria-sort="sortAria('event_date')"><FixometerSortHeader :label="t('devices.devices_date')" sort-key="event_date" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!devices.length" data-testid="device-search-empty">
                    <td colspan="8" class="text-muted text-center">
                      {{ t('client.devices.no_results') }}
                    </td>
                  </tr>
                  <template v-for="device in devices" :key="device.id">
                    <tr :data-testid="`device-search-row-${device.id}`">
                      <td>{{ device.item_type || '-' }}</td>
                      <td>{{ device.category ? t(device.category.name) : '-' }}</td>
                      <td class="d-none d-md-table-cell">{{ device.brand || '-' }}</td>
                      <td class="d-none d-md-table-cell">
                        <span class="device-search-assessment">{{ device.short_problem || '-' }}</span>
                      </td>
                      <td>
                        <BBadge v-if="statusLabel(device)" :variant="deviceStatusVariant(device)" :data-testid="`device-search-status-${device.id}`">
                          {{ statusLabel(device) }}
                        </BBadge>
                      </td>
                      <td class="d-none d-md-table-cell">{{ device.groupname || '-' }}</td>
                      <td>
                        <!-- The date is the event's, linking to the repair's items on the event page. -->
                        <NuxtLink
                          v-if="device.eventid"
                          :to="`/party/view/${device.eventid}#devices-section`"
                          :title="t('devices.view_event_for_repair')"
                          :data-testid="`device-search-view-${device.id}`"
                        >
                          {{ formatDate(device.event_date) }}
                        </NuxtLink>
                        <span v-else>{{ formatDate(device.event_date) }}</span>
                      </td>
                      <td class="text-md-end">
                        <button
                          type="button"
                          class="device-search-info"
                          :class="{ 'device-search-info--on': expanded.has(device.id) }"
                          :aria-expanded="expanded.has(device.id)"
                          :title="isAdmin ? t('devices.edit_record') : t('devices.view_record')"
                          :aria-label="isAdmin ? t('devices.edit_record') : t('devices.view_record')"
                          :data-testid="isAdmin ? `device-search-edit-${device.id}` : `device-search-info-${device.id}`"
                          @click="toggleDetails(device.id)"
                        >
                          <img :src="isAdmin ? '/icons/edit_ico_green.svg' : '/icons/info_ico_green.svg'" class="device-search-info__icon" alt="">
                        </button>
                      </td>
                    </tr>
                    <tr
                      v-if="expanded.has(device.id)"
                      class="device-search-details"
                      :data-testid="`device-search-details-${device.id}`"
                    >
                      <td colspan="8">
                        <!-- The full device form: read-only for non-admins, editable with an inline delete for
                             admins, and no help icons.  No cancel button - the row's own toggle closes it. -->
                        <DeviceForm
                          :event-id="device.eventid"
                          :device="device"
                          :powered="isPowered(device)"
                          :readonly="!isAdmin"
                          :delete-button="isAdmin"
                          :cancel-button="false"
                          :show-info="false"
                          @saved="onRowClosed(device.id)"
                          @deleted="onRowClosed(device.id)"
                        />
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <BPagination
              v-if="count > PAGE_SIZE"
              v-model="page"
              :total-rows="count"
              :per-page="PAGE_SIZE"
              aria-controls="device-search-results"
              class="mt-3"
              data-testid="device-search-pagination"
            />
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
// The per-section "Clear filters" buttons, as on develop's FixometerFilters.vue: a small brand outline button.
.clear-filters {
  border: 1px solid #0394a6;
  border-radius: 0;
  background-color: #fff;
  color: #0394a6;
  font-size: 13px;
  font-weight: bold;
  line-height: 1.2;
  padding: 4px 12px;
  text-decoration: none;

  &:hover,
  &:focus {
    background-color: #0394a6;
    border-color: #0394a6;
    color: #fff;
  }
}

// The filter rail beside the records (develop's .fp-layout): stacked below md, one part to three from md.
.device-search-layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto;
  gap: 20px 0;

  @media (min-width: 768px) {
    grid-template-columns: 1fr 3fr;
    grid-template-rows: auto;
    gap: 0 20px;
    align-items: start;
  }
}

.device-search-layout__results {
  min-width: 0;
}

// FixometerFilters.vue's collapsible section chrome (border/shadow in the
// lighter brand teal, uppercase clickable header) - kept scoped, same
// reasoning as ImpactStats.vue's stat-card grid. $shadow (assets/css/
// _variables.scss) is 5px.
.device-search-section {
  border: 1px solid #4aaebc;
  box-shadow: 5px 5px 0 0 #4aaebc;
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

// FixometerFilters.vue expands/collapses with an SVG icon image
// (add-icon-brand.svg/minus-icon-brand.svg), not a text +/− glyph - sized to
// match its .icon class.
.device-search-section__toggle {
  width: 30px;
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

// FixometerFilters.vue's `.datepicker` accent - the calendar-button widget
// there is themed brand-orange ($brand-orange #ffbe5f); vue-datepicker-next
// has no equivalent button, so the same accent goes on its input border/
// icon and the popup calendar's selected-day highlight instead.
.device-search-datepicker {
  :deep(.mx-datepicker) {
    width: 100%;
  }

  :deep(.mx-input:hover),
  :deep(.mx-input:focus) {
    border-color: #ffbe5f;
  }

  :deep(.mx-icon-calendar) {
    color: #ffbe5f;
  }

  :deep(.mx-calendar-content .cell.active) {
    background-color: #ffbe5f;
    color: #222;
  }
}

// Per-row edit/info toggle - legacy's single edit_ico_green.svg (admin) or
// info_ico_green.svg (everyone else) icon; a filled circle backdrop while
// its details row is open.
.device-search-info {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  padding: 0;
  border: 1px solid #0394a6;
  border-radius: 50%;
  background: #fff;
  cursor: pointer;

  &--on {
    background: #cdeaee;
  }
}

.device-search-info__icon {
  width: 1.1rem;
  height: 1.1rem;
}

.device-search-details > td {
  padding: 0;
  background: #f5f7fa;
}

// FixometerRecordsTable.vue's .badge - a fixed-width block, not Bootstrap
// 5's default rounded pill. BBadge's root element receives this component's
// scope attribute (Vue scopes a directly-used child's root node), so a plain
// selector reaches it without :deep().
.badge {
  width: 90px;
  padding: 0;
  border-radius: 0;
  font-size: small;
  line-height: 2;
  text-transform: uppercase;
}

// The Assessment cell is clamped to 3 lines so a long assessment can't grow the row height.
.device-search-assessment {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

// Below md the hidden columns drop out and each row, header included, reflows into a compact 2-column grid
// instead of a horizontally scrolling table.
@media (max-width: 767.98px) {
  .device-search-table :deep(tr:not(.device-search-details)) {
    display: grid;
    grid-template-columns: 1fr 1fr;
    padding-bottom: 0.3rem;
    border-bottom: 1px solid #222;
  }

  .device-search-table :deep(tr:not(.device-search-details) > th),
  .device-search-table :deep(tr:not(.device-search-details) > td) {
    width: 100%;
    border-bottom: none !important;
    padding: 0.2rem 0;
  }
}
</style>
