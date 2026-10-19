<template>
  <div class="network-events-list">
    <div v-if="!events.length" class="text-muted py-3">{{ __('networks.events.empty') }}</div>
    <div v-for="month in months" :key="month.key" class="mb-3">
      <h3 class="month-heading h5">{{ month.label }}</h3>
      <a
          v-for="e in month.events"
          :key="e.id"
          :href="'/party/view/' + e.id"
          class="event-row d-flex align-items-center p-2 mb-2 border rounded"
          :class="{ cancelled: e.cancelled }"
      >
        <div class="date-block text-center mr-3">
          <div class="date-day">{{ e.localStart.format('D') }}</div>
          <div class="date-month text-uppercase">{{ e.localStart.format('MMM') }}</div>
        </div>
        <div class="flex-grow-1">
          <div class="event-title font-weight-bold">{{ e.title }}</div>
          <div class="small text-muted">
            {{ e.localStart.format('ddd') }}
            {{ e.localStart.format('HH:mm') }} - {{ e.localEnd.format('HH:mm') }}
            &middot; {{ e.group.name }}
            <span v-if="e.online" class="badge badge-info ml-1">{{ __('networks.events.online') }}</span>
            <span v-if="e.cancelled" class="badge badge-secondary ml-1">{{ __('networks.events.cancelled') }}</span>
          </div>
        </div>
        <span
            v-if="clashDays[e.day] && !e.cancelled"
            class="badge badge-warning clash-badge ml-2"
            :title="__('networks.events.clash_title')"
        >{{ __('networks.events.clash', { count: clashDays[e.day] }) }}</span>
      </a>
    </div>
  </div>
</template>
<script>
import moment from 'moment-timezone'

export default {
  props: {
    events: {
      type: Array,
      required: true
    },
    clashDays: {
      type: Object,
      required: false,
      default: () => ({})
    }
  },
  computed: {
    months() {
      const out = []
      const byKey = {}

      this.events.forEach(raw => {
        const tz = raw.timezone || 'UTC'
        const localStart = moment.tz(raw.start, tz)
        const localEnd = moment.tz(raw.end, tz)
        const e = { ...raw, localStart, localEnd, day: localStart.format('YYYY-MM-DD') }
        const key = localStart.format('YYYY-MM')

        if (!byKey[key]) {
          byKey[key] = { key, label: localStart.format('MMMM YYYY'), events: [] }
          out.push(byKey[key])
        }

        byKey[key].events.push(e)
      })

      return out
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.event-row {
  color: inherit;
  text-decoration: none;
  background: $white;

  &:hover {
    background: $brand-grey;
    text-decoration: none;
  }

  &.cancelled {
    opacity: 0.6;

    .event-title {
      text-decoration: line-through;
    }
  }
}

.date-block {
  min-width: 3.5rem;
  border: 2px solid $black;
  padding: 0.25rem;
  line-height: 1.1;
}

.date-day {
  font-size: 1.4rem;
  font-weight: bold;
}

.date-month {
  font-size: 0.75rem;
  color: $brand-placeholder;
}
</style>
