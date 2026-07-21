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
</script>

<template>
  <div class="devices-summary__row">
    <section v-if="deviceStats" data-testid="group-stats-devices">
      <h2 class="mt-2 mb-2">{{ t('groups.total_devices') }}</h2>
      <div class="stat-cards">
        <div
          v-for="card in items"
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

    <section data-testid="group-stats-top-devices">
      <h2 class="mt-2 mb-2">{{ t('groups.most_repaired_devices') }}</h2>
      <div v-if="topDevices.length" class="podium">
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

.devices-summary__row > section {
  flex: 1 1 320px;
  min-width: 0;
}

.stat-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.stat-card {
  flex: 1 1 0;
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
  /* GroupDevicesWorkedOn.vue wraps these cards in .text-lowercase - "total"
     is already lowercase in the lang string, but "Fixed"/"Repairable"/
     "End-of-life" need the CSS transform to match (partials.php keeps them
     capitalised for reuse elsewhere, e.g. device tables). */
  text-transform: lowercase;
}

// GroupDeviceRepairPodium.vue: three boxes, 1st place tallest, laid out
// left-to-right as 2nd/1st/3rd via the `order` style set above.
.podium {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
}

.podium__slot {
  flex: 1 1 0;
  min-width: 0;
}

.podium__box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 0.25rem;
  padding: 0.75rem 0.5rem 1rem;
  background: #fff;
  border: 1px solid #222;
  box-shadow: 5px 5px 0 #222;
  text-align: center;
}

.podium__rosette {
  width: 40px;
  height: 40px;
  margin-top: -1.75rem;
}

.podium__count {
  font-size: 1.75rem;
  font-weight: bold;
  color: var(--bs-primary, #0394a6);
}

.podium__name {
  font-weight: bold;
  font-size: 0.9rem;
}
</style>
