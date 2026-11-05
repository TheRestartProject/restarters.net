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
        <section v-if="groupStats" class="group-stats__col stats-facts statsborder" data-testid="group-stats-facts">
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

        <section v-if="groupStats" class="group-stats__col stats-impact statsborder" data-testid="group-stats-impact">
          <h2 class="mt-2 mb-2">
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
                  <!-- eslint-disable-next-line vue/no-v-html -->
                  <span data-testid="group-stats-co2-description" v-html="co2Description" />
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

// GroupStats.vue's .statsborder: a rule above each section from md.
.statsborder {
  margin-top: 20px;

  @media (min-width: 768px) {
    border-top: 1px solid #222;
  }
}

// GroupStatsFacts.vue: the events card spans the row on mobile, with
// participants and hours below it; three equal columns from md.
.stat-cards--facts {
  display: grid;
  grid-template-columns: 1fr 1fr;
  column-gap: 20px;

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

// StatsImpact.vue: waste and CO2 stacked on mobile, 1fr:2fr from md.
.stat-cards--impact {
  display: grid;
  grid-template-columns: 1fr;
  column-gap: 20px;

  @media (min-width: 768px) {
    grid-template-columns: 1fr 2fr;
  }
}

// StatsValue.vue's .impact-stat: icon, figure and title in fixed rows, so the
// cards in a row line up; a 1rem gap above each card, under the heading.
.stat-card {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: 55px 54px auto;
  align-items: center;
  justify-items: center;
  margin-top: 1rem;
  padding: 5px;
  text-align: center;
  background: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px 0 0 #222;
}

// The first card of each section is teal, with white text; the icon stays black, as on live.
.stat-card--primary {
  background: #4aaebc;

  .stat-card__count,
  .stat-card__label {
    color: #fff;
  }

  // Horizontal below md, as StatsValue.vue's primary variant.
  @media (max-width: 767.98px) {
    display: flex;
    justify-content: center;
    align-items: center;

    .stat-card__icon,
    .stat-card__count {
      margin-right: 0.5rem;
    }
  }
}

.stat-card__icon {
  align-self: start;
  height: 46px;
  margin-top: 0.5rem;
}

.stat-card__count {
  font-family: 'Asap', sans-serif;
  font-size: 36px;
  font-weight: bold;
  color: #4aaebc;
}

.stat-card__label {
  font-family: 'Asap', sans-serif;
  font-size: 18px;
  font-weight: bold;
}

// StatsImpact.vue's info-icon popover trigger: an inline, borderless icon
// button next to the "Environmental impact" heading.
.info-toggle {
  border: 0;
  background: none;
  padding: 0;
  margin-left: 0.5rem;
  vertical-align: middle;
}

// StatsValue.vue's .impact-stat-aside: the CO2e card has the icon, figure and
// title on the left, and the comparison and share link on the right, split by
// a dashed line.  Stacked, with the line between them, below lg.
.stat-card--aside {
  display: flex;
  flex-direction: column;
  align-items: stretch;

  @media (min-width: 768px) {
    margin-left: 0;
  }

  .stat-card__main {
    display: grid;
    grid-template-rows: 55px 54px auto;
    align-items: center;
    justify-items: center;
    padding-bottom: 0.5rem;
  }

  .stat-card__aside {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.5rem;
    padding-top: 0.5rem;
    border-top: 3px dashed #222;
  }

  .stat-card__label {
    max-width: 170px;
  }

  @media (min-width: 992px) {
    flex-direction: row;

    .stat-card__main {
      padding: 0 1rem 0 0;
      border-right: 3px dashed #222;
    }

    .stat-card__aside {
      flex: 1;
      justify-content: flex-end;
      padding: 0 0 0 1rem;
      border-top: none;
    }

    .stat-card__description {
      flex: 1;
      display: flex;
      align-items: flex-end;
    }
  }
}

.stat-card__description {
  font-family: 'Asap', sans-serif;
  font-size: 16px;
  align-items: center;
}

.stat-card__share {
  align-self: flex-end;
  padding: 0;
  font-size: 0.875rem;
  color: #0394a6;
}
</style>
