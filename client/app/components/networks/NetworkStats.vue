<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// The Impact section of /networks/{id}, as develop's NetworkPage.vue: four
// tiles (groups, events, waste diverted, CO2 prevented). The stats arrive
// after the rest of the page because they are slow to calculate for a large
// network, so each figure shows "…" until then, or "–" if they fail to load.
const props = defineProps({
  stats: {
    type: Object,
    default: null,
  },
  error: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()

const loaded = computed(() => !!props.stats && Object.keys(props.stats).length > 0)
const placeholder = computed(() => (props.error ? '–' : '…'))
const groups = computed(() => props.stats?.groups || 0)
const parties = computed(() => props.stats?.parties || 0)

// NetworkPage.vue's formatWeight(): kg under 1000, tonnes (1dp) above.
function formatWeight(value) {
  if (!value) return '0 kg'
  if (value >= 1000) return `${(value / 1000).toFixed(1)} t`
  return `${Math.round(value)} kg`
}
</script>

<template>
  <div data-testid="network-stats">
    <div class="stats-grid">
      <div class="stat-box" data-testid="network-stats-groups">
        <div class="stat-value">{{ loaded ? groups : placeholder }}</div>
        <div class="stat-label">{{ t('networks.stats.groups', { count: groups }, groups) }}</div>
      </div>
      <div class="stat-box" data-testid="network-stats-parties">
        <div class="stat-value">{{ loaded ? parties : placeholder }}</div>
        <div class="stat-label">{{ t('networks.stats.events', { count: parties }, parties) }}</div>
      </div>
      <div class="stat-box" data-testid="network-stats-waste">
        <div class="stat-value">{{ loaded ? formatWeight(stats.waste_total) : placeholder }}</div>
        <div class="stat-label">{{ t('networks.stats.waste_diverted') }}</div>
      </div>
      <div class="stat-box" data-testid="network-stats-co2">
        <div class="stat-value">{{ loaded ? formatWeight(stats.co2_total) : placeholder }}</div>
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
  // $brand-light from app/assets/css/_variables.scss, hardcoded here as
  // other components already do in scoped styles (no global variable
  // import wired up for component <style> blocks).
  color: #4aaebc;
}

.stat-label {
  font-size: 0.875rem;
  color: #6c757d;
  text-transform: uppercase;
}
</style>
