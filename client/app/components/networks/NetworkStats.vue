<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// Network impact stats block for /networks/{id} (parity-v2/networks.md gap
// #5). Legacy NetworkPage.vue's "Impact" section is ONE unified 4-tile grid
// (Groups / Events / Waste diverted / CO2 prevented - .stats-grid/.stat-box:
// 2px solid black border, no shadow, uppercase labels) - not the
// Fixometer-wide <ImpactStats> component this used to reuse, which pulls in
// participants/years-volunteered/powered-unpowered tiles plus a "Latest
// Repairs" banner that has no place on this page (and, with no latestData
// prop wired up here, always rendered a visible "no repairs yet"
// placeholder with zero legacy equivalent). Rebuilt as a purpose-built
// 4-tile grid matching NetworkPage.vue's markup/styling instead.
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

// NetworkPage.vue's formatWeight(): kg under 1000, tonnes (1dp) above.
function formatWeight(value) {
  if (!value) return '0 kg'
  if (value >= 1000) return `${(value / 1000).toFixed(1)} t`
  return `${Math.round(value)} kg`
}
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

    <div v-else class="stats-grid">
      <div class="stat-box" data-testid="network-stats-groups">
        <div class="stat-value">{{ groupsCount ?? 0 }}</div>
        <div class="stat-label">{{ t('networks.stats.groups', { count: groupsCount ?? 0 }, groupsCount ?? 0) }}</div>
      </div>
      <div class="stat-box" data-testid="network-stats-parties">
        <div class="stat-value">{{ partiesCount }}</div>
        <div class="stat-label">{{ t('networks.stats.events', { count: partiesCount }, partiesCount) }}</div>
      </div>
      <div class="stat-box" data-testid="network-stats-waste">
        <div class="stat-value">{{ formatWeight(stats.waste_total) }}</div>
        <div class="stat-label">{{ t('networks.stats.waste_diverted') }}</div>
      </div>
      <div class="stat-box" data-testid="network-stats-co2">
        <div class="stat-value">{{ formatWeight(stats.co2_total) }}</div>
        <div class="stat-label">{{ t('networks.stats.co2_prevented') }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.stat-box {
  background: #fff;
  border: 2px solid #000;
  padding: 1rem;
  text-align: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: var(--bs-primary, #0394a6);
}

.stat-label {
  font-size: 0.875rem;
  color: #6c757d;
  text-transform: uppercase;
}
</style>
