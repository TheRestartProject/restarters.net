<script setup>
import { useI18n } from 'vue-i18n'
import DevicesSearchTable from '~/components/fixometer/DevicesSearchTable.vue'

// /device/search: the Repair records list on its own page, the same DevicesSearchTable the Fixometer embeds.
//
// Auth-gated like /fixometer.
definePageMeta({ auth: true })

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
