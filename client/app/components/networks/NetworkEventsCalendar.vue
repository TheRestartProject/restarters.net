<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment-timezone'
import NetworkEventChip from '~/components/networks/NetworkEventChip.vue'
import { bucketByDay, monthGrid } from '~/utils/networkEvents.js'

// Month grid of a network's events, Monday first. Each day shows up to
// MAX_CHIPS event chips; any more become "+N more", and that - like the count
// dot that stands in for the chips on small screens - opens a popover anchored
// to the day listing all of that day's events, each linking to its event (as
// Google Calendar does).
const MAX_CHIPS = 5

const props = defineProps({
  events: {
    type: Array,
    required: true,
  },
  // 'YYYY-MM'
  month: {
    type: String,
    required: true,
  },
})

defineEmits(['prev', 'next'])

const { t } = useI18n()

const weeks = computed(() => monthGrid(props.month, 1))
const buckets = computed(() => bucketByDay(props.events))
const title = computed(() => moment.utc(props.month + '-01', 'YYYY-MM-DD').format('MMMM YYYY'))
// 2024-01-01 was a Monday.
const weekdays = computed(() => [0, 1, 2, 3, 4, 5, 6].map((i) => moment.utc('2024-01-01').add(i, 'days').format('ddd')))
const today = computed(() => moment().format('YYYY-MM-DD'))

const openDay = ref(null)
const root = ref(null)

function dayEvents(date) {
  return buckets.value[date] || []
}

function cellLabel(d) {
  const date = moment.utc(d.date).format('D MMMM')
  return `${date}, ${t('networks.events.count', { count: dayEvents(d.date).length }, dayEvents(d.date).length)}`
}

function popoverTitle(date) {
  return t('networks.events.day_events', { date: moment.utc(date).format('dddd D MMMM') })
}

function toggleDay(date) {
  openDay.value = openDay.value === date ? null : date
}

function close() {
  openDay.value = null
}

function onDocumentClick(e) {
  if (openDay.value && root.value && !root.value.contains(e.target)) close()
}

function onKeydown(e) {
  if (e.key === 'Escape') close()
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div ref="root" class="network-events-calendar" data-testid="network-events-calendar">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" data-testid="network-events-prev" @click="close(); $emit('prev')">
        <span aria-hidden="true">&lsaquo;</span>
        <span class="visually-hidden">{{ t('networks.events.prev_month') }}</span>
      </button>
      <h3 class="h5 m-0" aria-live="polite" data-testid="network-events-month-title">{{ title }}</h3>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-testid="network-events-next" @click="close(); $emit('next')">
        <span aria-hidden="true">&rsaquo;</span>
        <span class="visually-hidden">{{ t('networks.events.next_month') }}</span>
      </button>
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
            v-for="(d, di) in week"
            :key="d.date"
            class="day-cell"
            :class="{ 'out-of-month': !d.inMonth, today: d.date === today, 'popover-open': openDay === d.date }"
            :aria-label="cellLabel(d)"
            :data-testid="`network-events-day-${d.date}`"
          >
            <div class="day-number small">{{ d.day }}</div>
            <template v-if="dayEvents(d.date).length">
              <div class="d-none d-md-block">
                <NetworkEventChip v-for="e in dayEvents(d.date).slice(0, MAX_CHIPS)" :key="e.id" :event="e" />
                <button
                  v-if="dayEvents(d.date).length > MAX_CHIPS"
                  type="button"
                  class="btn btn-link btn-sm p-0 more-link"
                  :aria-expanded="openDay === d.date"
                  :data-testid="`network-events-more-${d.date}`"
                  @click.stop="toggleDay(d.date)"
                >
                  {{ t('networks.events.more', { count: dayEvents(d.date).length - MAX_CHIPS }) }}
                </button>
              </div>
              <button
                type="button"
                class="count-dot d-md-none badge rounded-pill text-bg-primary border-0"
                :aria-expanded="openDay === d.date"
                :data-testid="`network-events-count-${d.date}`"
                @click.stop="toggleDay(d.date)"
              >
                {{ dayEvents(d.date).length }}
              </button>
            </template>

            <div
              v-if="openDay === d.date"
              class="day-popover"
              :class="{ 'align-end': di >= 4 }"
              role="dialog"
              :aria-label="popoverTitle(d.date)"
              :data-testid="`network-events-popover-${d.date}`"
              @click.stop
            >
              <div class="d-flex align-items-center justify-content-between mb-2">
                <strong class="small">{{ popoverTitle(d.date) }}</strong>
                <button type="button" class="btn-close btn-sm" :aria-label="t('networks.events.close')" data-testid="network-events-popover-close" @click="close" />
              </div>
              <NetworkEventChip v-for="e in dayEvents(d.date)" :key="e.id" :event="e" />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped lang="scss">
.calendar-grid {
  table-layout: fixed;
  background: #fff;
}

.day-cell {
  position: relative;
  min-height: 7.5rem;
  height: 7.5rem;
  padding: 0.25rem;
  vertical-align: top;

  &.out-of-month {
    background: #f5f7fa;
    color: #8a8f98;
  }

  &.today .day-number {
    font-weight: bold;
    color: #1b6e7a;
  }

  // The open day's popover must sit above the neighbouring cells.
  &.popover-open {
    z-index: 5;
  }
}

.more-link {
  font-size: 0.8rem;
}

.count-dot {
  cursor: pointer;
}

.day-popover {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 10;
  width: 18rem;
  max-width: 90vw;
  max-height: 20rem;
  overflow-y: auto;
  padding: 0.5rem;
  background: #fff;
  border: 1px solid #adb5bd;
  border-radius: 6px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);

  // Right-hand columns open leftwards so the popover stays on screen.
  &.align-end {
    left: auto;
    right: 0;
  }
}

@media (max-width: 767px) {
  .day-cell {
    min-height: 3.5rem;
    height: 3.5rem;
  }

  // On a phone the popover is a sheet at the foot of the screen rather than
  // something squeezed into a one-seventh-width cell.
  .day-popover {
    position: fixed;
    top: auto;
    left: 1rem;
    right: 1rem;
    bottom: 1rem;
    width: auto;
    max-width: none;
  }
}
</style>
