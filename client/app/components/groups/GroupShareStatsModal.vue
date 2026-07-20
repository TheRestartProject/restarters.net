<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// includes/modals/group-share-stats.blade.php: embed codes + live previews
// for the two public, anonymous-access widgets a group can show off on its
// own website - the headline-stats row (/group/stats/{id}) and the CO2
// equivalence infographic (/outbound/info/group/{id}/leaf). Both are plain
// web routes (routes/web.php), same kind of public link as the
// /export/devices href used elsewhere in the client - so a relative <iframe
// src> works here too, no apiBase prefix needed.
//
// StatsImpact.vue's canvas-based social-share image generator
// (StatsShareModal.vue/StatsShare.vue - Instagram/Facebook/Twitter/LinkedIn
// export) is a separate, much larger feature (seedling/hectare CO2
// visualisations painted to a <canvas>) that both the header's "Share group
// stats" action and the CO2 card's "Share this" link opened in develop; only
// the embed-code half is reproduced here.
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  groupId: {
    type: Number,
    required: true,
  },
  groupName: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close'])

const { t } = useI18n()

const headlineUrl = computed(() => `/group/stats/${props.groupId}`)
const headlineEmbed = computed(
  () => `<iframe src="${headlineUrl.value}" frameborder="0" width="700" height="370"></iframe>`
)

const co2Url = computed(() => `/outbound/info/group/${props.groupId}/leaf`)
const co2Embed = computed(() => `<iframe src="${co2Url.value}" frameborder="0" width="700" height="370"></iframe>`)

function close() {
  emit('close')
}
</script>

<template>
  <BModal
    :model-value="show"
    data-testid="group-share-stats-modal"
    :title="t('groups.share_stats_header')"
    size="lg"
    no-footer
    @hide="close"
  >
    <p>{{ t('groups.share_stats_message', { group: groupName }) }}</p>

    <h3 class="h6">{{ t('groups.headline_stats_dropdown') }}</h3>
    <p class="small text-muted">{{ t('groups.headline_stats_message') }}</p>
    <div class="mb-2">
      <label for="group-share-stats-headline-embed" class="fw-bold small">{{ t('groups.embed_code_header') }}:</label>
      <input
        id="group-share-stats-headline-embed"
        type="text"
        readonly
        class="form-control"
        data-testid="group-share-stats-headline-embed"
        :value="headlineEmbed"
        @focus="$event.target.select()"
      >
    </div>
    <iframe
      :src="headlineUrl"
      frameborder="0"
      width="100%"
      height="370"
      class="form-control mb-4"
      data-testid="group-share-stats-headline-preview"
    />

    <h3 class="h6">{{ t('groups.co2_equivalence_visualisation_dropdown') }}</h3>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <p class="small text-muted" v-html="t('groups.infographic_message')" />
    <div class="mb-2">
      <label for="group-share-stats-co2-embed" class="fw-bold small">{{ t('groups.embed_code_header') }}:</label>
      <input
        id="group-share-stats-co2-embed"
        type="text"
        readonly
        class="form-control"
        data-testid="group-share-stats-co2-embed"
        :value="co2Embed"
        @focus="$event.target.select()"
      >
    </div>
    <iframe
      :src="co2Url"
      frameborder="0"
      width="100%"
      height="370"
      class="form-control"
      data-testid="group-share-stats-co2-preview"
    />
  </BModal>
</template>
