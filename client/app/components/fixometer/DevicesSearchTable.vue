<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import { useDevicesStore } from '../../stores/devices.js'
import { useAuth } from '../../composables/useAuth.js'
import { deviceStatusKey, deviceStatusVariant } from '../../composables/useDeviceDisplay.js'
import FixometerSortHeader from './FixometerSortHeader.vue'
import { sortAriaValue } from '../../composables/useSortAria.js'
import DeviceForm from '../devices/DeviceForm.vue'
import FieldInfoPopover from '../forms/FieldInfoPopover.vue'
import DatePicker from 'vue-datepicker-next'
import 'vue-datepicker-next/index.css'

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
const { hasRole } = useAuth()

// Gap fix (HIGH): legacy FixometerRecordsTable.vue gives every row exactly
// ONE toggle control - an edit-pencil icon for admins, an info icon for
// everyone else - which opens the SAME row-details panel: EventDevice.vue,
// disabled (view-only) for non-admins, editable with an inline delete for
// admins (:edit="isAdmin" :delete-button="true" :cancel-button="false"). A
// prior version exposed THREE always-visible admin controls (info toggle +
// separate Edit/Delete text links) and only showed an abbreviated <dl> in
// the details panel. Both are fixed below: `expanded` (already existed for
// the info toggle) now doubles as the admin edit-open state too, and the
// details panel always renders the real DeviceForm - readonly for
// non-admins, editable+deletable for admins - instead of the <dl>.
const isAdmin = computed(() => hasRole('Administrator'))

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

// Current sort state for the column headers - belongs on <th>, not the button.
const sortAria = (key) => sortAriaValue(key, sortBy.value, sortDesc.value)

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

// Search results aren't cached in the store (unlike byEvent[id]), so a
// save/delete from within the expanded row's DeviceForm re-runs the current
// search rather than relying on the store's optimistic patch to update this
// list - closing the row again (matching legacy's `closed(row)` handler,
// which both refreshes the table and calls `row.toggleDetails()`).
function onRowClosed(id) {
  expanded.value = new Set([...expanded.value].filter((x) => x !== id))
  runSearch()
}

const clusters = computed(() => devicesStore.clusters)
// DeviceBrand.vue's `brands` prop - DeviceForm.vue's own brand field ports
// this via a real <input> plus a custom-styled suggestion panel (its own doc
// comment explains why: native <datalist> chrome doesn't match legacy's
// vue-typeahead-bootstrap look), and this filter field reproduces that exact
// pattern rather than a <datalist>.
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
  // Losing focus to our own panel (an option's mousedown is prevented, so
  // this only fires for a genuine focus move elsewhere) closes it - same
  // guard as DeviceForm.vue's `onFieldBlur`.
  if (event.currentTarget.contains(event.relatedTarget)) return
  brandSuggestionsOpen.value = false
}

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

// Gap fix (mobile parity, 05-fixometer mobile screenshot): legacy's mobile
// Repair Records UI (FixometerPage.vue's `d-block d-md-none` block) is a
// different pattern from desktop's tabs, not just a narrower version of it -
// two independent CollapsibleSections titled "Powered"/"Unpowered", both
// collapsed by default, no explainer text, no visible table until expanded.
// A prior version showed the desktop tabs+description+populated-table
// unconditionally at every width - it never diverged for mobile at all,
// which is exactly what the screenshot caught (Bootstrap 4 collapse
// behaviour on develop vs. no such divergence being built here).
//
// legacy mounts TWO fully independent FixometerRecordsTable instances (own
// props, own :total.sync) inside those sections, so both can be expanded
// with live data simultaneously. This component only tracks one powered/
// unpowered result set at a time (the same one the desktop tabs drive) -
// reusing that rather than duplicating the search/pagination/sort/details
// state is an accepted simplification (this file already drops other
// legacy widget-level duplication, see the date-picker note above):
// expanding a mobile section sets `filters.powered` to match and replaces
// whichever section was previously open, instead of both being
// independently live.
const mobileSection = ref(null) // null | 'powered' | 'unpowered'
const mobileSectionExpanded = computed(() => mobileSection.value !== null)

function toggleMobileSection(section) {
  mobileSection.value = mobileSection.value === section ? null : section
  if (mobileSection.value) {
    filters.powered = mobileSection.value === 'powered'
  }
}
</script>

