<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCo2Equivalent } from '../../composables/useCo2Equivalent.js'

// Global impact stat blocks for /fixometer (api-contracts-phase-c.md C6b;
// design.md §6.2 C6 task brief). Functional spec:
// resources/js/components/FixometerGlobalImpact.vue - same six figures,
// same formulas, replicated as stat tiles (h3 value + small muted label)
// rather than the legacy's bespoke StatsValue/CSS-grid markup, matching
// the tile pattern already established by components/groups/GroupStats.vue.
const props = defineProps({
  // The GET /api/homepage_data body (ConfigAPI#homepageData) - null while
  // unloaded.
  impactData: {
    type: Object,
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
const { equivalentConsumer } = useCo2Equivalent()

const wasteTonnes = computed(() => Math.round((props.impactData?.waste_total ?? 0) / 1000))
const co2Tonnes = computed(() => Math.round((props.impactData?.co2_total ?? 0) / 1000))
const co2Equivalent = computed(() => equivalentConsumer(Math.round(props.impactData?.co2_total ?? 0)))
// FixometerGlobalImpact.vue: Math.round(10 * hours_volunteered / 8766) / 10
const yearsVolunteered = computed(() => Math.round((10 * (props.impactData?.hours_volunteered ?? 0)) / 8766) / 10)
</script>

<template>
  <div data-testid="impact-stats">
    <div v-if="loading" data-testid="impact-stats-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 4rem" />
      </div>
    </div>

    <div v-else-if="error || !impactData" class="text-muted" data-testid="impact-stats-unavailable">
      {{ t('client.fixometer.load_error') }}
    </div>

    <template v-else>
      <div class="d-flex flex-wrap gap-4 mb-3">
        <div data-testid="impact-stat-waste">
          <div class="h3 mb-0">{{ wasteTonnes.toLocaleString() }} {{ t('client.fixometer.tonnes') }}</div>
          <div class="small text-muted">{{ t('partials.waste_prevented') }}</div>
        </div>

        <div data-testid="impact-stat-co2">
          <div class="h3 mb-0">{{ co2Tonnes.toLocaleString() }} {{ t('client.fixometer.tonnes') }}</div>
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="small text-muted" v-html="t('partials.co2')" />
          <div v-if="co2Tonnes > 0" class="small text-muted" data-testid="impact-stat-co2-equivalent">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <span v-html="co2Equivalent" />
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-4 mb-3">
        <div data-testid="impact-stat-participants">
          <div class="h3 mb-0">{{ (impactData.participants ?? 0).toLocaleString() }}</div>
          <div class="small text-muted">{{ t('groups.participants') }}</div>
        </div>

        <div data-testid="impact-stat-years-volunteered">
          <div class="h3 mb-0">{{ yearsVolunteered }}</div>
          <div class="small text-muted">{{ t('groups.years_volunteered') }}</div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-4">
        <div data-testid="impact-stat-powered">
          <div class="h3 mb-0">{{ (impactData.fixed_powered ?? 0).toLocaleString() }}</div>
          <div class="small text-muted">{{ t('devices.powered_items') }}</div>
        </div>

        <div data-testid="impact-stat-unpowered">
          <div class="h3 mb-0">{{ (impactData.fixed_unpowered ?? 0).toLocaleString() }}</div>
          <div class="small text-muted">{{ t('devices.unpowered_items') }}</div>
        </div>
      </div>

      <p class="small text-muted mt-3">{{ t('partials.impact_estimates') }}</p>
    </template>
  </div>
</template>
