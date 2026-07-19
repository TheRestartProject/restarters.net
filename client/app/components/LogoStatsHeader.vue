<script setup>
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFixometerStore } from '~/stores/fixometer.js'
import IconLogo from '~/components/icons/IconLogo.vue'

// The logged-out header: brand mark + the global impact-stats bar
// (#logostats-header / includes/info.blade.php). Legacy showed this on the
// landing / login / register pages instead of a login/join nav. The four
// figures come from GET /api/homepage_data (fixometer store impactData):
// items fixed, CO2e prevented, waste prevented, events held. Stats are
// desktop-only (the legacy bar was d-none d-md-block), so mobile shows just
// the logo.
const { t } = useI18n()
const fixometerStore = useFixometerStore()

onMounted(() => {
  fixometerStore.fetchImpactData().catch(() => {})
})

const data = computed(() => fixometerStore.impactData.data)

// Legacy weight/CO2 display: tonnes once past 1000 (kg), else kg - matching
// includes/info.blade.php's `@if($x >= 1000) ... tonnes @else ... kg`.
function weight(kg) {
  const n = Number(kg ?? 0)
  return n >= 1000
    ? `${Math.round(n / 1000).toLocaleString()} ${t('client.fixometer.tonnes')}`
    : `${Math.round(n).toLocaleString()} ${t('client.fixometer.kg')}`
}

const stats = computed(() => {
  const d = data.value
  if (!d) {
    return []
  }
  return [
    { figure: Number(d.items_fixed ?? 0).toLocaleString(), labelKey: 'login.stat_1' },
    { figure: weight(d.co2_total), labelKey: 'login.stat_2' },
    { figure: weight(d.waste_total), labelKey: 'login.stat_3' },
    { figure: Number(d.events_held ?? 0).toLocaleString(), labelKey: 'login.stat_4' },
  ]
})
</script>

<template>
  <div id="logostats-header" class="d-flex align-items-center justify-content-between" data-testid="logo-stats-header">
    <NuxtLink to="/" data-testid="logo-stats-logo">
      <IconLogo />
    </NuxtLink>

    <div v-if="stats.length" class="stats d-none d-md-flex align-items-center" data-testid="logo-stats-figures">
      <div v-for="s in stats" :key="s.labelKey" class="stats__stat text-center">
        <div class="stat-figure">{{ s.figure }}</div>
        <!-- eslint-disable-next-line vue/no-v-html — fixed lang strings (CO2 <sub>, <br>) -->
        <p class="stat-header" v-html="t(s.labelKey)" />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
// Ported from legacy resources/sass/_login-page.scss (#logostats-header + .stats).
.stats {
  &__stat {
    border-right: 1px solid #222;
    padding: 0 10px;
    white-space: nowrap;

    &:last-child {
      border-right: 0;
    }
  }

  .stat-header {
    font-family: 'Open Sans', sans-serif;
    margin: 0;
    color: #0394a6;
    font-size: 14px;
    line-height: 1;
    white-space: nowrap;
  }

  .stat-figure {
    font-family: 'Asap', sans-serif;
    font-size: 33px;
    line-height: 1;
    font-weight: 700;
  }
}
</style>
