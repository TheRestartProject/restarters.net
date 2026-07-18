<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFixometerStore } from '~/stores/fixometer.js'
import { useAuth } from '~/composables/useAuth.js'
import ImpactStats from '~/components/fixometer/ImpactStats.vue'
import DevicesSearchTable from '~/components/fixometer/DevicesSearchTable.vue'

// /fixometer - resources/views/fixometer/index.blade.php +
// resources/js/components/FixometerPage.vue are the functional spec
// (api-contracts-phase-c.md C6b; design.md §6.2 C6 task brief; git show
// 07e6abd7cc^ for both, the commit before Phase F deleted the Blade + Vue 2
// frontend). Public, no auth gate - DeviceController::index() works for
// guests too (isAdmin false, user_groups empty when logged out).
//
// Legacy top-to-bottom structure, replicated here: FixometerHeading (h1 +
// doodle + "Add Data" button), a thick <hr>, the "Our Global Impact" h2 +
// intro paragraph, FixometerGlobalImpact's stat-card grid (ImpactStats.vue,
// which nests the "Latest Data" card the same way the legacy component
// did), then the Repair Records section - a "Download all data" export
// link plus the powered/unpowered filterable table
// (FixometerRecordsTable.vue via FixometerFilters.vue), embedded directly
// on this page rather than switched by legacy's b-tabs. DevicesSearchTable
// also exists standalone at /device/search (see that page's doc comment) -
// this page embeds the same component inline to match the legacy's
// single-page layout, and keeps a link through to the standalone page for
// anyone who wants a page of their own to bookmark/share.
const { t } = useI18n()
useHead({ title: t('devices.fixometer') })

const fixometerStore = useFixometerStore()
const { loggedIn, hasRole } = useAuth()

function retry() {
  fixometerStore.fetchImpactData({ force: true })
  fixometerStore.fetchLatestRepaired({ force: true })
}

onMounted(() => {
  fixometerStore.fetchImpactData()
  fixometerStore.fetchLatestRepaired()
})
</script>

<template>
  <div class="container py-4" data-testid="fixometer-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div class="d-flex align-items-center gap-3">
        <h1 class="mb-0">{{ t('devices.fixometer') }}</h1>
        <img src="/images/fixometer-doodle.svg" class="d-none d-md-block fixometer-doodle" alt="">
      </div>
      <!--
        FixometerHeading.vue's "Add Data" button is unconditional in the
        legacy app (AddDataModal.vue self-guides logged-out/groupless users
        to "follow a group first"). Porting that full group/event picker
        modal is out of scope here - see docs/nuxt-migration/api-gaps.md -
        so this links straight to /dashboard (where a host/admin's own
        groups and events are already listed) and is only shown once
        logged in, a deliberate simplification.
      -->
      <NuxtLink v-if="loggedIn" to="/dashboard" class="btn btn-primary" data-testid="fixometer-add-data">
        {{ t('devices.add_data_button') }}
      </NuxtLink>
    </div>

    <hr class="fixometer-hr">

    <BAlert
      v-if="fixometerStore.impactData.error"
      :model-value="true"
      variant="danger"
      data-testid="fixometer-error"
    >
      <p>{{ t('client.fixometer.load_error') }}</p>
      <BButton variant="danger" data-testid="fixometer-retry" @click="retry">
        {{ t('client.dashboard.retry') }}
      </BButton>
    </BAlert>

    <template v-else>
      <h2>{{ t('devices.global_impact') }}</h2>
      <p>
        <b>{{ t('devices.huge_impact') }}</b>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-html="t('devices.huge_impact_2')" />
      </p>

      <ImpactStats
        :impact-data="fixometerStore.impactData.data"
        :loading="fixometerStore.impactData.loading"
        :latest-data="fixometerStore.latestRepaired.data"
        :latest-loading="fixometerStore.latestRepaired.loading"
        class="mb-4"
      />

      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h2>{{ t('devices.repair_records') }}</h2>
          <p>{{ t('devices.search_text') }}</p>
        </div>
        <div class="d-flex gap-2">
          <!-- FixometerPage.vue: v-if="isAdmin" - export/devices is a public,
               anonymous-access web route (routes/web.php - "also called from
               https://therestartproject.org/download-dataset"), but the
               legacy button itself is Administrator-gated, so this matches
               that rather than the route's own (looser) access. -->
          <a
            v-if="hasRole('Administrator')"
            href="/export/devices"
            class="btn btn-outline-primary"
            data-testid="fixometer-export-devices"
          >
            {{ t('devices.export_device_data') }}
          </a>
          <NuxtLink to="/device/search" class="btn btn-outline-primary" data-testid="fixometer-browse-records">
            {{ t('client.fixometer.browse_records') }}
          </NuxtLink>
        </div>
      </div>

      <DevicesSearchTable
        :powered-count="fixometerStore.impactData.data?.total_powered ?? null"
        :unpowered-count="fixometerStore.impactData.data?.total_unpowered ?? null"
      />
    </template>
  </div>
</template>

<style scoped>
.fixometer-hr {
  border-top: 5px solid #222;
  opacity: 1;
}

.fixometer-doodle {
  height: 60px;
}
</style>
