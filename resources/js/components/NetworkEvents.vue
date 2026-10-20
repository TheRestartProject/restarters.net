<template>
  <div class="network-events">
    <div class="d-flex flex-wrap align-items-center justify-content-end mb-3">
      <b-button
          v-if="calendarCopyUrl"
          variant="link"
          class="subscribe-btn mr-2"
          @click="$refs.calendar.show()"
      >{{ __('networks.events.subscribe') }}</b-button>
      <b-button-group :aria-label="__('networks.events.view_toggle')">
        <b-button
            class="view-list"
            size="sm"
            :variant="view === 'list' ? 'primary' : 'outline-primary'"
            :pressed="view === 'list'"
            @click="setView('list')"
        >{{ __('networks.events.list') }}</b-button>
        <b-button
            class="view-calendar"
            size="sm"
            :variant="view === 'calendar' ? 'primary' : 'outline-primary'"
            :pressed="view === 'calendar'"
            @click="setView('calendar')"
        >{{ __('networks.events.calendar') }}</b-button>
      </b-button-group>
    </div>

    <div class="filter-bar d-flex flex-wrap align-items-center mb-3">
      <b-dropdown :text="groupLabel" variant="outline-secondary" size="sm" class="mr-3 mb-2 group-filter">
        <b-dropdown-form>
          <b-form-checkbox-group
              v-model="groupIds"
              :options="groupOptions"
              stacked
              name="network-events-groups"
          />
        </b-dropdown-form>
      </b-dropdown>
      <template v-if="view === 'list'">
        <b-form-select v-model="range" :options="rangeOptions" size="sm" class="range-select mr-3 mb-2" />
        <template v-if="range === 'custom'">
          <label for="network-events-from" class="mr-1 mb-2 small">{{ __('networks.events.from') }}</label>
          <b-form-input id="network-events-from" v-model="customFrom" type="date" size="sm" class="date-input mr-2 mb-2" />
          <label for="network-events-to" class="mr-1 mb-2 small">{{ __('networks.events.to') }}</label>
          <b-form-input id="network-events-to" v-model="customTo" type="date" size="sm" class="date-input mr-3 mb-2" />
        </template>
        <b-form-checkbox v-model="includePast" class="mr-3 mb-2 include-past">{{ __('networks.events.include_past') }}</b-form-checkbox>
      </template>
      <b-button v-if="filtersActive" variant="link" size="sm" class="clear-filters mb-2 p-0 mr-3" @click="clearFilters">
        {{ __('networks.events.clear') }}
      </b-button>
      <span class="event-count text-muted small mb-2 ml-auto">{{ countLabel }}</span>
    </div>

    <b-alert v-if="failed" show variant="danger">{{ __('networks.events.error') }}</b-alert>
    <div v-else-if="loading && !loaded" class="text-muted">{{ __('networks.events.loading') }}</div>
    <NetworkEventsList v-else-if="view === 'list'" :events="visibleEvents" :clash-days="clashes" />
    <NetworkEventsCalendar
        v-else
        :events="visibleEvents"
        :month="month"
        :clash-days="clashes"
        @prev="shift(-1)"
        @next="shift(1)"
    />

    <CalendarAddModal v-if="calendarCopyUrl" ref="calendar" :copy-url="calendarCopyUrl" :edit-url="calendarEditUrl">
      <template slot="title">{{ __('networks.events.subscribe') }}</template>
      <template slot="description">{{ __('calendars.find_out_more') }}</template>
    </CalendarAddModal>
  </div>
</template>
<script>
import axios from 'axios'
import moment from 'moment-timezone'
import CalendarAddModal from './CalendarAddModal.vue'
import NetworkEventsList from './NetworkEventsList.vue'
import NetworkEventsCalendar from './NetworkEventsCalendar.vue'
import { bucketByDay, clashDays, shiftMonth } from './networkEventsUtil'

const VIEW_KEY = 'networkEventsView'

function storedView() {
  try {
    const v = window.localStorage.getItem(VIEW_KEY)
    return v === 'calendar' ? 'calendar' : 'list'
  } catch {
    // localStorage can be unavailable (private mode); fall back to the default view.
    return 'list'
  }
}

