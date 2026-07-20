<script setup>
import { useI18n } from 'vue-i18n'
import GroupCollapsibleSection from './GroupCollapsibleSection.vue'

// group/view.blade.php's device-category breakdown (GroupDevicesBreakdown
// .vue + GroupDevicesBreakdownCluster.vue): a BTabs on desktop (one cluster
// visible at a time) and a collapsible accordion on mobile (first cluster
// expanded, the rest collapsed) - not four boxes shown simultaneously
// (parity gap 7). Each cluster panel shows fixed/repairable/dead plus
// most-seen/most-repaired/least-repaired as icon-topped stat items rather
// than plain text lines (parity gap 8).
defineProps({
  clusterStats: {
    type: Object,
    default: null,
  },
})

const { t } = useI18n()

const CLUSTERS = [
  { id: 1, key: 'client.groups.cluster_computers' },
  { id: 2, key: 'client.groups.cluster_gadgets' },
  { id: 3, key: 'client.groups.cluster_entertainment' },
  { id: 4, key: 'client.groups.cluster_household' },
]

function translateName(name) {
  return name ? t(name) : ''
}
</script>

<template>
  <section v-if="clusterStats" data-testid="group-stats-clusters">
    <h2 class="mt-4">{{ t('groups.device_breakdown') }}</h2>

    <BTabs class="d-none d-md-block mt-3" justified data-testid="group-stats-clusters-tabs">
      <BTab v-for="cluster in CLUSTERS" :key="cluster.id" :title="t(cluster.key)">
        <div class="cluster-panel" :data-testid="`group-stats-cluster-${cluster.id}`">
          <template v-if="clusterStats[cluster.id]">
            <div class="cluster-panel__item">
              <img :src="'/images/fixed.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].fixed ?? 0 }}</div>
              <div class="small">{{ t('partials.fixed') }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/repairable.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].repairable ?? 0 }}</div>
              <div class="small">{{ t('partials.repairable') }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/dead.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].dead ?? 0 }}</div>
              <div class="small">{{ t('partials.end_of_life') }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/most-seen_ico.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].most_seen?.count ?? 0 }}</div>
              <div class="small">{{ translateName(clusterStats[cluster.id].most_seen?.name) }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/most-repaired_ico.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].most_repaired?.count ?? 0 }}</div>
              <div class="small">{{ translateName(clusterStats[cluster.id].most_repaired?.name) }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/least-repaired_ico.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].least_repaired?.count ?? 0 }}</div>
              <div class="small">{{ translateName(clusterStats[cluster.id].least_repaired?.name) }}</div>
            </div>
          </template>
        </div>
      </BTab>
    </BTabs>

    <div class="d-block d-md-none mt-3">
      <GroupCollapsibleSection
        v-for="(cluster, index) in CLUSTERS"
        :key="cluster.id"
        :collapsed="index !== 0"
        class="mb-2"
      >
        <template #title>
          <span class="cluster-mobile-title">{{ t(cluster.key) }}</span>
        </template>
        <div class="cluster-panel" :data-testid="`group-stats-cluster-${cluster.id}-mobile`">
          <template v-if="clusterStats[cluster.id]">
            <div class="cluster-panel__item">
              <img :src="'/images/fixed.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].fixed ?? 0 }}</div>
              <div class="small">{{ t('partials.fixed') }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/repairable.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].repairable ?? 0 }}</div>
              <div class="small">{{ t('partials.repairable') }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/dead.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].dead ?? 0 }}</div>
              <div class="small">{{ t('partials.end_of_life') }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/most-seen_ico.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].most_seen?.count ?? 0 }}</div>
              <div class="small">{{ translateName(clusterStats[cluster.id].most_seen?.name) }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/most-repaired_ico.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].most_repaired?.count ?? 0 }}</div>
              <div class="small">{{ translateName(clusterStats[cluster.id].most_repaired?.name) }}</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/least-repaired_ico.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].least_repaired?.count ?? 0 }}</div>
              <div class="small">{{ translateName(clusterStats[cluster.id].least_repaired?.name) }}</div>
            </div>
          </template>
        </div>
      </GroupCollapsibleSection>
    </div>

    <p class="small text-muted mt-3">{{ t('groups.no_unpowered_stats') }}</p>
  </section>
</template>

<style scoped lang="scss">
.cluster-panel {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1rem 0;
}

.cluster-panel__item {
  flex: 1 1 90px;
  min-width: 80px;
  text-align: center;
}

.cluster-panel__icon {
  height: 32px;
}

.cluster-panel__count {
  font-weight: bold;
  font-size: 1.25rem;
  color: var(--bs-primary, #0394a6);
}

.cluster-mobile-title {
  text-transform: uppercase;
  font-size: 1.1rem;
  font-weight: bold;
}
</style>
