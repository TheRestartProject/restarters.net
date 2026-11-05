<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// group/view.blade.php's GroupDevicesWorkedOn + GroupDevicesMostRepaired
// (side by side, GroupPage.vue:41-47) - split out of GroupStats.vue so the
// page can render Events between the facts/impact stats and this section,
// matching develop's order (parity gap 13). The most-repaired list renders
// as a 1st/2nd/3rd place podium with rosette badges (GroupDeviceRepairPodium
// .vue), not a plain numbered list (parity gap 6).
const props = defineProps({
  stats: {
    type: Object,
    default: null,
  },
  // Placed in the page's compact grid: add no box of our own and let the two
  // sections be its grid items (see GroupStats.vue's compact prop).
  compact: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()

const deviceStats = computed(() => props.stats?.device_stats || null)
const topDevices = computed(() => (props.stats?.top_devices || []).slice(0, 3))

const totalDevices = computed(() =>
  deviceStats.value
    ? (deviceStats.value.fixed || 0) + (deviceStats.value.repairable || 0) + (deviceStats.value.dead || 0)
    : 0
)

// GroupDevicesWorkedOn.vue: total (brand-teal) / fixed / repairable / end-of-life.
const items = computed(() =>
  deviceStats.value
    ? [
        { icon: 'drill', count: totalDevices.value, label: t('partials.total'), testid: 'group-stats-total', primary: true },
        { icon: 'fixed', count: deviceStats.value.fixed ?? 0, label: t('partials.fixed'), testid: 'group-stats-fixed' },
        { icon: 'repairable', count: deviceStats.value.repairable ?? 0, label: t('partials.repairable'), testid: 'group-stats-repairable' },
        { icon: 'dead', count: deviceStats.value.dead ?? 0, label: t('partials.end_of_life'), testid: 'group-stats-dead' },
      ]
    : []
)

// GroupDeviceRepairPodium.vue: 2nd/1st/3rd left-to-right on desktop, with
// box height (and therefore visual "podium" stagger) tallest for 1st place.
const PODIUM_HEIGHTS = { 1: 152, 2: 132, 3: 112 }

function podiumHeight(position) {
  return `${PODIUM_HEIGHTS[position]}px`
}

// StatsValue.vue's printableCount(): stat-card counts get thousand
// separators. GroupDeviceRepairPodium.vue's device.counter is NOT run
// through StatsValue/printableCount in develop - it's a plain
// `{{ device.counter }}` interpolation - so the podium count below is left
// unformatted to match.
function printableCount(value) {
  return Math.round(value ?? 0).toLocaleString()
}
</script>

<template>
  <div class="devices-summary__row" :class="{ 'devices-summary__row--bare': compact }">
    <section v-if="deviceStats" class="stats-worked-on statsborder" data-testid="group-stats-devices">
      <h2 class="mt-2 mb-2">{{ t('groups.total_devices') }}</h2>
      <div class="stat-cards">
        <div
          v-for="card in items"
          :key="card.testid"
          class="stat-card"
          :class="{ 'stat-card--primary': card.primary }"
          :data-testid="card.testid"
        >
          <img :src="`/images/${card.icon}.svg`" alt="" class="stat-card__icon" :width="card.icon === 'drill' ? 50 : null">
          <div class="stat-card__count">{{ printableCount(card.count) }}</div>
          <div class="stat-card__label">{{ card.label }}</div>
        </div>
      </div>
    </section>

    <section class="stats-most-repaired statsborder" data-testid="group-stats-top-devices">
      <h2 class="mt-2 mb-4">{{ t('groups.most_repaired_devices') }}</h2>
      <!-- GroupDevicesMostRepaired.vue's `.d-none.d-md-block`: the staggered
           2nd/1st/3rd podium is desktop-only. -->
      <div v-if="topDevices.length" class="podium d-none d-md-flex">
        <div
          v-for="(device, index) in topDevices"
          :key="device.name + index"
          class="podium__slot"
          :style="{ order: index === 0 ? 2 : index === 1 ? 1 : 3 }"
        >
          <div
            class="podium__box"
            :style="{ height: podiumHeight(index + 1) }"
            :data-testid="`group-stats-top-device-${index}`"
          >
            <img :src="`/images/rosette_${index + 1}_ico.svg`" alt="" class="podium__rosette">
            <span class="podium__count">{{ device.counter }}</span>
            <span class="podium__name">{{ t(device.name) }}</span>
          </div>
        </div>
      </div>

      <!-- GroupDevicesMostRepaired.vue's `.d-block.d-md-none` +
           GroupDeviceRepairPodium.vue's `.mobile` grid: on mobile the three
           devices render as flat, full-width rosette/count/name rows in
           natural 1st/2nd/3rd order (no stagger, no reordering). -->
      <div v-if="topDevices.length" class="podium-mobile d-block d-md-none">
        <div
          v-for="(device, index) in topDevices"
          :key="'mobile-' + device.name + index"
          class="podium-mobile__row"
          :data-testid="`group-stats-top-device-mobile-${index}`"
        >
          <img :src="`/images/rosette_${index + 1}_ico.svg`" alt="" class="podium-mobile__rosette">
          <span class="podium-mobile__count">{{ device.counter }}</span>
          <span class="podium-mobile__name">{{ t(device.name) }}</span>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped lang="scss">
.devices-summary__row {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.devices-summary__row--bare {
  display: contents;
}

.devices-summary__row > section {
  flex: 1 1 320px;
  min-width: 0;
}

// GroupStats.vue's .statsborder: a rule above each section from md.
.statsborder {
  margin-top: 20px;

  @media (min-width: 768px) {
    border-top: 1px solid #222;
  }
}

// GroupDevicesWorkedOn.vue: two by two on mobile, four in a row from md.
.stat-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  column-gap: 20px;
  text-transform: lowercase;

  @media (min-width: 768px) {
    grid-template-columns: repeat(4, 1fr);
  }
}

// StatsValue.vue's .impact-stat, 152px tall as GroupDevicesWorkedOn.vue's
// .height, so the cards end level with the podium beside them.
.stat-card {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: 55px 54px auto;
  align-items: center;
  justify-items: center;
  height: 152px;
  margin-top: 1rem;
  padding: 5px;
  text-align: center;
  background: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px 0 0 #222;
}

// The total card is teal, with white text; the icon stays black, as on live.
.stat-card--primary {
  background: #4aaebc;

  .stat-card__count,
  .stat-card__label {
    color: #fff;
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

// GroupDevicesMostRepaired.vue + GroupDeviceRepairPodium.vue: 2nd, 1st and
// 3rd place standing on a 152px line, the rosette over the top of each.
.podium {
  display: flex;
  align-items: flex-end;
  gap: 20px;
  height: 152px;
}

.podium__slot {
  flex: 1 1 0;
  min-width: 0;
}

.podium__box {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  background: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px 0 0 #222;
  text-align: center;
  font-weight: bold;
}

.podium__rosette {
  position: absolute;
  top: 0;
  left: 50%;
  width: 40px;
  transform: translate(-20px, -22px);
}

.podium__count {
  font-size: 36px;
  color: #0394a6;
}

.podium__name {
  font-size: 80%;
  margin-bottom: 0.5rem;
}

// GroupDeviceRepairPodium.vue's `.mobile` grid + `.border-black`: a flat,
// full-width row per device - rosette / count / name columns, with a
// bottom rule between rows.
.podium-mobile__row {
  display: grid;
  grid-template-columns: 50px 4rem 1fr;
  align-items: center;
  padding-bottom: 0.5rem;
  margin-bottom: 0.5rem;
  border-bottom: 1px solid #000;
}

.podium-mobile__rosette {
  width: 40px;
  height: 40px;
}

.podium-mobile__count {
  font-size: 36px;
  font-weight: bold;
  color: var(--bs-primary, #0394a6);
}

.podium-mobile__name {
  font-weight: bold;
}
</style>
