<script setup>
import { useI18n } from 'vue-i18n'
import DevicesSearchTable from '~/components/fixometer/DevicesSearchTable.vue'

// /device/search - api-contracts-phase-c.md C6a: "the legacy /device/search
// route is dead" (routes/web.php:340 -> DeviceController::search, which the
// contract doc flags as not worth porting markup-for-markup) - build fresh
// against GET /api/v2/devices per the contract. Functional spec for the
// filter/column set is FixometerFilters.vue + FixometerRecordsTable.vue
// (previously embedded in /fixometer, now its own page - see fixometer.vue's
// doc comment). Public, no auth gate (same as /fixometer).
const { t } = useI18n()
useHead({ title: t('devices.repair_records') })
</script>

<template>
  <div class="container py-4" data-testid="device-search-page">
    <h1>{{ t('devices.repair_records') }}</h1>
    <p>{{ t('devices.search_text') }}</p>

    <DevicesSearchTable />
  </div>
</template>