<template>
  <div data-testid="devices-search-table">
    <!-- Legacy FixometerPage.vue's two-column Repair Records card: the filter
         accordions form a narrow left rail, the Powered/Unpowered tabs + table
         a wide right column, both inside one teal-bordered container. Single
         column on narrow viewports. -->
    <div class="device-search-layout">
      <!-- FixometerFilters.vue (legacy) has no responsive hiding of its own -
           it's visible at every width, ABOVE the mobile Powered/Unpowered
           accordion pair below (see mobileSection's own doc comment). A
           prior version wrongly hid this whole rail below md, assuming it
           was desktop-only. -->
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

        <div class="row g-3">
          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-category">
              {{ t('devices.category') }}
              <FieldInfoPopover :content="t('devices.tooltip_category')" />
            </label>
            <select id="device-search-category" v-model.number="filters.category" class="form-select" data-testid="device-search-category">
              <option :value="null">{{ t('client.devices.any_category') }}</option>
              <optgroup v-for="cluster in filteredClusters" :key="cluster.id" :label="t(cluster.name)">
                <option v-for="c in cluster.categories" :key="c.idcategories" :value="c.idcategories">
                  {{ t(c.name) }}
                </option>
              </optgroup>
            </select>
          </div>

          <!-- Gap fix (MEDIUM): FixometerFilters.vue orders Model before Brand
               for powered items - a prior version had them swapped. -->
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

          <div v-if="filters.powered" class="col-sm-6 col-md-4">
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
        <img
          class="device-search-section__toggle"
          :src="eventInfoExpanded ? '/images/minus-icon-brand.svg' : '/images/add-icon-brand.svg'"
          :alt="eventInfoExpanded ? 'Collapse' : 'Expand'"
        >
      </button>
      <fieldset v-show="eventInfoExpanded" class="device-search-section__body">
        <legend class="visually-hidden">{{ t('devices.event_info') }}</legend>

        <div class="row g-3">
          <div class="col-sm-6 col-md-4">
            <label class="form-label" for="device-search-group">{{ t('devices.group') }}</label>
            <input id="device-search-group" v-model="filters.group" type="text" class="form-control" data-testid="device-search-group">
          </div>

          <!-- Gap fix (MEDIUM): FixometerFilters.vue's b-form-datepicker
               (Bootstrap-5-less equivalent: vue-datepicker-next, design.md
               §2/api-gaps.md C4 - already used this way by EventForm.vue's
               event-date field), themed with the brand-orange .datepicker
               accent (FixometerFilters.vue's scoped style). A prior version
               used a bare native <input type="date"> instead. Its rendered
               <input> doesn't take a data-testid (api-gaps.md C4 again), so
               the native fallback below - shown on narrow viewports, same
               dual pattern as EventForm.vue/legacy's own EventDatePicker.vue -
               keeps the stable test hook and remains the only one of the pair
               actually wired to data-testid. -->
          <div class="col-sm-6 col-md-4 device-search-datepicker">
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

          <div class="col-sm-6 col-md-4 device-search-datepicker">
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
        </div>
      </fieldset>
    </div>
      </div>

      <div class="device-search-layout__results ourtabs ourtabs-brand">
        <!-- Desktop: Powered/Unpowered tabs + explainer text (legacy's
             b-tabs, wrapping both the nav strip and the shared results body
             below in one teal-bordered box). Reuses the already-ported
             .ourtabs.ourtabs-brand nav-tabs chrome (assets/css/_tabs.scss,
             same pattern as devices/EventDevicesPanel.vue's desktop tab
             strip) rather than a bespoke pill button-group - the nav sits
             directly under .ourtabs (its `.nav-tabs { border-bottom: none }`
             rule) with the rest of the panel in one `> div:not(.nav)` sibling
             (its `padding: 0 1rem 1rem` rule), matching that CSS's shape. -->
        <ul class="nav nav-tabs d-none d-md-flex mb-3" role="group" data-testid="device-search-powered-toggle">
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: filters.powered }"
              data-testid="device-search-powered-true"
              @click="filters.powered = true"
            >
              {{ poweredLabel }}
            </button>
          </li>
          <li class="nav-item">
            <button
              type="button"
              class="nav-link"
              :class="{ active: !filters.powered }"
              data-testid="device-search-powered-false"
              @click="filters.powered = false"
            >
              {{ unpoweredLabel }}
            </button>
          </li>
        </ul>

        <div class="device-search-layout__content">
          <div class="d-none d-md-block">
            <p class="text-brand small" data-testid="device-search-powered-description">
              <!-- eslint-disable-next-line vue/no-v-html -->
              <span v-html="filters.powered ? t('devices.description_powered') : t('devices.description_unpowered')" />
            </p>
          </div>

          <!-- Mobile: two independent collapsed accordion headers (legacy's
               `d-block d-md-none` CollapsibleSection pair) - no tabs, no
               explainer text, collapsed by default; expanding one reveals the
               shared results body below (mobileSection's own doc comment). -->
          <div class="d-block d-md-none device-search-mobile-sections mb-3">
            <button
              type="button"
              class="device-search-mobile-section__header"
              data-testid="device-search-mobile-powered-toggle"
              :aria-expanded="mobileSection === 'powered'"
              @click="toggleMobileSection('powered')"
            >
              <span>{{ t('devices.title_powered') }}</span>
              <span aria-hidden="true">{{ mobileSection === 'powered' ? '−' : '+' }}</span>
            </button>
            <button
              type="button"
              class="device-search-mobile-section__header"
              data-testid="device-search-mobile-unpowered-toggle"
              :aria-expanded="mobileSection === 'unpowered'"
              @click="toggleMobileSection('unpowered')"
            >
              <span>{{ t('devices.title_unpowered') }}</span>
              <span aria-hidden="true">{{ mobileSection === 'unpowered' ? '−' : '+' }}</span>
            </button>
          </div>

          <!-- Shared results body: always shown on desktop (d-md-block), only
               shown on mobile once a section above is expanded. -->
          <div :class="['d-md-block', { 'd-none': !mobileSectionExpanded }]" data-testid="device-search-results-body">
    <div v-if="loading" data-testid="device-search-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 8rem" />
      </div>
    </div>

    <div v-else-if="error" class="text-danger" data-testid="device-search-error">
      {{ t('client.devices.load_error') }}
    </div>

    <template v-else>
      <!-- Legacy FixometerRecordsTable.vue has no results-count line of its
           own (just devices.table_intro below) - a prior version added one,
           which doesn't appear on /fixometer in develop. -->
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
        <table class="table device-search-table" data-testid="device-search-results">
          <thead>
            <tr>
              <th :aria-sort="sortAria('item_type')"><FixometerSortHeader :label="t('devices.model_or_type')" sort-key="item_type" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th :aria-sort="sortAria('category')"><FixometerSortHeader :label="t('devices.category')" sort-key="category" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <!-- Gap fix (MEDIUM): legacy hides Brand/Assessment/Group below
                   md (FixometerRecordsTable.vue's `d-none d-md-table-cell`
                   fields), and restyles the remaining cells into a compact
                   2-column grid on mobile (see the scoped style block below). -->
              <th v-if="filters.powered" class="d-none d-md-table-cell" :aria-sort="sortAria('brand')"><FixometerSortHeader :label="t('devices.brand')" sort-key="brand" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th class="d-none d-md-table-cell"><FixometerSortHeader :label="t('devices.assessment')" /></th>
              <th class="d-none d-md-table-cell" :aria-sort="sortAria('groupname')"><FixometerSortHeader :label="t('devices.group')" sort-key="groupname" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th :aria-sort="sortAria('repair_status')"><FixometerSortHeader :label="t('devices.status')" sort-key="repair_status" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
              <th :aria-sort="sortAria('created_at')"><FixometerSortHeader :label="t('devices.devices_date')" sort-key="created_at" :active-key="sortBy" :desc="sortDesc" @sort="toggleSort" /></th>
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
                <td v-if="filters.powered" class="d-none d-md-table-cell">{{ device.brand || '-' }}</td>
                <td class="d-none d-md-table-cell">
                  <span class="device-search-assessment">{{ device.short_problem || '-' }}</span>
                </td>
                <td class="d-none d-md-table-cell">{{ device.groupname || '-' }}</td>
                <td>
                  <BBadge v-if="statusLabel(device)" :variant="deviceStatusVariant(device)" :data-testid="`device-search-status-${device.id}`">
                    {{ statusLabel(device) }}
                  </BBadge>
                </td>
                <td>{{ formatDate(device.created_at) }}</td>
                <td class="text-end">
                  <!-- Gap fix (HIGH): a single toggle - edit-pencil for
                       admins, info for everyone else - matching legacy's
                       show_details slot exactly (one edit_ico_green.svg or
                       info_ico_green.svg icon, no separate Edit/Delete
                       links). -->
                  <button
                    type="button"
                    class="device-search-info"
                    :class="{ 'device-search-info--on': expanded.has(device.id) }"
                    :aria-expanded="expanded.has(device.id)"
                    :data-testid="isAdmin ? `device-search-edit-${device.id}` : `device-search-info-${device.id}`"
                    @click="toggleDetails(device.id)"
                  >
                    <img :src="isAdmin ? '/icons/edit_ico_green.svg' : '/icons/info_ico_green.svg'" class="device-search-info__icon" alt="">
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
                  <!-- Gap fix (HIGH): the full device form (repair status,
                       next steps, spare parts, barrier, notes, weight,
                       images) instead of an abbreviated summary - disabled/
                       read-only for non-admins, editable with an inline
                       delete for admins. No cancel button: the row's own
                       toggle above already closes this panel. -->
                  <DeviceForm
                    :event-id="device.eventid"
                    :device="device"
                    :powered="filters.powered"
                    :readonly="!isAdmin"
                    :delete-button="isAdmin"
                    :cancel-button="false"
                    @saved="onRowClosed(device.id)"
                    @deleted="onRowClosed(device.id)"
                  />
                  <NuxtLink :to="`/party/view/${device.eventid}`" :data-testid="`device-search-view-${device.id}`">
                    {{ t('client.events.view_event') }}
                  </NuxtLink>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Gap fix (HIGH): legacy's b-pagination, a numbered page-jump
           control, only rendered above one page - not the prev/next-only
           bar this replaces (which also stayed visible for a single page). -->
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
  </div>
</template>

<style scoped lang="scss">
// Two-column Repair Records layout (legacy FixometerPage.vue's .fp-layout):
// a narrow filter rail beside the tabbed results. .fp-layout itself has no
// border - it's just a grid; the two halves are separately boxed (the filter
// rail's individual accordion sections below, the results column here),
// with a visible gap between them, not one continuous border wrapping both -
// a prior version wrapped the whole grid in a single teal border/padding,
// which visually fused the two into one box. Stacks to a single column below
// the lg breakpoint.
.device-search-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;

  @media (min-width: 992px) {
    grid-template-columns: minmax(220px, 300px) 1fr;
    align-items: start;
  }
}

// legacy's b-tabs (.ourtabs.ourtabs-brand) is itself `d-none d-md-block` -
// the teal border + offset shadow wrapping the nav-tab strip and its
// tab-content (table) only exists on desktop. Below md, legacy's mobile
// Powered/Unpowered accordion (mobileSection's own doc comment) sits
// unboxed, flush with the filter accordion above it - so this border/
// shadow/padding is desktop-only too, not a permanent property of the
// results column. The border/shadow/padding themselves now come from the
// .ourtabs.ourtabs-brand classes on this element (assets/css/_tabs.scss) -
// reusing the same chrome devices/EventDevicesPanel.vue's desktop tab strip
// uses - rather than one-off rules; .ourtabs applies unconditionally, so
// it's switched off below md here to keep that desktop-only behaviour.
.device-search-layout__results {
  min-width: 0;

  // !important: .ourtabs.ourtabs-brand's own border rule (_tabs.scss) is
  // !important, so switching it off below md needs to match.
  @media (max-width: 767.98px) {
    background-color: transparent !important;
    border: 0 !important;
    box-shadow: none !important;
  }

  // Gap in the ported _tabs.scss (assets/css/_tabs.scss): develop's
  // .ourtabs.ourtabs-brand .nav-link.active also gets a 5px teal top border
  // (_events.scss:397-399) - the ported version carries the teal side/bottom
  // border override but not this one. Added locally rather than editing the
  // shared file (also used by EventDevicesPanel.vue, out of scope here).
  :deep(.nav-link.active) {
    border-top: 5px solid #0394a6;
  }
}

// .ourtabs's own `> div:not(.nav) { padding: 0 1rem 1rem }` rule (_tabs.scss)
// supplies this content's side/bottom padding at every width - cancelled
// below md, alongside the border/shadow above, to match the mobile
// accordion's unboxed, flush presentation.
.device-search-layout__content {
  @media (max-width: 767.98px) {
    padding: 0 !important;
  }
}

// Mobile Powered/Unpowered accordion headers - legacy's CollapsibleSection
// with no `border-shadow` prop set: plain text, no box, a hairline divider
// (not the teal-boxed look the filter accordion above uses).
.device-search-mobile-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.75rem 0;
  background: transparent;
  border: 0;
  border-bottom: 1px solid #dee2e6;
  text-align: left;
  font-weight: 700;
  color: #222;
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

// Gap fix (LOW): FixometerRecordsTable.vue clamps the Assessment cell to 3
// lines (v-line-clamp="3") so a long assessment can't grow the row height
// unpredictably.
.device-search-assessment {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

// Gap fix (MEDIUM): FixometerRecordsTable.vue's mobile treatment - below the
// md breakpoint, hidden columns (d-none d-md-table-cell above) drop out and
// the remaining cells in each row reflow into a compact 2-column grid,
// rather than a horizontally-scrolling full-width table.
@media (max-width: 767.98px) {
  .device-search-table :deep(tbody tr:not(.device-search-details)) {
    display: grid;
    grid-template-columns: 1fr 1fr;
    padding-bottom: 0.3rem;
    border-bottom: 1px solid #222;
  }

  .device-search-table :deep(tbody tr:not(.device-search-details) > td) {
    width: 100%;
    border-bottom: none !important;
    padding: 0.2rem 0;
  }
}
</style>

