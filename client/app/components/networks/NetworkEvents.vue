<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment-timezone'
import NetworkEventsList from '~/components/networks/NetworkEventsList.vue'
import NetworkEventsCalendar from '~/components/networks/NetworkEventsCalendar.vue'
import { shiftMonth } from '~/utils/networkEvents.js'

// The "Upcoming events" section of /networks/{id}: the network's events as a
// calendar (the default) or a list, with filters, and a button to subscribe to
// the whole network's iCal feed. Events come from GET /api/v2/networks/{id}/
// events; the server also returns events on unapproved and archived groups,
// but only when the caller is an administrator or one of this network's
// coordinators (the include_unapproved gate is server side).
const props = defineProps({
  networkId: {
    type: Number,
    required: true,
  },
})

const { t } = useI18n()
const { $api } = useNuxtApp()
const runtimeConfig = useRuntimeConfig()

const VIEW_KEY = 'networkEventsView'

function storedView() {
  try {
    const v = window.localStorage.getItem(VIEW_KEY)
    return v === 'list' ? 'list' : 'calendar'
  } catch {
    // localStorage can be unavailable (private mode); fall back to the default view.
    return 'calendar'
  }
}

const view = ref(storedView())
const month = ref(moment().format('YYYY-MM'))
const groupIds = ref([])
const range = ref('30')
const customFrom = ref('')
const customTo = ref('')
const includePast = ref(false)
const events = ref([])
const knownGroups = ref({})
const loading = ref(false)
const loaded = ref(false)
const failed = ref(false)
const showSubscribe = ref(false)
let requestId = 0

// The iCal feed is served by the Laravel app, not the SPA.
const calendarUrl = computed(() => `${runtimeConfig.public.apiBase || ''}/calendar/network/${props.networkId}`)

const rangeOptions = computed(() => [
  { value: '7', text: t('networks.events.range_7') },
  { value: '30', text: t('networks.events.range_30') },
  { value: 'custom', text: t('networks.events.range_custom') },
])

const groupOptions = computed(() =>
  Object.values(knownGroups.value).sort((a, b) => a.name.localeCompare(b.name))
)

const groupLabel = computed(() =>
  groupIds.value.length
    ? `${groupIds.value.length} / ${groupOptions.value.length} ${t('networks.events.groups')}`
    : t('networks.events.all_groups')
)

// The window of time to ask the server for.
const timeWindow = computed(() => {
  if (view.value === 'calendar') {
    const first = moment.utc(month.value + '-01', 'YYYY-MM-DD')

    return {
      start: first.clone().subtract(1, 'days').startOf('day'),
      end: first.clone().endOf('month').add(1, 'days'),
    }
  }

  if (range.value === 'custom' && customFrom.value && customTo.value) {
    return {
      start: moment(customFrom.value).startOf('day'),
      end: moment(customTo.value).endOf('day'),
    }
  }

  const now = moment()

  return {
    start: includePast.value ? now.clone().subtract(90, 'days') : now,
    end: now.clone().add(Number.parseInt(range.value, 10) || 30, 'days'),
  }
})

const visibleEvents = computed(() => {
  const now = moment()

  return events.value
    .filter((e) => !groupIds.value.length || groupIds.value.includes(e.group.id))
    .filter((e) => view.value === 'calendar' || includePast.value || moment(e.end).isSameOrAfter(now))
    .sort((a, b) => new Date(a.start) - new Date(b.start))
})

const filtersActive = computed(() => groupIds.value.length > 0 || (view.value === 'list' && (range.value !== '30' || includePast.value)))

const countLabel = computed(() => t('networks.events.count', { count: visibleEvents.value.length }, visibleEvents.value.length))

function setView(v) {
  view.value = v

  try {
    window.localStorage.setItem(VIEW_KEY, v)
  } catch {
    // Storage unavailable - the choice just won't be remembered.
  }
}

function shift(n) {
  month.value = shiftMonth(month.value, n)
}

function clearFilters() {
  groupIds.value = []
  range.value = '30'
  customFrom.value = ''
  customTo.value = ''
  includePast.value = false
}

function toggleGroup(id) {
  groupIds.value = groupIds.value.includes(id) ? groupIds.value.filter((g) => g !== id) : [...groupIds.value, id]
}

function copyCalendarUrl() {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(calendarUrl.value)
  }
}

async function load() {
  const id = ++requestId
  loading.value = true
  failed.value = false

  try {
    const response = await $api.network.events(props.networkId, {
      start: timeWindow.value.start.toISOString(),
      end: timeWindow.value.end.toISOString(),
      include_unapproved: 1,
    })

    // Ignore responses that have been superseded by a later request.
    if (id !== requestId) return

    events.value = response.data || []
    events.value.forEach((e) => {
      if (e.group && !knownGroups.value[e.group.id]) {
        knownGroups.value = { ...knownGroups.value, [e.group.id]: { id: e.group.id, name: e.group.name } }
      }
    })
    loaded.value = true
  } catch {
    // Only the latest request may flag failure; stale ones are deliberately ignored.
    if (id === requestId) failed.value = true
  } finally {
    if (id === requestId) loading.value = false
  }
}

