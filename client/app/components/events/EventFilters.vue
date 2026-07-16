<script setup>
import { useI18n } from 'vue-i18n'

// Minimal filter bar for /party/all and /party/all-past
// (api-contracts-phase-c.md C2, judgment call 5: these routes have no
// working legacy implementation to port, so this is deliberately a small,
// new build - a title/venue/group text search over the events already
// fetched, not a re-implementation of GroupEventsScrollTableFilters.vue's
// title/country/date-range search, which needs fields (event.group.country,
// a date-range picker) out of scope for this minimal build).
defineProps({
  search: {
    type: String,
    default: '',
  },
})

defineEmits(['update:search'])

const { t } = useI18n()
</script>

<template>
  <div class="event-filters mb-3" data-testid="event-filters">
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
</template>
