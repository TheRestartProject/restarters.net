<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'

// "Latest Data" grid card for /fixometer - the most recent finished event
// with at least one repaired device (api-contracts-phase-c.md C6b;
// functional spec: resources/js/components/FixometerLatestData.vue, git show
// 07e6abd7cc^). Rendered inside ImpactStats.vue's stat-card grid (legacy
// nested this the same way, inside FixometerGlobalImpact.vue's own grid) -
// kept as its own component since it's independently unit-tested and has
// its own loading/empty states.
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
  <div data-testid="latest-repairs" class="latest-repairs">
    <div v-if="loading" data-testid="latest-repairs-loading" class="latest-repairs__placeholder">
      <div class="placeholder-glow">
        <span class="placeholder col-12" style="height: 3rem" />
      </div>
    </div>

    <div v-else-if="!latestData" class="text-muted latest-repairs__placeholder" data-testid="latest-repairs-empty">
      {{ t('client.fixometer.no_latest_repairs') }}
    </div>

    <div v-else class="latest-repairs__card" data-testid="latest-repairs-content">
      <div class="latest-repairs__title">
        {{ t('devices.latest_data') }}
        <img src="/images/clap-doodle.svg" class="latest-repairs__icon" alt="">
      </div>
      <div class="latest-repairs__description">
        <img
          v-if="groupImageUrl"
          :src="groupImageUrl"
          alt=""
          width="32"
          height="32"
          class="rounded-circle me-2"
          data-testid="latest-repairs-group-image"
        >
        <NuxtLink :to="`/group/view/${latestData.group.id}`" data-testid="latest-repairs-group-link">
          {{ latestData.group.name }}
        </NuxtLink>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <span v-html="translatedWastePrevented" />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
// FixometerLatestData.vue's .fld-layout: filled with the lighter brand teal
// ($brand-light #4aaebc), black text/border - the one card in the grid that
// keeps the legacy's literal colours rather than ImpactStats.vue's darker
// teal outline, since this is the "hero" card the legacy design deliberately
// set apart from the rest of the grid.
.latest-repairs,
.latest-repairs__placeholder {
  height: 100%;
}

.latest-repairs__card {
  height: 100%;
  padding: 1rem;
  background-color: #4aaebc;
  color: #222;
  border: 1px solid #222;
  box-shadow: 4px 4px 0 #222;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.latest-repairs__title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: bold;
}

.latest-repairs__icon {
  height: 32px;
}

.latest-repairs__description {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 3px dashed #222;
  font-weight: bold;
}

.latest-repairs__description :deep(a) {
  color: #222;
  text-decoration: underline;
}
</style>
