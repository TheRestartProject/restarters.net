<template>
  <div class="network-events-calendar">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <b-button variant="outline-secondary" size="sm" class="prev-month" @click="$emit('prev')">
        <span aria-hidden="true">&lsaquo;</span>
        <span class="sr-only">{{ __('networks.events.prev_month') }}</span>
      </b-button>
      <h3 class="h5 m-0 month-title" aria-live="polite">{{ title }}</h3>
      <b-button variant="outline-secondary" size="sm" class="next-month" @click="$emit('next')">
        <span aria-hidden="true">&rsaquo;</span>
        <span class="sr-only">{{ __('networks.events.next_month') }}</span>
      </b-button>
    </div>
    <table class="table table-bordered calendar-grid mb-0">
      <thead>
        <tr>
          <th v-for="d in weekdays" :key="d" scope="col" class="text-center small">{{ d }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(week, wi) in weeks" :key="wi">
          <td
              v-for="d in week"
              :key="d.date"
              class="day-cell"
              :class="{ 'out-of-month': !d.inMonth, clash: clashDays[d.date], today: d.date === today }"
              :aria-label="cellLabel(d)"
          >
            <div class="day-number small">{{ d.day }}</div>
            <template v-if="d.inMonth || buckets[d.date]">
              <a
                  v-for="e in (buckets[d.date] || [])"
                  :key="e.id"
                  :href="'/party/view/' + e.id"
                  class="chip d-none d-md-block"
                  :class="{ cancelled: e.cancelled, online: e.online }"
                  :title="e.title"
              >{{ time(e) }} {{ e.group.name }}</a>
              <span
                  v-if="buckets[d.date]"
                  class="count-dot d-md-none badge badge-pill badge-primary"
              >{{ buckets[d.date].length }}</span>
              <span
                  v-if="clashDays[d.date]"
                  class="badge badge-warning clash-badge d-none d-md-inline-block"
                  :title="__('networks.events.clash_title')"
              >{{ __('networks.events.clash', { count: clashDays[d.date] }) }}</span>
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script>
import moment from 'moment-timezone'
import { bucketByDay, monthGrid } from './networkEventsUtil'

export default {
  props: {
    events: {
      type: Array,
      required: true
    },
    // 'YYYY-MM'
    month: {
      type: String,
      required: true
    },
    clashDays: {
      type: Object,
      required: false,
      default: () => ({})
    }
  },
  computed: {
    // Monday-first throughout.
    weeks() {
      return monthGrid(this.month, 1)
    },
    buckets() {
      return bucketByDay(this.events)
    },
    title() {
      return moment.utc(this.month + '-01', 'YYYY-MM-DD').format('MMMM YYYY')
    },
    weekdays() {
      // 2024-01-01 was a Monday.
      return [0, 1, 2, 3, 4, 5, 6].map(i => moment.utc('2024-01-01').add(i, 'days').format('ddd'))
    },
    today() {
      return moment().format('YYYY-MM-DD')
    }
  },
  methods: {
    time(e) {
      return moment.tz(e.start, e.timezone || 'UTC').format('HH:mm')
    },
    cellLabel(d) {
      const date = moment.utc(d.date).format('D MMMM')
      const n = (this.buckets[d.date] || []).length

      return date + ', ' + this.__('networks.events.count', { count: n })
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.calendar-grid {
  table-layout: fixed;
  background: $white;
}

.day-cell {
  height: 5.5rem;
  padding: 0.25rem;
  vertical-align: top;
  overflow: hidden;

  &.out-of-month {
    background: $brand-grey;
    color: $brand-placeholder;
  }

  &.today .day-number {
    font-weight: bold;
    color: $brand;
  }

  &.clash {
    background: lighten($brand-orange, 28%);
  }
}

.chip {
  display: block;
  font-size: 0.75rem;
  line-height: 1.3;
  padding: 1px 4px;
  margin-bottom: 2px;
  border-radius: 3px;
  background: $brand-light;
  color: $white;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  &:hover {
    text-decoration: none;
    background: $brand;
  }

  &.online {
    background: $brand-placeholder;
  }

  &.cancelled {
    opacity: 0.5;
    text-decoration: line-through;
  }
}

.clash-badge {
  font-size: 0.7rem;
}

@media (max-width: 767px) {
  .day-cell {
    height: 3.5rem;
  }
}
</style>