// The window is a computed of moment objects (new instances each time),
// so watch what it is made of rather than the object.
watch(() => [timeWindow.value.start.valueOf(), timeWindow.value.end.valueOf()], load)

onMounted(load)
</script>

<template>
  <div data-testid="network-events">
    <div class="d-flex flex-wrap align-items-center justify-content-end mb-3">
      <button type="button" class="btn btn-link me-2" data-testid="network-events-subscribe" @click="showSubscribe = true">
        {{ t('networks.events.subscribe') }}
      </button>
      <div class="btn-group view-toggle" role="group" :aria-label="t('networks.events.view_toggle')">
        <button
          type="button"
          class="btn btn-sm"
          :class="{ active: view === 'calendar' }"
          :aria-pressed="view === 'calendar'"
          data-testid="network-events-view-calendar"
          @click="setView('calendar')"
        >
          {{ t('networks.events.calendar') }}
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="{ active: view === 'list' }"
          :aria-pressed="view === 'list'"
          data-testid="network-events-view-list"
          @click="setView('list')"
        >
          {{ t('networks.events.list') }}
        </button>
      </div>
    </div>

    <div class="d-flex flex-wrap align-items-center mb-3 gap-2" data-testid="network-events-filters">
      <BDropdown :text="groupLabel" variant="outline-secondary" size="sm" data-testid="network-events-group-filter">
        <div class="px-3 py-1 group-options">
          <div v-for="g in groupOptions" :key="g.id" class="form-check">
            <input
              :id="`network-events-group-${g.id}`"
              type="checkbox"
              class="form-check-input"
              :checked="groupIds.includes(g.id)"
              :data-testid="`network-events-group-${g.id}`"
              @change="toggleGroup(g.id)"
            >
            <label class="form-check-label" :for="`network-events-group-${g.id}`">{{ g.name }}</label>
          </div>
        </div>
      </BDropdown>
      <template v-if="view === 'list'">
        <select v-model="range" class="form-select form-select-sm range-select" :aria-label="t('networks.events.range_custom')" data-testid="network-events-range">
          <option v-for="o in rangeOptions" :key="o.value" :value="o.value">{{ o.text }}</option>
        </select>
        <template v-if="range === 'custom'">
          <label for="network-events-from" class="small">{{ t('networks.events.from') }}</label>
          <input id="network-events-from" v-model="customFrom" type="date" class="form-control form-control-sm date-input">
          <label for="network-events-to" class="small">{{ t('networks.events.to') }}</label>
          <input id="network-events-to" v-model="customTo" type="date" class="form-control form-control-sm date-input">
        </template>
        <div class="form-check mb-0">
          <input id="network-events-include-past" v-model="includePast" type="checkbox" class="form-check-input" data-testid="network-events-include-past">
          <label class="form-check-label" for="network-events-include-past">{{ t('networks.events.include_past') }}</label>
        </div>
      </template>
      <button v-if="filtersActive" type="button" class="btn btn-link btn-sm p-0" data-testid="network-events-clear" @click="clearFilters">
        {{ t('networks.events.clear') }}
      </button>
      <span class="text-muted small ms-auto" data-testid="network-events-count">{{ countLabel }}</span>
    </div>

    <BAlert v-if="failed" :model-value="true" variant="danger" data-testid="network-events-error">{{ t('networks.events.error') }}</BAlert>
    <div v-else-if="loading && !loaded" class="text-muted" data-testid="network-events-loading">{{ t('networks.events.loading') }}</div>
    <NetworkEventsList v-else-if="view === 'list'" :events="visibleEvents" />
    <NetworkEventsCalendar v-else :events="visibleEvents" :month="month" @prev="shift(-1)" @next="shift(1)" />

    <BModal
      :model-value="showSubscribe"
      :title="t('networks.events.subscribe')"
      no-footer
      data-testid="network-events-subscribe-modal"
      @hide="showSubscribe = false"
    >
      <p>{{ t('networks.events.subscribe_description') }}</p>
      <div class="input-group">
        <input type="text" class="form-control" readonly :value="calendarUrl" :aria-label="t('networks.events.subscribe')" data-testid="network-events-calendar-url">
        <BButton variant="primary" @click="copyCalendarUrl">{{ t('profile.calendars.copy_link') }}</BButton>
      </div>
    </BModal>
  </div>
</template>

<style scoped lang="scss">
// Same look as the target toggle in StatsShareModal: black on white, inverted when active.
.view-toggle .btn {
  color: #000;
  background-color: #fff;
  border: 1px solid #000;

  &.active {
    color: #fff;
    background-color: #000;
  }
}

.range-select,
.date-input {
  width: auto;
}

.group-options {
  max-height: 16rem;
  overflow-y: auto;
  min-width: 14rem;
}
</style>
