<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFixometerStore } from '~/stores/fixometer.js'
import ImpactStats from '~/components/fixometer/ImpactStats.vue'
import DevicesSearchTable from '~/components/fixometer/DevicesSearchTable.vue'
import AddDataModal from '~/components/fixometer/AddDataModal.vue'

// /fixometer: the heading with its "Add Data" button, the global impact stats, and the Repair records - a download
// link plus the filterable list of powered and unpowered items (DevicesSearchTable).
//
// Auth-gated: the live site redirects anonymous visitors to /login.
definePageMeta({ auth: true })
const { t } = useI18n()
useHead({ title: t('devices.fixometer') })

const fixometerStore = useFixometerStore()

// "Add Data" is offered to everyone and opens an in-page group/event picker; AddDataModal handles the logged-out
// and no-groups cases.
const showAddData = ref(false)

// Set by DevicesSearchTable once its filters narrow the records; null otherwise.
const filteredDownloadUrl = ref(null)

// The live total of the list, once its search has landed; until then the sitewide total.
const listTotal = ref(null)
const total = computed(() => {
  if (listTotal.value !== null) {
    return listTotal.value
  }

  const data = fixometerStore.impactData.data

  return (data?.total_powered || 0) + (data?.total_unpowered || 0)
})

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
        <!-- The compiler condenses the whitespace between the two, so the space has to be explicit. -->
        {{ ' ' }}
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-html="t('devices.huge_impact_2')" />
      </p>

      <ImpactStats
        :impact-data="fixometerStore.impactData.data"
        :loading="fixometerStore.impactData.loading"
        :latest-data="fixometerStore.latestRepaired.data"
        :latest-loading="fixometerStore.latestRepaired.loading"
        :reporting-url="fixometerStore.impactData.data?.reporting_url || null"
        class="mb-4"
      />

      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h2>
            {{ t('devices.repair_records') }}
            <span class="records-total" data-testid="records-total">({{ total.toLocaleString() }})</span>
          </h2>
          <p>{{ t('devices.search_text') }}</p>
        </div>
        <div class="d-flex gap-2">
          <!-- Everyone can download the repair data: the file built overnight when there is one, else the live export. -->
          <a
            :href="fixometerStore.impactData.data?.download_url || '/export/devices'"
            class="btn btn-primary"
            data-testid="fixometer-export-devices"
          >
            {{ t('devices.export_device_data') }}
          </a>
          <!-- The same export narrowed to the records the table's filters show. -->
          <a
            v-if="filteredDownloadUrl"
            :href="filteredDownloadUrl"
            class="btn btn-secondary"
            data-testid="fixometer-export-devices-filtered"
          >
            {{ t('devices.export_filtered_data') }}
          </a>
        </div>
      </div>

      <DevicesSearchTable
        v-model:filtered-download-url="filteredDownloadUrl"
        v-model:total="listTotal"
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
