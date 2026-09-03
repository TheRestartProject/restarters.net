<script setup>
import { computed, ref } from 'vue'
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
// this same modal for the embed codes. The canvas social-image generator is a
// separate component (StatsShareImageModal.vue / StatsShareImage.vue), opened
// by the CO2 card's "Share this".
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

// event-share-stats.blade.php's #accordionEvent: both cards start collapsed
// (`.collapse` with no `.show`), expanding only on a header click - this was
// rendering as two always-open, always-live-iframe blocks instead.
const headlineOpen = ref(false)
const co2Open = ref(false)

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

    <!-- event-share-stats.blade.php's #accordionEvent: two collapsed-by-
         default cards, matching StatsShareImageModal's sibling GroupShareStatsModal
         convention of a plain header-button toggle rather than a full
         accordion widget. -->
    <div class="accordion-share">
      <div class="accordion-share__item">
        <button
          type="button"
          class="accordion-share__header"
          :aria-expanded="headlineOpen"
          data-testid="event-share-stats-headline-toggle"
          @click="headlineOpen = !headlineOpen"
        >
          {{ t('events.headline_stats_dropdown') }}
          <svg
            class="accordion-share__caret"
            :class="{ 'accordion-share__caret--open': headlineOpen }"
            width="10"
            height="6"
            viewBox="0 0 10 6"
            aria-hidden="true"
          ><path d="M5,6l-5,-6l10,0l-5,6Z" fill="#0394a6" /></svg>
        </button>
        <div v-show="headlineOpen" class="accordion-share__body" data-testid="event-share-stats-headline-body">
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
            :title="t('events.headline_stats_dropdown')"
            frameborder="0"
            width="100%"
            height="370"
            class="form-control"
            data-testid="event-share-stats-headline-preview"
          />
        </div>
      </div>

      <div class="accordion-share__item">
        <button
          type="button"
          class="accordion-share__header"
          :aria-expanded="co2Open"
          data-testid="event-share-stats-co2-toggle"
          @click="co2Open = !co2Open"
        >
          {{ t('events.co2_equivalence_visualisation_dropdown') }}
          <svg
            class="accordion-share__caret"
            :class="{ 'accordion-share__caret--open': co2Open }"
            width="10"
            height="6"
            viewBox="0 0 10 6"
            aria-hidden="true"
          ><path d="M5,6l-5,-6l10,0l-5,6Z" fill="#0394a6" /></svg>
        </button>
        <div v-show="co2Open" class="accordion-share__body" data-testid="event-share-stats-co2-body">
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
            :title="t('events.co2_equivalence_visualisation_dropdown')"
            frameborder="0"
            width="100%"
            height="370"
            class="form-control"
            data-testid="event-share-stats-co2-preview"
          />
        </div>
      </div>
    </div>
  </BModal>
</template>

<style scoped>
/* event-share-stats.blade.php's #accordionEvent .card: a plain bordered,
   button-toggled section, collapsed by default. */
.accordion-share__item {
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  margin-bottom: 0.5rem;
}

.accordion-share__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  border: 0;
  background: none;
  padding: 0.75rem 1rem;
  font-weight: bold;
  text-align: left;
}

.accordion-share__caret {
  transition: transform 0.2s ease;
}

.accordion-share__caret--open {
  transform: rotate(180deg);
}

.accordion-share__body {
  padding: 0 1rem 1rem;
}
</style>
