<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { STATS_SHARE_PLATFORMS } from '../../composables/useStatsShareImage.js'
import StatsShareImage from './StatsShareImage.vue'

// Port of resources/js/components/StatsShareModal.vue: the Instagram/
// Facebook/Twitter/LinkedIn toggle + Close/Download footer wrapping
// StatsShareImage.vue's canvas painter. This is the CO2-card "Share this"
// button's own modal (StatsImpact.vue's `share` handler) - a different,
// separate feature from EventShareStatsModal.vue/GroupShareStatsModal.vue's
// embed-code modal, which is what the page header's "Share event/group
// stats" action opens. Shared unchanged between the event and group pages,
// same as develop: only the `count` prop differs between callers.
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  count: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['close'])

const { t } = useI18n()

const target = ref('Instagram')
const painting = ref(false)
const statsRef = ref(null)

function close() {
  emit('close')
}

function shown() {
  statsRef.value?.paint()
}

function download() {
  statsRef.value?.download()
}
</script>

<template>
  <BModal
    :model-value="show"
    data-testid="stats-share-image-modal"
    :title="t('partials.share_modal_title')"
    size="md"
    @shown="shown"
    @hide="close"
  >
    <div class="w-100 d-flex justify-content-around">
      <div class="btn-group mb-4 stats-share-buttons" role="group">
        <BButton
          v-for="platform in STATS_SHARE_PLATFORMS"
          :key="platform"
          variant="primary"
          size="sm"
          :class="{ active: target === platform }"
          :disabled="painting"
          :data-testid="`stats-share-image-target-${platform.toLowerCase()}`"
          @click="target = platform"
        >
          {{ platform }}
        </BButton>
      </div>
    </div>

    <StatsShareImage ref="statsRef" :count="props.count" :target="target" size @update:painting="painting = $event" />

    <template #footer>
      <BButton variant="white" data-testid="stats-share-image-close" @click="close">
        {{ t('partials.close') }}
      </BButton>
      <BButton variant="primary" data-testid="stats-share-image-download" @click="download">
        {{ t('partials.download') }}
      </BButton>
    </template>
  </BModal>
</template>

<style scoped>
/* StatsShareModal.vue's black/white toggle look: unselected buttons are
   white-on-black-outline, the active platform inverts to black-fill with an
   offset shadow - matches the neo-brutalist stat-card treatment used
   elsewhere on the event/group pages. develop only overrides
   background-color/color (its own ::v-deep .buttons button rule), not
   border-color, so the button keeps whatever border its Bootstrap variant
   sets - `variant="primary"` on the BButton above renders that border as
   $primary/brand teal, not `outline-dark`'s near-black. */
.stats-share-buttons :deep(.btn) {
  font-size: 12px;
  color: #000;
  background-color: #fff;
}

.stats-share-buttons :deep(.btn.active) {
  color: #fff;
  background-color: #222;
  box-shadow: 5px 5px 0 0 #222;
}

@media (max-width: 575.98px) {
  .stats-share-buttons :deep(.btn) {
    font-size: 10px;
  }
}
</style>
