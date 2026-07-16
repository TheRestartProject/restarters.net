<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'

// "Latest Data" banner for /fixometer - the most recent finished event with
// at least one repaired device (api-contracts-phase-c.md C6b; functional
// spec: resources/js/components/FixometerLatestData.vue).
//
// Backed by GET /api/v2/stats/latest-repaired-event (NEW, public) rather
// than the legacy inline DeviceController::index() query - see
// stores/fixometer.js. `latestData` is `{id, waste_prevented, group:
// {id, name, image}}` (GroupSummary), or null when there's genuinely no
// finished-event-with-repairs to show yet (a state the legacy Blade prop
// couldn't represent - it always had a value from a database with existing
// data - so this component adds an empty state the legacy one never
// needed).
const props = defineProps({
  latestData: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const { uploadedImageUrl } = useUploadedImageUrl()

// devices.group_prevented: " prevented <a href=\"/party/view/{idevents}\">
// {amount} kg</a> of waste!" - reused verbatim (byte-identical placeholder
// names), rendered via v-html same as the legacy component. Round up to
// avoid showing "0kg" (FixometerLatestData.vue's own comment).
const translatedWastePrevented = computed(() => {
  if (!props.latestData) return ''
  return t('devices.group_prevented', {
    idevents: props.latestData.id,
    amount: Math.ceil(props.latestData.waste_prevented),
  })
})

const groupImageUrl = computed(() => uploadedImageUrl(props.latestData?.group?.image))
</script>

<template>
  <div data-testid="latest-repairs">
    <div v-if="loading" data-testid="latest-repairs-loading">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <div v-else-if="!latestData" class="text-muted" data-testid="latest-repairs-empty">
      {{ t('client.fixometer.no_latest_repairs') }}
    </div>

    <div v-else class="border p-3" data-testid="latest-repairs-content">
      <div class="fw-bold mb-2">{{ t('devices.latest_data') }}</div>
      <div class="d-flex align-items-center gap-2">
        <img
          v-if="groupImageUrl"
          :src="groupImageUrl"
          alt=""
          width="32"
          height="32"
          class="rounded-circle"
          data-testid="latest-repairs-group-image"
        >
        <div>
          <NuxtLink :to="`/group/view/${latestData.group.id}`" data-testid="latest-repairs-group-link">
            {{ latestData.group.name }}
          </NuxtLink>
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="translatedWastePrevented" />
        </div>
      </div>
    </div>
  </div>
</template>
