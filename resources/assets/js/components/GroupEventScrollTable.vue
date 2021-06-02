<template>
  <div>
    <GroupEventsScrollTableFilters v-if="filters" />
    <b-table :fields="fields" :items="items" sort-null-last :sort-compare="sortCompare" sticky-header="50vh" responsive class="text-center mt-2 pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
      <template slot="head(date_short)">
        <span />
      </template>
      <template slot="cell(date_short)" slot-scope="data" v-bind:attending="attending" v-bind:dayofmonth="dayofmonth" v-bind:month="month">
        <div :class="{
          datecell: true,
          attending: data.item.date_short.attending
        }">
          <div class="datebox d-flex flex-column">
            <span class="day align-top">{{ dayofmonth(data.item.date_short) }}</span>
            <span class="month">
              {{ month(data.item.date_short) }}
            </span>
          </div>
        </div>
      </template>

      <template slot="head(date_long)">
        <span />
      </template>
      <template slot="cell(date_long)" slot-scope="data" v-bind:attending="attending" v-bind:date="date" v-bind:start="start" v-bind:end="end" v-bind:addGroupName="addGroupName">
        <div :class="{
          date: true,
          'text-left': true,
          'pl-3': true,
          attending: data.item.date_short.attending
        }">
          {{ date(data.item.date_short) }}
          <br class="d-none d-md-block" />
          {{ start(data.item.date_short) }} <span class="d-none d-md-inline">- {{ end(data.item.date_short) }}</span>
          <br class="d-block d-md-none" />
          <!--          TODO Event title?-->
        </div>
        <div class="text-left d-none d-md-table-cell pl-3">
          <span v-if="addGroupName" class="small">
            <a :href="'/group/view/' + data.item.date_short.group.idgroups">{{ data.item.date_short.group.name }}</a>
          </span>
        </div>
      </template>

      <template slot="head(title)">
        <span />
      </template>
      <template slot="cell(title)" slot-scope="data" v-bind:attending="attending">
        <a :class="{
          attending: data.item.date_short.attending
        }">
          <!-- eslint-disable-next-line -->
          <b>{{ data.item.title.venue ? data.item.title.venue : data.item.title.location }}<b-badge v-if="data.item.title.online" variant="primary" pill class="nounderline">{{ __('events.online') }}</b-badge></b>
        </a>
      </template>

      <template slot="head(invited)">
        <div class="d-none d-md-table-cell">
          <b-img class="icon" src="/images/mail_ico.svg" :title="__('groups.volunteers_invited')" />
        </div>
      </template>
      <template slot="cell(invited)" slot-scope="data" v-bind:attending="attending">
        <div :class="{
          'd-none': true,
          'd-md-table-cell': true,
          'cell-number': true,
          attending: data.item.date_short.attending
        }">
          {{ data.item.invited }}
        </div>
      </template>

      <template slot="head(volunteers)">
        <div class="d-none d-md-table-cell">
          <b-img class="icon" src="/images/participants.svg" :title="__('groups.volunteers_confirmed')" />
        </div>
      </template>
      <template slot="cell(volunteers)" slot-scope="data" v-bind:attending="attending">
        <div :class="{
          'd-none': true,
          'd-md-table-cell': true,
          'cell-number': true,
          attending: data.item.date_short.attending
        }">
          {{ data.item.volunteers }}
        </div>
      </template>

      <template slot="cell(actions)" slot-scope="data" v-bind:attending="attending">
        <div :class="{
          attending: data.item.date_short.attending
        }">
          TODO
        </div>
      </template>
    </b-table>
  </div>
</template>
<script>
import GroupEventSummary from './GroupEventSummary'
import GroupEventsTableHeading from './GroupEventsTableHeading'
import InfiniteLoading from 'vue-infinite-loading'
import GroupEventsScrollTableFilters from './GroupEventsScrollTableFilters'
import { DATE_FORMAT, DEFAULT_PROFILE } from '../constants'
import moment from 'moment'
import GroupEventTableDate from './GroupEventTableDate'

