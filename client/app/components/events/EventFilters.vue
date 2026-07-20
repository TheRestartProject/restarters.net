<script setup>
import { useI18n } from 'vue-i18n'

// Filter bar for /party/all and /party/all-past (api-contracts-phase-c.md
// C2, judgment call 5: these routes have no working legacy implementation
// to port, so this started as a deliberately small, new build - a title/
// venue/group text search over the events already fetched).
//
// The title search is always shown. The country dropdown and date-range
// inputs are opt-in (gated on countryOptions/dateRange below) so
// party/all-past.vue's existing `<EventFilters v-model:search="search" />`
// usage renders exactly as before - only party/index.vue's "all other
// events" tab (gap 18) passes them, once group.country is available on the
// event resource. This is a native <select>/<input type="date"> pair
// rather than a port of legacy's GroupEventsScrollTableFilters.vue
// (b-form-datepicker + vue-multiselect), matching the plain-control idiom
// already used for filters elsewhere in the client (e.g.
// DevicesSearchTable.vue's status/category <select>s).
defineProps({
  search: {
    type: String,
    default: '',
  },
  country: {
    type: String,
    default: '',
  },
  // Distinct group.country values to offer - the country control only
  // renders when this is non-empty.
  countryOptions: {
    type: Array,
    default: () => [],
  },
  start: {
    type: String,
    default: '',
  },
  end: {
    type: String,
    default: '',
  },
  // Whether to render the start/end date inputs at all.
  dateRange: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:search', 'update:country', 'update:start', 'update:end'])

const { t } = useI18n()
</script>

<template>
  <div class="event-filters mb-3 row g-2" data-testid="event-filters">
    <div class="col-12 col-md">
      <label class="form-label visually-hidden" for="event-filters-search">
        {{ t('client.events.search_label') }}
      </label>
      <input
        id="event-filters-search"
        type="search"
        class="form-control"
        :placeholder="t('client.events.search_placeholder')"
        :value="search"
        data-testid="event-filters-search"
        @input="$emit('update:search', $event.target.value)"
      >
    </div>

    <div v-if="countryOptions.length" class="col-12 col-md-auto">
      <label class="form-label visually-hidden" for="event-filters-country">
        {{ t('groups.search_country_placeholder') }}
      </label>
      <select
        id="event-filters-country"
        class="form-select"
        :value="country"
        data-testid="event-filters-country"
        @change="$emit('update:country', $event.target.value)"
      >
        <option value="">{{ t('groups.search_country_placeholder') }}</option>
        <option v-for="option in countryOptions" :key="option" :value="option">{{ option }}</option>
      </select>
    </div>

    <template v-if="dateRange">
      <div class="col-6 col-md-auto">
        <label class="form-label visually-hidden" for="event-filters-start">
          {{ t('client.events.filter_start_label') }}
        </label>
        <input
          id="event-filters-start"
          type="date"
          class="form-control"
          :placeholder="t('client.events.filter_start_label')"
          :value="start"
          data-testid="event-filters-start"
          @input="$emit('update:start', $event.target.value)"
        >
      </div>
      <div class="col-6 col-md-auto">
        <label class="form-label visually-hidden" for="event-filters-end">
          {{ t('client.events.filter_end_label') }}
        </label>
        <input
          id="event-filters-end"
          type="date"
          class="form-control"
          :placeholder="t('client.events.filter_end_label')"
          :value="end"
          data-testid="event-filters-end"
          @input="$emit('update:end', $event.target.value)"
        >
      </div>
    </template>
  </div>
</template>
