<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// Gap 3: "Share event stats" is completely absent from the Nuxt event page
// today. develop's "Share event stats" dropdown item (EventActions.vue)
// opens resources/views/includes/modals/event-share-stats.blade.php, a
// plain embed-code modal - a DIFFERENT feature from StatsImpact.vue's CO2-
// card "Share this" button, which opens the canvas-painted, Instagram/
// Facebook/Twitter/LinkedIn social-image generator (StatsShareModal.vue/
// StatsShare.vue). This component reproduces the embed-code modal only
// (mirroring components/groups/GroupShareStatsModal.vue's identical
// decision for the group page's equivalent pair of entry points) - the two
// public, anonymous-access widgets it embeds are PartyController::stats
// (`/party/stats/{id}/wide`) and OutboundController::info's 'leaf' format
// (`/outbound/info/party/{id}/leaf`, confirmed to accept type=party same as
// type=group). Both the dropdown item and the CO2-card share button open
// this same modal - the canvas image generator itself is out of scope, same
// as the group page.
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  eventId: {
    type: Number,
    required: true,
  },
  // events.share_stats_message's :date/:event_name/:number_devices params
  // (event-share-stats.blade.php) - passed through rather than recomputed
  // here since the caller already has event/stats loaded.
  eventDate: {
    type: String,
    default: '',
  },
  eventName: {
    type: String,
    default: '',
  },
  fixedDevices: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['close'])

const { t } = useI18n()

// Absolute, against the Laravel origin - these widgets are served by Laravel
// (routes/web.php:433,453), not by Nuxt. Root-relative URLs were doubly wrong
// here: the preview iframe resolved against the SPA's own origin and 404'd
// (Nuxt logged "Page not found: /party/stats/{id}/wide" on every event page),
// and the embed code the user copies is meant to be pasted on THEIR site,
// where a root-relative path can never resolve. develop builds these from
// env('APP_URL') for the same reason
// (includes/modals/event-share-stats.blade.php:33).
const runtimeConfig = useRuntimeConfig()

const headlineUrl = computed(() => `${runtimeConfig.public.apiBase}/party/stats/${props.eventId}/wide`)
const headlineEmbed = computed(
  () => `<iframe src="${headlineUrl.value}" frameborder="0" width="700" height="370"></iframe>`
)

const co2Url = computed(() => `${runtimeConfig.public.apiBase}/outbound/info/party/${props.eventId}/leaf`)
const co2Embed = computed(() => `<iframe src="${co2Url.value}" frameborder="0" width="700" height="370"></iframe>`)

function close() {
  emit('close')
}
</script>

<template>
  <BModal
    :model-value="show"
    data-testid="event-share-stats-modal"
    :title="t('events.share_stats_header')"
    size="lg"
    no-footer
    @hide="close"
  >
    <p>{{ t('events.share_stats_message', { date: eventDate, event_name: eventName, number_devices: fixedDevices }) }}</p>

    <h3 class="h6">{{ t('events.headline_stats_dropdown') }}</h3>
    <div class="mb-2">
      <label for="event-share-stats-headline-embed" class="fw-bold small">{{ t('events.embed_code_header') }}:</label>
      <input
        id="event-share-stats-headline-embed"
        type="text"
        readonly
        class="form-control"
        data-testid="event-share-stats-headline-embed"
        :value="headlineEmbed"
        @focus="$event.target.select()"
      >
    </div>
    <p class="small text-muted">{{ t('events.headline_stats_message') }}</p>
    <iframe
      :src="headlineUrl"
      frameborder="0"
      width="100%"
      height="370"
      class="form-control mb-4"
      data-testid="event-share-stats-headline-preview"
    />

    <h3 class="h6">{{ t('events.co2_equivalence_visualisation_dropdown') }}</h3>
    <p class="small text-muted">{{ t('events.infographic_message') }}</p>
    <div class="mb-2">
      <label for="event-share-stats-co2-embed" class="fw-bold small">{{ t('events.embed_code_header') }}:</label>
      <input
        id="event-share-stats-co2-embed"
        type="text"
        readonly
        class="form-control"
        data-testid="event-share-stats-co2-embed"
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
      data-testid="event-share-stats-co2-preview"
    />
  </BModal>
</template>
