<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCo2Equivalent } from '../../composables/useCo2Equivalent.js'
import LatestRepairs from './LatestRepairs.vue'

// Global impact stat-card grid for /fixometer (api-contracts-phase-c.md C6b;
// design.md §6.2 C6 task brief). Functional spec: resources/js/components/
// FixometerGlobalImpact.vue + StatsValue.vue (git show 07e6abd7cc^, the
// commit before Phase F deleted the Blade + Vue 2 frontend) - same six
// figures, same formulas, same CSS-grid layout (a "Latest Data" banner plus
// waste/co2/participants/years/powered/unpowered cards), replicated here as
// a purpose-built stat-card grid rather than porting StatsValue.vue's
// generic variant/size/icon/description prop surface wholesale - Fixometer
// is its only "secondary variant" consumer in scope for this task
// (StatsImpact.vue's event/group impact cards are a separate component).
//
// Colour: matches the live site - near-black ($black #222) card border +
// offset shadow (legacy StatsValue.vue's .hasBorder), with only the value
// text and the "Latest Data" banner fill in brand teal.
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
  // GET /api/v2/stats/latest-repaired-event, passed straight through to the
  // "Latest Data" grid card. FixometerLatestData.vue was nested inside
  // FixometerGlobalImpact.vue's own grid in the legacy app; LatestRepairs.vue
  // (already built, already tested standalone) is reused here as that grid
  // cell rather than duplicating its markup/logic.
  latestData: {
    type: Object,
    default: null,
  },
  latestLoading: {
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
    <!-- Skeleton laid out in the SAME grid as the real content, one block per
         grid-area, rather than a single short bar. A 4rem bar stood in for a
         multi-row grid, so the page jumped by hundreds of pixels the moment
         the stats arrived. Reusing the grid means the reserved space tracks
         the real layout at every breakpoint without hard-coding a height. -->
    <div v-if="loading" class="stat-grid placeholder-glow" data-testid="impact-stats-loading">
      <span
        v-for="area in ['latest', 'waste', 'co2', 'participants', 'years', 'powered', 'unpowered']"
        :key="area"
        class="placeholder"
        :class="`stat-grid__${area}`"
      />
    </div>

    <div v-else-if="error || !impactData" class="text-muted" data-testid="impact-stats-unavailable">
      {{ t('client.fixometer.load_error') }}
    </div>

    <template v-else>
      <div class="stat-grid">
        <LatestRepairs :latest-data="latestData" :loading="latestLoading" class="stat-grid__latest" />

        <div class="stat-card stat-grid__waste" data-testid="impact-stat-waste">
          <img src="/images/trash.svg" class="stat-card__icon" alt="">
          <div class="stat-card__count">{{ wasteTonnes.toLocaleString() }} {{ t('client.fixometer.tonnes') }}</div>
          <div class="stat-card__label">{{ t('partials.waste_prevented') }}</div>
        </div>

        <div class="stat-card stat-grid__co2" data-testid="impact-stat-co2">
          <img src="/images/cloud-empty.svg" class="stat-card__icon" alt="">
          <div class="stat-card__count">{{ co2Tonnes.toLocaleString() }} {{ t('client.fixometer.tonnes') }}</div>
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="stat-card__label" v-html="t('partials.co2')" />
          <div v-if="co2Tonnes > 0" class="stat-card__description" data-testid="impact-stat-co2-equivalent">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <span v-html="co2Equivalent" />
          </div>
        </div>

        <div class="stat-card stat-grid__participants" data-testid="impact-stat-participants">
          <img src="/images/participants.svg" class="stat-card__icon" alt="">
          <div class="stat-card__count">{{ (impactData.participants ?? 0).toLocaleString() }}</div>
          <div class="stat-card__label">{{ t('groups.participants') }}</div>
        </div>

        <div class="stat-card stat-grid__years" data-testid="impact-stat-years-volunteered">
          <img src="/images/clock.svg" class="stat-card__icon" alt="">
          <div class="stat-card__count">{{ yearsVolunteered }}</div>
          <div class="stat-card__label">{{ t('groups.years_volunteered') }}</div>
        </div>

        <div class="stat-card stat-grid__powered" data-testid="impact-stat-powered">
          <img src="/images/powered.svg" class="stat-card__icon" alt="">
          <div class="stat-card__count">{{ (impactData.fixed_powered ?? 0).toLocaleString() }}</div>
          <div class="stat-card__label">{{ t('devices.powered_items') }}</div>
        </div>

        <div class="stat-card stat-grid__unpowered" data-testid="impact-stat-unpowered">
          <img src="/images/unpowered.svg" class="stat-card__icon" alt="">
          <div class="stat-card__count">{{ (impactData.fixed_unpowered ?? 0).toLocaleString() }}</div>
          <div class="stat-card__label">{{ t('devices.unpowered_items') }}</div>
        </div>
      </div>

      <p class="small text-muted mt-3">{{ t('partials.impact_estimates') }}</p>
    </template>
  </div>
</template>

<style scoped lang="scss">
// Fixometer-specific stat-card grid - kept scoped per this task's brief
// rather than added to assets/css (a parallel task also touches global
// CSS). TODO: promote to global if a second stat-card grid emerges
// elsewhere (e.g. a StatsImpact.vue-equivalent for event/group pages).
// Skeleton blocks: a bare placeholder span has no content, so it would
// collapse to zero height and reserve nothing. These match the stat cards'
// rough height so the grid occupies its real footprint while loading.
.placeholder-glow .placeholder {
  min-height: 5.5rem;
  border-radius: 0.25rem;
}

.stat-grid {
  display: grid;
  gap: 1.25rem;
  grid-template-columns: 1fr 1fr;
  grid-template-areas:
    'latest latest'
    'waste waste'
    'co2 co2'
    'participants years'
    'powered unpowered';
}

.stat-grid__latest {
  grid-area: latest;
}
.stat-grid__waste {
  grid-area: waste;
}
.stat-grid__co2 {
  grid-area: co2;
}
.stat-grid__participants {
  grid-area: participants;
}
.stat-grid__years {
  grid-area: years;
}
.stat-grid__powered {
  grid-area: powered;
}
.stat-grid__unpowered {
  grid-area: unpowered;
}

// FixometerGlobalImpact.vue's media-breakpoint-up(md) layout: a 2fr/2fr/1fr/1fr
// grid where the co2 card spans both rows next to the latest-data/waste column.
@media (min-width: 768px) {
  .stat-grid {
    grid-template-columns: 2fr 2fr 1fr 1fr;
    grid-template-rows: auto auto;
    grid-template-areas:
      'latest co2 participants years'
      'waste co2 powered unpowered';
  }
}

.stat-card {
  height: 100%;
  padding: 1rem 0.5rem;
  text-align: center;
  background-color: #fff;
  // Match the live site: near-black card border + offset shadow, with only
  // the value text in teal (legacy StatsValue.vue's .hasBorder uses $black).
  // $shadow (assets/css/_variables.scss) is 5px.
  border: 1px solid #222;
  box-shadow: 5px 5px 0 #222;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}

.stat-card__icon {
  height: 44px;
}

.stat-card__count {
  font-size: 2rem;
  font-weight: bold;
  color: var(--bs-primary, #0394a6);
}

.stat-card__label {
  font-size: 0.9rem;
  font-weight: bold;
}

.stat-card__description {
  border-top: 3px dashed #222;
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  font-size: 0.85rem;
}
</style>
