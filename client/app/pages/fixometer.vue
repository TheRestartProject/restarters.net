<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFixometerStore } from '~/stores/fixometer.js'
import ImpactStats from '~/components/fixometer/ImpactStats.vue'
import LatestRepairs from '~/components/fixometer/LatestRepairs.vue'

// /fixometer - resources/views/fixometer/index.blade.php +
// resources/js/components/FixometerPage.vue are the functional spec
// (api-contracts-phase-c.md C6b; design.md §6.2 C6 task brief). Public, no
// auth gate - DeviceController::index() works for guests too (isAdmin
// false, user_groups empty when logged out).
//
// The legacy page also embedded the powered/unpowered device records table
// (FixometerRecordsTable.vue) directly below the impact stats. Per the C6
// task brief this becomes its own page, /device/search
// (components/fixometer/DevicesSearchTable.vue) - a judgment call recorded
// in docs/nuxt-migration/api-gaps.md, since the two pages now need a link
// between them where the legacy app just had tabs on one page.
const { t } = useI18n()
useHead({ title: t('devices.fixometer') })

const fixometerStore = useFixometerStore()

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
    <h1>{{ t('devices.fixometer') }}</h1>

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
      <LatestRepairs
        :latest-data="fixometerStore.latestRepaired.data"
        :loading="fixometerStore.latestRepaired.loading"
        class="mb-4"
      />

      <ImpactStats
        :impact-data="fixometerStore.impactData.data"
        :loading="fixometerStore.impactData.loading"
        class="mb-4"
      />

      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h2>{{ t('devices.repair_records') }}</h2>
          <p>{{ t('devices.search_text') }}</p>
        </div>
        <NuxtLink to="/device/search" class="btn btn-primary" data-testid="fixometer-browse-records">
          {{ t('client.fixometer.browse_records') }}
        </NuxtLink>
      </div>
    </template>
  </div>
</template>
