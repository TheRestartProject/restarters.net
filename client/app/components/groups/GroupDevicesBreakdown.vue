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

// GroupDevicesBreakdownCluster.vue's pc(): fixed/repairable/dead render as
// a percentage of the cluster's own total, not the raw count's word label
// (most-seen/most-repaired/least-repaired keep their name subtitle - only
// this trio switches to a percentage).
function pc(cluster, count) {
  if (!cluster) return 0
  const total = (cluster.fixed ?? 0) + (cluster.repairable ?? 0) + (cluster.dead ?? 0)
  return total ? Math.round((10000 * count) / total) / 100 : 0
}
</script>

<template>
  <section v-if="clusterStats" data-testid="group-stats-clusters">
    <h2 class="mt-4">{{ t('groups.device_breakdown') }}</h2>

    <!-- GroupDevicesBreakdown.vue's <b-tabs>: bordered/drop-shadowed box
         with UPPERCASE cluster labels, same chrome as the events tabs box
         in GroupEventsList.vue - not bare title-case tabs. -->
    <div class="cluster-tabs-box d-none d-md-block mt-3">
      <BTabs justified data-testid="group-stats-clusters-tabs">
        <BTab v-for="cluster in CLUSTERS" :key="cluster.id" :title="t(cluster.key)">
          <div class="cluster-panel" :data-testid="`group-stats-cluster-${cluster.id}`">
            <template v-if="clusterStats[cluster.id]">
              <div class="cluster-panel__item">
                <img :src="'/images/fixed.svg'" alt="" class="cluster-panel__icon">
                <div class="cluster-panel__count">{{ clusterStats[cluster.id].fixed ?? 0 }}</div>
                <div class="small">{{ pc(clusterStats[cluster.id], clusterStats[cluster.id].fixed ?? 0) }}%</div>
              </div>
              <div class="cluster-panel__item">
                <img :src="'/images/repairable.svg'" alt="" class="cluster-panel__icon">
                <div class="cluster-panel__count">{{ clusterStats[cluster.id].repairable ?? 0 }}</div>
                <div class="small">{{ pc(clusterStats[cluster.id], clusterStats[cluster.id].repairable ?? 0) }}%</div>
              </div>
              <div class="cluster-panel__item">
                <img :src="'/images/dead.svg'" alt="" class="cluster-panel__icon">
                <div class="cluster-panel__count">{{ clusterStats[cluster.id].dead ?? 0 }}</div>
                <div class="small">{{ pc(clusterStats[cluster.id], clusterStats[cluster.id].dead ?? 0) }}%</div>
              </div>
              <!-- GroupDevicesBreakdownCluster.vue's .divider: a teal rule
                   between the fixed/repairable/dead trio and the most-seen/
                   most-repaired/least-repaired trio, desktop only. -->
              <div class="cluster-panel__divider d-none d-md-block" :data-testid="`group-stats-cluster-${cluster.id}-divider`" />
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
    </div>

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
              <div class="small">{{ pc(clusterStats[cluster.id], clusterStats[cluster.id].fixed ?? 0) }}%</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/repairable.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].repairable ?? 0 }}</div>
              <div class="small">{{ pc(clusterStats[cluster.id], clusterStats[cluster.id].repairable ?? 0) }}%</div>
            </div>
            <div class="cluster-panel__item">
              <img :src="'/images/dead.svg'" alt="" class="cluster-panel__icon">
              <div class="cluster-panel__count">{{ clusterStats[cluster.id].dead ?? 0 }}</div>
              <div class="small">{{ pc(clusterStats[cluster.id], clusterStats[cluster.id].dead ?? 0) }}%</div>
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
// Same "brutalist" box border + hard drop-shadow as GroupEventsList.vue's
// .events-tabs (the sitewide .box-shadow-offset formula from _type.scss),
// with the nav recoloured from Bootstrap's default grey/rounded pills to
// square black-bordered, UPPERCASE tabs, and the same active-tab 5px top
// bar ported from _tabs.scss's .nav-tabs-block.
.cluster-tabs-box {
  background: #fff;
  border: 1px solid #222;
  box-shadow: 6px 6px 0 0 #222;
}

.cluster-tabs-box :deep(.nav-tabs) {
  border-bottom: 0;
}

.cluster-tabs-box :deep(.nav-link) {
  position: relative;
  border: 0;
  border-right: 1px solid #222;
  border-bottom: 1px solid #222;
  border-radius: 0;
  background: #f9f9f9;
  color: #555;
  font-weight: 700;
  text-transform: uppercase;
}

.cluster-tabs-box :deep(.nav-item:last-child .nav-link) {
  border-right: 0;
}

.cluster-tabs-box :deep(.nav-link.active) {
  background: #fff;
  color: #222;
  border-bottom-color: #fff;
}

.cluster-tabs-box :deep(.nav-link.active)::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 5px;
  background: #222;
}

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

// GroupDevicesBreakdownCluster.vue's .divider (desktop): a thin brand-light
// rule separating the fixed/repairable/dead trio from the most-seen/
// most-repaired/least-repaired trio.
.cluster-panel__divider {
  align-self: stretch;
  flex: 0 0 auto;
  width: 1px;
  margin: 0 0.5rem;
  background: #4aaebc;
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
