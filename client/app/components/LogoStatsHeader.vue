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
  <!-- Live's includes/info.blade.php markup: offset logo column + right-floated stats. -->
  <div id="logostats-header" class="row row-expanded" data-testid="logo-stats-header">
    <div class="col-md-3 offset-2 p-0">
      <header>
        <NuxtLink to="/" data-testid="logo-stats-logo">
          <IconLogo />
        </NuxtLink>
      </header>
    </div>
    <div class="col-md-6 d-none d-md-block p-0">
      <div v-if="stats.length" class="row row-compressed stats float-end text-center" data-testid="logo-stats-figures">
        <div v-for="s in stats" :key="s.labelKey" class="stats__stat">
          <div class="stat-figure">{{ s.figure }}</div>
          <!-- eslint-disable-next-line vue/no-v-html — fixed lang strings (CO2 <sub>, <br>) -->
          <div class="stat-header" v-html="t(s.labelKey)" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
// Ported from live's resources/sass/_login-page.scss (#logostats-header + .stats).
#logostats-header {
  margin: 0 -15px;

  header :deep(svg) {
    width: 204px;
    height: auto;
    display: block;

    @media (max-width: 767.98px) {
      margin: auto;
    }
  }
}

.stats {
  // Row margins/padding: live's .row-compressed (10px gutters) inside a 15px-gutter header.
  flex-wrap: nowrap;
  margin: 0 -15px 30px;

  &__stat {
    border-right: 1px solid #000;
    padding: 0 10px;
    white-space: nowrap;
    width: auto;
    flex: 0 0 auto;

    &:last-child {
      border-right: 1px solid #000;
    }
  }

  .stat-header {
    font-family: 'Open Sans', sans-serif;
    margin: 0;
    color: #222;
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