export default {
  components: {
    GroupEventTableDate,
    GroupEventsScrollTableFilters, GroupEventsTableHeading, GroupEventSummary, InfiniteLoading},
  props: {
    events: {
      type: Array,
      required: true
    },
    canedit: {
      type: Boolean,
      required: true
    },
    addGroupName: {
      type: Boolean,
      required: true
    },
    limit: {
      type: Number,
      required: false,
      default: null
    },
    past: {
      type: Boolean,
      required: false,
      default: false
    },
    filters: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
    }
  },
  computed: {
    fields() {
      if (this.past) {
        return [
          { key: 'date_short', label: 'Short Date', sortable: true },
          { key: 'date_long', label: 'Long Date', sortable: true },
          { key: 'title', label: 'Event Title', sortable: true },
          { key: 'actions', label: 'Actions', },
        ]
      } else {
        return [
          { key: 'date_short', label: 'Short Date', sortable: true },
          { key: 'date_long', label: 'Long Date', sortable: true },
          { key: 'title', label: 'Event Title', sortable: true },
          { key: 'invited', label: 'Invited', sortable: true },
          { key: 'volunteers', label: 'Volunteers', sortable: true },
          { key: 'actions', label: 'Actions', },
        ]
      }
    },
    items() {
      if (this.past) {
        return this.events.map(e => {
          return {
            date_short: e,
            date_long: e,
            title: e,
            invited: e.allinvitedcount || '0',
            volunteers: e.volunteers || '0',
            actions: e
          }
        })
      } else {
        return this.events.map(e => {
          return {
            date_short: e,
            date_long: e,
            title: e,
            invited: e.allinvitedcount,
            volunteers: e.volunteers,
            actions: e
          }
        })
      }
    },
  },
  methods: {
    sortCompare(aRow, bRow, key, sortDesc, formatter, compareOptions, compareLocale) {
      const a = aRow[key]
      const b = bRow[key]

      if (key === 'date_short' || key === 'date_long') {
          return new moment(aRow.start).unix() - new moment(bRow.start).unix()
      } else {
        return toString(a).localeCompare(toString(b), compareLocale, compareOptions)
      }
    },
    upcoming(event) {
      let ret = false;

      if (event) {
        const start = new moment(event.event_date + ' ' + event.start)
        ret = start.isAfter()
      }

      return ret
    },
    finished(event) {
      let ret = false;

      if (event) {
        const end = new moment(event.event_date + ' ' + event.end)
        ret = end.isBefore()
      }

      return ret
    },
    inProgress(event) {
      return !this.upcoming(event) && !this.finished(event)
    },
    startingSoon(event) {
      return this.upcoming(event) && !this.finished(event) && (new moment().isSame(event.event_date, 'day'))
    },
    date(event) {
      return event ? (new moment(event.event_date).format(DATE_FORMAT)) : null
    },
    dayofmonth(event) {
      return event ? (new moment(event.event_date).format('DD')) : null
    },
    month(event) {
      return event ? (new moment(event.event_date).format('MMM').toUpperCase()) : null
    },
    start(event) {
      return event ? event.start.substring(0, 5) : null
    },
    end(event) {
      return event ? event.end.substring(0, 5) : null
    },
    volunteerCount(event) {
      return event && event.volunteers ? event.volunteers : 0
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.datebox {
  text-align: center;
  padding-top: 8px;
  font-weight: bold;

  .day {
    font-size: 1.7rem;
    line-height: 1.7rem;
  }

  .month {
    line-height: 1rem;
  }
}

.attending {
  background-color: $brand-grey;

  &.datecell {
    padding-top: 9px;
    padding-bottom: 9px;
    padding-left: 9px;
    padding-right: 9px;
    text-align: center;
    background-color: $black;
    width: 76px !important;

    .datebox {
      background-color: $black;
      color: $white;
    }
  }
}

.date {
  line-height: 1.3rem;
  text-align: center;
  padding-top: 13px;
  width: 150px;
  font-size: 15px;
}

.fullsize {
  font-size: 100%;
}

.cell-number {
  width: 60px;
}

/deep/ .hidecell {
  display: none;

  @include media-breakpoint-up(md) {
    display: table-cell;
  }
}

/deep/ .nounderline {
  text-decoration: none !important;
}
</style>