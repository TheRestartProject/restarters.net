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
      <!-- Groups/events counts as neo-brutalist stat-box cards (same white
           box / #222 border / 4px offset shadow / teal value look as
           GroupStats.vue's .stat-card and ImpactStats.vue's waste/CO2 cards
           just below) in a responsive grid, instead of plain unstyled
           numbers - matches legacy NetworkPage.vue's stats-grid .stat-box. -->
      <div class="network-stats__grid mb-3">
        <div class="stat-box" data-testid="network-stats-groups">
          <div class="stat-box__count">{{ groupsCount ?? 0 }}</div>
          <div class="stat-box__label">{{ t('networks.stats.groups', { count: groupsCount ?? 0 }, groupsCount ?? 0) }}</div>
        </div>
        <div class="stat-box" data-testid="network-stats-parties">
          <div class="stat-box__count">{{ partiesCount }}</div>
          <div class="stat-box__label">{{ t('networks.stats.events', { count: partiesCount }, partiesCount) }}</div>
        </div>
      </div>

      <!-- Waste/CO2 (plus participants/years/powered-unpowered, a superset
           of legacy's plain waste+CO2 pair) already render as the same
           bordered stat-card look via ImpactStats.vue - reused as-is rather
           than duplicated here. -->
      <ImpactStats :impact-data="stats" />
    </template>
  </div>
</template>

<style scoped lang="scss">
.network-stats__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

// Neo-brutalist stat-box card (same formula as GroupStats.vue's .stat-card /
// ImpactStats.vue's .stat-card): white box, near-black border + offset
// shadow, teal value.
.stat-box {
  background: #fff;
  border: 1px solid #222;
  box-shadow: 4px 4px 0 #222;
  padding: 1rem 0.5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}

.stat-box__count {
  font-size: 1.75rem;
  font-weight: bold;
  color: var(--bs-primary, #0394a6);
  line-height: 1;
}

.stat-box__label {
  font-size: 0.875rem;
  color: #6c757d;
}
</style>
