<script setup>
import { useI18n } from 'vue-i18n'
import TagMultiselect from '../forms/TagMultiselect.vue'
import DatePicker from 'vue-datepicker-next'
import 'vue-datepicker-next/index.css'

// Filter bar for /party/all and /party/all-past (api-contracts-phase-c.md
// C2, judgment call 5: these routes have no working legacy implementation
// to port, so this started as a deliberately small, new build - a title/
// venue/group text search over the events already fetched).
//
// The title search is always shown. The country picker and date-range
// inputs are opt-in (gated on countryOptions/dateRange below) so
// party/all-past.vue's existing `<EventFilters v-model:search="search" />`
// usage renders exactly as before - only party/index.vue's "all other
// events" tab (gap 18) passes them, once group.country is available on the
// event resource.
//
// Functional spec: GroupEventsScrollTableFilters.vue - a title
// b-form-input, a single-select country `<multiselect>` and two
// b-form-datepickers. Ported here as TagMultiselect.vue (this project's
// vue-multiselect equivalent - see its own class doc comment: vue-multiselect
// is Vue 2 only) for the country picker, and vue-datepicker-next's
// DatePicker (already EventForm.vue's own b-form-datepicker stand-in) for
// the date-range pair, rather than the plain <select>/<input type="date">
// pair this used to render.
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
        :placeholder="t('events.search_title_placeholder')"
        :value="search"
        data-testid="event-filters-search"
        @input="$emit('update:search', $event.target.value)"
      >
    </div>

    <div v-if="countryOptions.length" class="col-12 col-md-auto">
      <label class="form-label visually-hidden" for="event-filters-country">
        {{ t('groups.search_country_placeholder') }}
      </label>
      <TagMultiselect
        id="event-filters-country"
        :model-value="country"
        :options="countryOptions"
        :multiple="false"
        :placeholder="t('groups.search_country_placeholder')"
        data-testid="event-filters-country"
        @update:model-value="$emit('update:country', $event ?? '')"
      />
    </div>

    <template v-if="dateRange">
      <div class="col-6 col-md-auto">
        <label class="form-label visually-hidden" for="event-filters-start">
          {{ t('events.search_start_placeholder') }}
        </label>
        <!-- ssr:false (nuxt.config.ts) - no hydration mismatch risk, so this
             renders unwrapped, matching EventForm.vue's identical date-field
             split (desktop DatePicker / mobile native fallback carrying the
             test hook and accessible label). -->
        <DatePicker
          :value="start"
          type="date"
          value-type="YYYY-MM-DD"
          format="ddd, MMM D, YYYY"
          input-class="form-control d-none d-lg-block"
          :placeholder="t('events.search_start_placeholder')"
          @update:value="$emit('update:start', $event)"
        />
        <input
          id="event-filters-start"
          type="date"
          class="form-control d-block d-lg-none"
          :value="start"
          data-testid="event-filters-start"
          @input="$emit('update:start', $event.target.value)"
        >
      </div>
      <div class="col-6 col-md-auto">
        <label class="form-label visually-hidden" for="event-filters-end">
          {{ t('events.search_end_placeholder') }}
        </label>
        <DatePicker
          :value="end"
          type="date"
          value-type="YYYY-MM-DD"
          format="ddd, MMM D, YYYY"
          input-class="form-control d-none d-lg-block"
          :placeholder="t('events.search_end_placeholder')"
          @update:value="$emit('update:end', $event)"
        />
        <input
          id="event-filters-end"
          type="date"
          class="form-control d-block d-lg-none"
          :value="end"
          data-testid="event-filters-end"
          @input="$emit('update:end', $event.target.value)"
        >
      </div>
    </template>
  </div>
</template>

<style scoped lang="scss">
// Rendered-parity fix: vue-datepicker-next's own default (index.css) puts
// the calendar icon on the right (`.mx-icon-calendar { right: 8px }`) and
// pads the input to match; develop's b-form-datepicker (which this stands
// in for - see the class doc comment) puts it on the left instead - same
// fix as EventForm.vue's identical date fields.
:deep(.mx-icon-calendar) {
  right: auto;
  left: 8px;
}

:deep(.mx-input) {
  padding-left: 30px;
  padding-right: 10px;
}
</style>

