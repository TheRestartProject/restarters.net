<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ImpactStats from '../fixometer/ImpactStats.vue'

// Network impact stats block for /networks/{id} (design.md §6.2 Phase E
// task E1; NetworkPage.vue's "Impact" stats-grid is the functional spec:
// groups/events counts + waste/CO2 totals).
//
// GET /api/v2/networks/{id}'s embedded `stats` (App\Http\Resources\
// Network#stats -> Network::stats(), same array shape as the homepage's
// GET /api/homepage_data) carries co2_total/waste_total/participants/
// hours_volunteered/fixed_powered/fixed_unpowered/parties - a superset of
// what ImpactStats.vue (built for /fixometer, C6) already renders, so the
// waste/CO2/participants/volunteered-years/powered-unpowered tiles are
// reused as-is rather than re-implemented. `parties` isn't one of
// ImpactStats' tiles, so it's added here alongside `groupsCount` (which
// isn't in the stats payload at all - the legacy web controller computed
// it separately as `$network->groups->count()`; this component takes it
// as its own prop, sourced from the groups list length - see
// docs/nuxt-migration/api-gaps.md Phase E).
const props = defineProps({
  stats: {
    type: Object,
    default: null,
  },
  groupsCount: {
    type: Number,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()

const partiesCount = computed(() => props.stats?.parties ?? 0)
</script>

<template>
  <div data-testid="network-stats">
    <div v-if="loading" data-testid="network-stats-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 4rem" />
      </div>
    </div>

    <div v-else-if="error || !stats" class="text-muted" data-testid="network-stats-unavailable">
      {{ t('client.networks.stats_unavailable') }}
    </div>

    <template v-else>
      <div class="d-flex flex-wrap gap-4 mb-3">
        <div data-testid="network-stats-groups">
          <div class="h3 mb-0">{{ groupsCount ?? 0 }}</div>
          <div class="small text-muted">{{ t('networks.stats.groups', { count: groupsCount ?? 0 }, groupsCount ?? 0) }}</div>
        </div>
        <div data-testid="network-stats-parties">
          <div class="h3 mb-0">{{ partiesCount }}</div>
          <div class="small text-muted">{{ t('networks.stats.events', { count: partiesCount }, partiesCount) }}</div>
        </div>
      </div>

      <ImpactStats :impact-data="stats" />
    </template>
  </div>
</template>
