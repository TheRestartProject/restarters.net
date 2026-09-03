<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

// Shown when EventForm's pre-create check finds an event that already looks
// like the one being posted. Advisory only: the criteria in
// utils/duplicateEvents.js are heuristics over user-entered text and times, so
// this offers a way out and never blocks. Two genuinely different events can
// share a venue and a day.
const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  // findDuplicateEvents() output: [{ event, confidence, reasons }], strongest
  // first.
  matches: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'post-anyway'])

const { t, locale } = useI18n()

const CONFIDENCE_KEYS = {
  certain: 'client.events.duplicate_check_certain',
  likely: 'client.events.duplicate_check_likely',
  possible: 'client.events.duplicate_check_possible',
}

function confidenceText(confidence) {
  return t(CONFIDENCE_KEYS[confidence] || CONFIDENCE_KEYS.possible)
}

// The event's own timezone, not the viewer's: a host in London checking a
// Brussels group's event should see the time the event actually starts.
function when(event) {
  if (!event.start) return ''

  const parsed = new Date(event.start)
  if (Number.isNaN(parsed.getTime())) return ''

  try {
    return new Intl.DateTimeFormat(locale.value, {
      dateStyle: 'full',
      timeStyle: 'short',
      timeZone: event.timezone || 'UTC',
    }).format(parsed)
  } catch {
    return parsed.toISOString()
  }
}

function where(event) {
  return event.online ? t('client.events.duplicate_check_online') : event.location || ''
}

const rows = computed(() =>
  props.matches.map((match) => ({
    id: match.event.id,
    title: match.event.title,
    confidence: confidenceText(match.confidence),
    when: when(match.event),
    where: where(match.event),
  })),
)
</script>

<template>
  <BModal
    :model-value="show"
    :title="t('client.events.duplicate_check_title')"
    no-footer
    data-testid="event-duplicate-modal"
    @hide="emit('close')"
  >
    <p data-testid="event-duplicate-intro">{{ t('client.events.duplicate_check_intro', matches.length) }}</p>

    <div
      v-for="row in rows"
      :key="row.id"
      class="border rounded p-3 mb-3"
      :data-testid="`event-duplicate-match-${row.id}`"
    >
      <BBadge variant="warning" class="mb-2" :data-testid="`event-duplicate-confidence-${row.id}`">
        {{ row.confidence }}
      </BBadge>
      <p class="fw-bold mb-1">{{ row.title }}</p>
      <p class="mb-1 small">{{ row.when }}</p>
      <p class="mb-2 small text-muted">{{ row.where }}</p>
      <NuxtLink
        :to="`/party/edit/${row.id}`"
        class="btn btn-outline-primary btn-sm"
        :data-testid="`event-duplicate-edit-${row.id}`"
      >
        {{ t('client.events.duplicate_check_edit') }}
      </NuxtLink>
    </div>

    <div class="d-flex justify-content-end gap-2 flex-wrap">
      <BButton variant="outline-secondary" data-testid="event-duplicate-cancel" @click="emit('close')">
        {{ t('client.events.duplicate_check_cancel') }}
      </BButton>
      <BButton variant="primary" data-testid="event-duplicate-post-anyway" @click="emit('post-anyway')">
        {{ t('client.events.duplicate_check_post_anyway') }}
      </BButton>
    </div>
  </BModal>
</template>
