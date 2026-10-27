<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment-timezone'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'

// Events grouped under month headings, soonest first, each row linking to
// its event. Times are in the event's own timezone.
const props = defineProps({
  events: {
    type: Array,
    required: true,
  },
})

const { t } = useI18n()
const { uploadedImageUrl } = useUploadedImageUrl()

const months = computed(() => {
  const out = []
  const byKey = {}

  props.events.forEach((raw) => {
    const tz = raw.timezone || 'UTC'
    const localStart = moment.tz(raw.start, tz)
    const localEnd = moment.tz(raw.end, tz)
    const key = localStart.format('YYYY-MM')

    if (!byKey[key]) {
      byKey[key] = { key, label: localStart.format('MMMM YYYY'), events: [] }
      out.push(byKey[key])
    }

    byKey[key].events.push({ ...raw, localStart, localEnd })
  })

  return out
})
</script>

<template>
  <div data-testid="network-events-list">
    <div v-if="!events.length" class="text-muted py-3" data-testid="network-events-empty">{{ t('networks.events.empty') }}</div>
    <div v-for="month in months" :key="month.key" class="mb-3">
      <h3 class="h5">{{ month.label }}</h3>
      <NuxtLink
        v-for="e in month.events"
        :key="e.id"
        :to="`/party/view/${e.id}`"
        class="event-row d-flex align-items-center p-2 mb-2 border rounded"
        :class="{ cancelled: e.cancelled }"
        :data-testid="`network-event-row-${e.id}`"
      >
        <div class="date-block text-center me-3">
          <div class="date-day">{{ e.localStart.format('D') }}</div>
          <div class="date-month text-uppercase">{{ e.localStart.format('MMM') }}</div>
        </div>
        <img v-if="uploadedImageUrl(e.group.image)" :src="uploadedImageUrl(e.group.image)" alt="" width="32" height="32" class="group-avatar me-3">
        <div class="flex-grow-1">
          <div class="event-title fw-bold">{{ e.title }}</div>
          <div class="small text-muted">
            {{ e.localStart.format('ddd') }}
            {{ e.localStart.format('HH:mm') }} - {{ e.localEnd.format('HH:mm') }}
            &middot; {{ e.group.name }}
            <span v-if="e.online" class="badge text-bg-info ms-1">{{ t('networks.events.online') }}</span>
            <span v-if="e.cancelled" class="badge text-bg-secondary ms-1">{{ t('networks.events.cancelled') }}</span>
            <span v-if="e.group.approved === false" class="badge text-bg-warning ms-1" data-testid="unapproved-badge">{{ t('networks.events.group_unapproved') }}</span>
            <span v-if="e.group.archived_at" class="badge text-bg-dark ms-1" data-testid="archived-badge">{{ t('networks.events.group_archived') }}</span>
          </div>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>

<style scoped lang="scss">
.event-row {
  color: inherit;
  text-decoration: none;
  background: #fff;

  &:hover {
    background: #f5f7fa;
    text-decoration: none;
  }

  &.cancelled .event-title {
    text-decoration: line-through;
  }
}

.date-block {
  min-width: 3.5rem;
  border: 2px solid #000;
  padding: 0.25rem;
  line-height: 1.1;
}

.date-day {
  font-size: 1.4rem;
  font-weight: bold;
}

.date-month {
  font-size: 0.75rem;
  color: #6c757d;
}

.group-avatar {
  flex: none;
  border-radius: 50%;
  object-fit: cover;
}
</style>
