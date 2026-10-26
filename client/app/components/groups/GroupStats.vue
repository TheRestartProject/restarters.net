<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCo2Equivalent } from '~/composables/useCo2Equivalent.js'

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
  // Compact layout (?stats_layout=compact, the default): the page lays the
  // facts, impact, items and most-repaired panels out in one grid, so this
  // component renders its two sections straight into that grid (bare) and
  // leaves out the "not counting" caveat, as develop does (#922).
  compact: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['share-stats'])

const { t } = useI18n()

const groupStats = computed(() => props.stats?.group_stats || null)

const infoButton = ref(null)
const co2InfoButton = ref(null)

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

// StatsValue.vue's printableCount(): roundedCount.toLocaleString() - every
// stat-card count gets thousand separators, not just the kg() figures.
function printableCount(value) {
  return Math.round(value ?? 0).toLocaleString()
}

// StatsImpact.vue's notincluded(): the "not counting X/Y/Z devices" caveat,
// built from dead/repairable/no-weight counts and joined with "and" when
// more than one applies. events.to_be_recycled/to_be_repaired/no_weight are
// reused as-is (already exported to the client, identical wording pattern to
// the group-specific groups.not_counting intro).
const { equivalentConsumer, popoverConsumer } = useCo2Equivalent()

const co2Description = computed(() => {
  const co2 = Math.round(groupStats.value?.co2_total ?? 0)

  return co2 > 0 ? equivalentConsumer(co2) : null
})
</script>

<template>
  <div data-testid="group-stats" :class="{ 'group-stats--bare': compact }">
    <div v-if="loading" data-testid="group-stats-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 4rem" />
      </div>
    </div>

    <div v-else-if="error || !stats" class="text-muted" data-testid="group-stats-unavailable">
      {{ t('client.groups.stats_unavailable') }}
    </div>

    <template v-else>
      <div class="group-stats__row" :class="{ 'group-stats__row--bare': compact }">
        <section v-if="groupStats" class="group-stats__col stats-facts" data-testid="group-stats-facts">
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
              <div class="stat-card__count">{{ printableCount(card.count) }}</div>
              <div class="stat-card__label">{{ card.label }}</div>
            </div>
          </div>
        </section>

        <section v-if="groupStats" class="group-stats__col stats-impact" data-testid="group-stats-impact">
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
            <!-- develop #946: the figure on the left, its "that's like..." comparison and the share link on the
                 right, with a dashed line between them. -->
            <div class="stat-card stat-card--aside" data-testid="group-stats-co2">
              <div class="stat-card__main">
                <img :src="'/images/cloud-empty.svg'" alt="" class="stat-card__icon">
                <div class="stat-card__count">{{ kg(groupStats.co2_total) }}</div>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div class="stat-card__label" v-html="t('partials.co2')" />
              </div>
              <div class="stat-card__aside">
                <p v-if="co2Description" class="stat-card__description mb-0">
                  <span data-testid="group-stats-co2-description">{{ co2Description }}</span>
                  <button ref="co2InfoButton" type="button" class="info-toggle" data-testid="group-stats-co2-info">
                    <img :src="'/icons/info_ico_green.svg'" alt="" width="20" height="20">
                  </button>
                  <BPopover :target="co2InfoButton" triggers="hover focus click" data-testid="group-stats-co2-popover">
                    {{ popoverConsumer(Math.round(groupStats.co2_total)) }}
                  </BPopover>
                </p>
                <button
                  type="button"
                  class="btn btn-link p-0 small stat-card__share"
                  data-testid="group-stats-share"
                  @click="emit('share-stats')"
                >
                  {{ t('partials.share_this') }}
                </button>
              </div>
            </div>
          </div>
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

// Compact layout: the page's grid places the sections, so this row and its
// wrapper add no box of their own.
.group-stats--bare,
.group-stats__row--bare {
  display: contents;
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

// StatsValue.vue's .impact-stat-count-secondary: $brand-light (#4aaebc), NOT
// $brand/#0394a6 - that darker teal is reserved for the primary-variant
// cards (background #4aaebc, white text - see .stat-card--primary above).
.stat-card__count {
  font-size: 1.75rem;
  font-weight: bold;
  color: #4aaebc;
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

// develop #946's StatsValue .impact-stat-aside: the CO2e card has the icon, figure and title on the left, and the
// comparison and share link on the right, split by a dashed line.  Stacked, with the line between them, below lg.
.stat-card--aside {
  flex-direction: column;
  align-items: stretch;

  .stat-card__main {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding-bottom: 0.75rem;
  }

  .stat-card__aside {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 3px dashed #222;
  }

  .stat-card__label {
    // Let the title wrap, rather than squeeze the description.
    max-width: 170px;
  }

  @media (min-width: 992px) {
    flex-direction: row;
    text-align: left;

    .stat-card__main {
      padding: 0 1rem 0 0;
      border-right: 3px dashed #222;
      text-align: center;
    }

    .stat-card__aside {
      flex: 1;
      padding: 0 0 0 1rem;
      border-top: none;
    }
  }
}

.stat-card__description {
  font-size: 16px;
}

.stat-card__share {
  align-self: flex-end;
}
</style>
