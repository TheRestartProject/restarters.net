<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// GET /api/v2/groups/{id}/stats (api-contracts-phase-b.md B2, not yet
// implemented server-side). Functional spec: group/view.blade.php's
// $group_stats/$device_stats/$cluster_stats/$top passed into GroupStats.vue
// + GroupStatsFacts/GroupDevicesWorkedOn/GroupDevicesMostRepaired/
// GroupDevicesBreakdown.vue - folded into one component here (replicate
// features/strings, not markup - see task brief).
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

const { t } = useI18n()

const CLUSTERS = [
  { id: 1, key: 'client.groups.cluster_computers' },
  { id: 2, key: 'client.groups.cluster_gadgets' },
  { id: 3, key: 'client.groups.cluster_entertainment' },
  { id: 4, key: 'client.groups.cluster_household' },
]

const groupStats = computed(() => props.stats?.group_stats || null)
const deviceStats = computed(() => props.stats?.device_stats || null)
const clusterStats = computed(() => props.stats?.cluster_stats || null)
const topDevices = computed(() => props.stats?.top_devices || [])

const totalDevices = computed(() => {
  if (!deviceStats.value) return 0
  return (deviceStats.value.fixed || 0) + (deviceStats.value.repairable || 0) + (deviceStats.value.dead || 0)
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
      <section v-if="groupStats" data-testid="group-stats-facts" class="mb-4">
        <h2>{{ t('groups.group_facts') }}</h2>
        <div class="d-flex flex-wrap gap-4">
          <div data-testid="group-stats-parties">
            <div class="h3 mb-0">{{ groupStats.parties ?? 0 }}</div>
            <div class="small text-muted">{{ t('groups.events') }}</div>
          </div>
          <div data-testid="group-stats-participants">
            <div class="h3 mb-0">{{ groupStats.participants ?? 0 }}</div>
            <div class="small text-muted">{{ t('groups.participants') }}</div>
          </div>
          <div data-testid="group-stats-hours">
            <div class="h3 mb-0">{{ groupStats.hours_volunteered ?? 0 }}</div>
            <div class="small text-muted">{{ t('groups.hours_volunteered') }}</div>
          </div>
        </div>
      </section>

      <section v-if="deviceStats" data-testid="group-stats-devices" class="mb-4">
        <h2>{{ t('groups.total_devices') }}</h2>
        <div class="d-flex flex-wrap gap-4">
          <div data-testid="group-stats-total">
            <div class="h3 mb-0">{{ totalDevices }}</div>
            <div class="small text-muted">{{ t('partials.total') }}</div>
          </div>
          <div data-testid="group-stats-fixed">
            <div class="h3 mb-0">{{ deviceStats.fixed ?? 0 }}</div>
            <div class="small text-muted">{{ t('partials.fixed') }}</div>
          </div>
          <div data-testid="group-stats-repairable">
            <div class="h3 mb-0">{{ deviceStats.repairable ?? 0 }}</div>
            <div class="small text-muted">{{ t('partials.repairable') }}</div>
          </div>
          <div data-testid="group-stats-dead">
            <div class="h3 mb-0">{{ deviceStats.dead ?? 0 }}</div>
            <div class="small text-muted">{{ t('partials.end_of_life') }}</div>
          </div>
        </div>
      </section>

      <section v-if="topDevices.length" data-testid="group-stats-top-devices" class="mb-4">
        <h2>{{ t('groups.most_repaired_devices') }}</h2>
        <ol>
          <li
            v-for="(device, index) in topDevices.slice(0, 3)"
            :key="device.name + index"
            :data-testid="`group-stats-top-device-${index}`"
          >
            {{ t(device.name) }} - {{ device.counter }}
          </li>
        </ol>
      </section>

      <section v-if="clusterStats" data-testid="group-stats-clusters">
        <h2>{{ t('groups.device_breakdown') }}</h2>
        <div
          v-for="cluster in CLUSTERS"
          :key="cluster.id"
          class="mb-3"
          :data-testid="`group-stats-cluster-${cluster.id}`"
        >
          <h3>{{ t(cluster.key) }}</h3>
          <template v-if="clusterStats[cluster.id]">
            <div class="d-flex flex-wrap gap-3 small">
              <span>{{ t('partials.total') }}: {{ clusterStats[cluster.id].total ?? 0 }}</span>
              <span>{{ t('partials.fixed') }}: {{ clusterStats[cluster.id].fixed ?? 0 }}</span>
              <span>{{ t('partials.repairable') }}: {{ clusterStats[cluster.id].repairable ?? 0 }}</span>
              <span>{{ t('partials.end_of_life') }}: {{ clusterStats[cluster.id].dead ?? 0 }}</span>
            </div>
            <div class="small text-muted">
              <span v-if="clusterStats[cluster.id].most_seen?.name">
                {{ t('partials.most_seen') }}: {{ clusterStats[cluster.id].most_seen.name }}
                ({{ clusterStats[cluster.id].most_seen.count }})
              </span>
              <span v-if="clusterStats[cluster.id].most_repaired?.name" class="ms-2">
                {{ t('partials.most_repaired') }}: {{ clusterStats[cluster.id].most_repaired.name }}
                ({{ clusterStats[cluster.id].most_repaired.count }})
              </span>
              <span v-if="clusterStats[cluster.id].least_repaired?.name" class="ms-2">
                {{ t('partials.least_repaired') }}: {{ clusterStats[cluster.id].least_repaired.name }}
                ({{ clusterStats[cluster.id].least_repaired.count }})
              </span>
            </div>
          </template>
        </div>
        <p class="small text-muted">{{ t('groups.no_unpowered_stats') }}</p>
      </section>
    </template>
  </div>
</template>
