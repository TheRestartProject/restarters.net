<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFixometerStore } from '~/stores/fixometer.js'
import { useAuth } from '~/composables/useAuth.js'
import ImpactStats from '~/components/fixometer/ImpactStats.vue'
import DevicesSearchTable from '~/components/fixometer/DevicesSearchTable.vue'
import AddDataModal from '~/components/fixometer/AddDataModal.vue'

// /fixometer - resources/views/fixometer/index.blade.php +
// resources/js/components/FixometerPage.vue are the functional spec
// (api-contracts-phase-c.md C6b; design.md §6.2 C6 task brief; git show
// 07e6abd7cc^ for both, the commit before Phase F deleted the Blade + Vue 2
// frontend).
//
// AUTH-GATED. The legacy route sat inside
// `Route::middleware('auth', 'verifyUserConsent', 'ensureAPIToken')`
// (routes/web.php at 07e6abd7cc^), and the live site still 302s anonymous
// visitors to /login. A previous comment here asserted this page was "public,
// no auth gate" because DeviceController::index() tolerates guests - that
// conflated "the API tolerates logged-out callers" with "the page was reachable
// logged out", and left the page open to anonymous visitors.
definePageMeta({ auth: true })
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
// single-page layout. Gap fix: a prior version also added a "Browse repair
// records" button linking through to /device/search here, but legacy's
// FixometerPage.vue header row has only the (Administrator-only) export
// button - browsing already happens in place, via the embedded table below.
const { t } = useI18n()
useHead({ title: t('devices.fixometer') })

const fixometerStore = useFixometerStore()
const { hasRole } = useAuth()

// Gap fix (HIGH): FixometerHeading.vue's "Add Data" button is unconditional
// (rendered even logged-out) and opens AddDataModal.vue, an in-page group/
// event picker - a prior version gated the button on `loggedIn` and linked
// straight to /dashboard instead. AddDataModal.vue itself handles the
// logged-out/no-groups case (same warning branch legacy shows either way).
const showAddData = ref(false)

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
      <BButton variant="primary" data-testid="fixometer-add-data" @click="showAddData = true">
        {{ t('devices.add_data_button') }}
      </BButton>
    </div>

    <AddDataModal :show="showAddData" @close="showAddData = false" />

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
               that rather than the route's own (looser) access. No separate
               "browse records" link/button here - legacy's FixometerPage.vue
               header row has only this one button; browsing IS the
               DevicesSearchTable embedded directly below, not a route away. -->
          <a
            v-if="hasRole('Administrator')"
            href="/export/devices"
            class="btn btn-primary"
            data-testid="fixometer-export-devices"
          >
            {{ t('devices.export_device_data') }}
          </a>
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
