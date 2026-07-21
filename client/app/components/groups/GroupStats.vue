<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// GET /api/v2/groups/{id}/stats. Functional + visual spec:
// group/view.blade.php's GroupStatsFacts + StatsImpact - group achievements
// and environmental impact only (the neo-brutalist stat-card boxes, shared
// StatsValue.vue look reused from the fixometer ImpactStats card here).
// Sits between the header and the events list (GroupPage.vue's order); the
// devices-worked-on/most-repaired-podium/cluster-breakdown sections that
// used to live in this component moved out to GroupDevicesSummary.vue and
// GroupDevicesBreakdown.vue, rendered after the events list, to match
// develop's page order (parity gap 13).
const props = defineProps({
  stats: {
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

const emit = defineEmits(['share-stats'])

const { t } = useI18n()

const groupStats = computed(() => props.stats?.group_stats || null)

const infoButton = ref(null)

// GroupStatsFacts.vue: events (brand-teal) / participants / hours.
const achievements = computed(() =>
  groupStats.value
    ? [
        { icon: 'coffee', count: groupStats.value.parties ?? 0, label: t('groups.events', 2), testid: 'group-stats-parties', primary: true },
        { icon: 'participants', count: groupStats.value.participants ?? 0, label: t('groups.participants'), testid: 'group-stats-participants' },
        { icon: 'clock', count: groupStats.value.hours_volunteered ?? 0, label: t('groups.hours_volunteered'), testid: 'group-stats-hours' },
      ]
    : []
)

// StatsImpact.vue rounds waste/CO2 to whole kg (the group page shows kg, not the
// fixometer's tonnes).
function kg(value) {
  return `${Math.round(value ?? 0).toLocaleString()} kg`
}

// StatsImpact.vue's notincluded(): the "not counting X/Y/Z devices" caveat,
// built from dead/repairable/no-weight counts and joined with "and" when
// more than one applies. events.to_be_recycled/to_be_repaired/no_weight are
// reused as-is (already exported to the client, identical wording pattern to
// the group-specific groups.not_counting intro).
const notCounting = computed(() => {
  if (!groupStats.value) {
    return null
  }

  const parts = []

  if (groupStats.value.dead_devices) {
    parts.push(t('events.to_be_recycled', { value: groupStats.value.dead_devices }, groupStats.value.dead_devices))
  }
  if (groupStats.value.repairable_devices) {
    parts.push(t('events.to_be_repaired', { value: groupStats.value.repairable_devices }, groupStats.value.repairable_devices))
  }
  if (groupStats.value.no_weight) {
    parts.push(t('events.no_weight', { value: groupStats.value.no_weight }, groupStats.value.no_weight))
  }

  if (!parts.length) {
    return null
  }

  const intro = t('groups.not_counting', parts.length)

  if (parts.length === 1) {
    return `${intro} ${parts[0]}.`
  }

  const last = parts[parts.length - 1]
  const rest = parts.slice(0, -1)
  return `${intro} ${rest.join(', ')} ${t('groups.and')} ${last}.`
})
</script>

<template>
  <div data-testid="group-stats">
    <div v-if="loading" data-testid="group-stats-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 4rem" />
      </div>
    </div>

    <div v-else-if="error || !stats" class="text-muted" data-testid="group-stats-unavailable">
      {{ t('client.groups.stats_unavailable') }}
    </div>

    <template v-else>
      <div class="group-stats__row">
        <section v-if="groupStats" class="group-stats__col" data-testid="group-stats-facts">
          <h2 class="mt-2 mb-2">{{ t('groups.group_facts') }}</h2>
          <div class="stat-cards stat-cards--facts">
            <div
              v-for="card in achievements"
              :key="card.testid"
              class="stat-card"
              :class="{ 'stat-card--primary': card.primary }"
              :data-testid="card.testid"
            >
              <img :src="`/images/${card.icon}.svg`" alt="" class="stat-card__icon">
              <div class="stat-card__count">{{ card.count }}</div>
              <div class="stat-card__label">{{ card.label }}</div>
            </div>
          </div>
        </section>

        <section v-if="groupStats" class="group-stats__col" data-testid="group-stats-impact">
          <h2>
            {{ t('groups.environmental_impact') }}
            <button ref="infoButton" type="button" class="info-toggle" data-testid="group-stats-impact-info">
              <img :src="'/icons/info_ico_green.svg'" alt="" width="20" height="20">
            </button>
            <BPopover :target="infoButton" triggers="hover focus click" html data-testid="group-stats-impact-popover">
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div v-html="t('groups.impact_calculation')" />
            </BPopover>
          </h2>
          <div class="stat-cards stat-cards--impact">
            <div class="stat-card" data-testid="group-stats-waste">
              <img :src="'/images/trash.svg'" alt="" class="stat-card__icon">
              <div class="stat-card__count">{{ kg(groupStats.waste_total) }}</div>
              <div class="stat-card__label">{{ t('partials.waste_prevented') }}</div>
            </div>
            <div class="stat-card" data-testid="group-stats-co2">
              <img :src="'/images/cloud-empty.svg'" alt="" class="stat-card__icon">
              <div class="stat-card__count">{{ kg(groupStats.co2_total) }}</div>
              <!-- eslint-disable-next-line vue/no-v-html -->
              <div class="stat-card__label" v-html="t('partials.co2')" />
              <button
                type="button"
                class="btn btn-link p-0 small"
                data-testid="group-stats-share"
                @click="emit('share-stats')"
              >
                {{ t('partials.share_this') }}
              </button>
            </div>
          </div>
          <p v-if="notCounting" class="small text-muted mt-2" data-testid="group-stats-not-counting">
            {{ notCounting }}
          </p>
        </section>
      </div>
    </template>
  </div>
</template>

<style scoped lang="scss">
.group-stats__row {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.group-stats__col {
  flex: 1 1 320px;
  min-width: 0;
}

.stat-cards {
  display: grid;
  gap: 1rem;
}

// GroupStatsFacts.vue's mobile layout (findings: parity-shots/mobile/
// 09-group-view): the primary "events" card spans the full row, with
// participants/hours-volunteered sharing a 2-column row below it; all
// three become equal columns in a single row from md (768px) up.
.stat-cards--facts {
  grid-template-columns: 1fr 1fr;

  .stat-card--primary {
    grid-column: 1 / -1;
  }

  @media (min-width: 768px) {
    grid-template-columns: repeat(3, 1fr);

    .stat-card--primary {
      grid-column: auto;
    }
  }
}

// StatsImpact.vue's mobile layout: waste/CO2 cards stack full-width, one
// per row, only becoming a 1fr:2fr (not equal) side-by-side pair from md up.
.stat-cards--impact {
  grid-template-columns: 1fr;

  @media (min-width: 768px) {
    grid-template-columns: 1fr 2fr;
  }
}

// Neo-brutalist stat card (shared StatsValue.vue look; same as the fixometer
// ImpactStats card): white box, near-black border + offset shadow, teal value.
// Sizing comes from the parent .stat-cards--facts/--impact grid tracks
// (not a flex-item property) - see those rules above.
.stat-card {
  min-width: 90px;
  padding: 1rem 0.5rem;
  text-align: center;
  background: #fff;
  border: 1px solid #222;
  box-shadow: 4px 4px 0 #222;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}

// The first card of each group (events / total) is filled brand-teal, with a
// white icon and light text - matching the live site's highlighted card.
.stat-card--primary {
  background: #4aaebc;

  .stat-card__icon {
    filter: brightness(0) invert(1);
  }

  .stat-card__count,
  .stat-card__label {
    color: #fff;
  }
}

.stat-card__icon {
  height: 40px;
}

.stat-card__count {
  font-size: 1.75rem;
  font-weight: bold;
  color: var(--bs-primary, #0394a6);
  line-height: 1;
}

.stat-card__label {
  line-height: 1.1;
}

// StatsImpact.vue's info-icon popover trigger: an inline, borderless icon
// button next to the "Environmental impact" heading.
.info-toggle {
  border: 0;
  background: none;
  padding: 0;
  margin-left: 0.35rem;
  vertical-align: middle;
}
</style>