export default {
  components: { CalendarAddModal, NetworkEventsList, NetworkEventsCalendar },
  props: {
    networkId: {
      type: Number,
      required: true
    },
    calendarCopyUrl: {
      type: String,
      required: false,
      default: ''
    },
    calendarEditUrl: {
      type: String,
      required: false,
      default: ''
    }
  },
  data() {
    return {
      view: storedView(),
      month: moment().format('YYYY-MM'),
      groupIds: [],
      range: '30',
      customFrom: '',
      customTo: '',
      includePast: false,
      events: [],
      knownGroups: {},
      loading: false,
      loaded: false,
      failed: false,
      requestId: 0
    }
  },
  computed: {
    rangeOptions() {
      return [
        { value: '7', text: this.__('networks.events.range_7') },
        { value: '30', text: this.__('networks.events.range_30') },
        { value: 'custom', text: this.__('networks.events.range_custom') }
      ]
    },
    groupOptions() {
      return Object.values(this.knownGroups)
        .sort((a, b) => a.name.localeCompare(b.name))
        .map(g => ({ value: g.id, text: g.name }))
    },
    groupLabel() {
      return this.groupIds.length
        ? this.groupIds.length + ' / ' + this.groupOptions.length + ' ' + this.__('networks.events.groups')
        : this.__('networks.events.all_groups')
    },
    // The window of time to ask the server for.
    timeWindow() {
      if (this.view === 'calendar') {
        const first = moment.utc(this.month + '-01', 'YYYY-MM-DD')

        return {
          start: first.clone().subtract(1, 'days').startOf('day'),
          end: first.clone().endOf('month').add(1, 'days')
        }
      }

      if (this.range === 'custom' && this.customFrom && this.customTo) {
        return {
          start: moment(this.customFrom).startOf('day'),
          end: moment(this.customTo).endOf('day')
        }
      }

      const now = moment()

      return {
        start: this.includePast ? now.clone().subtract(90, 'days') : now,
        end: now.clone().add(Number.parseInt(this.range, 10) || 30, 'days')
      }
    },
    visibleEvents() {
      const now = moment()

      return this.events
        .filter(e => !this.groupIds.length || this.groupIds.includes(e.group.id))
        .filter(e => this.view === 'calendar' || this.includePast || moment(e.end).isSameOrAfter(now))
        .sort((a, b) => new Date(a.start) - new Date(b.start))
    },
    clashes() {
      return clashDays(bucketByDay(this.visibleEvents))
    },
    filtersActive() {
      return this.groupIds.length > 0 || this.range !== '30' || this.includePast
    },
    countLabel() {
      const n = this.visibleEvents.length

      return this.__('networks.events.count', { count: n })
    }
  },
  watch: {
    timeWindow() {
      this.load()
    }
  },
  mounted() {
    this.load()
  },
  methods: {
    setView(v) {
      this.view = v

      try {
        window.localStorage.setItem(VIEW_KEY, v)
      } catch (e) {
        // Storage unavailable - the choice just won't be remembered.
      }
    },
    shift(n) {
      this.month = shiftMonth(this.month, n)
    },
    clearFilters() {
      this.groupIds = []
      this.range = '30'
      this.customFrom = ''
      this.customTo = ''
      this.includePast = false
    },
    async load() {
      const id = ++this.requestId
      this.loading = true
      this.failed = false

      try {
        const response = await axios.get(`/api/v2/networks/${this.networkId}/events`, {
          params: {
            start: this.timeWindow.start.toISOString(),
            end: this.timeWindow.end.toISOString()
          }
        })

        // Ignore responses that have been superseded by a later request.
        if (id !== this.requestId) {
          return
        }

        this.events = response.data.data || []
        this.events.forEach(e => {
          if (e.group && !this.knownGroups[e.group.id]) {
            this.$set(this.knownGroups, e.group.id, { id: e.group.id, name: e.group.name })
          }
        })
        this.loaded = true
      } catch {
        // Only the latest request may flag failure; stale ones are deliberately ignored.
        if (id === this.requestId) {
          this.failed = true
        }
      } finally {
        if (id === this.requestId) {
          this.loading = false
        }
      }
    }
  }
}
</script>
<style scoped lang="scss">
.range-select {
  width: auto;
}

.date-input {
  width: auto;
}
</style>
