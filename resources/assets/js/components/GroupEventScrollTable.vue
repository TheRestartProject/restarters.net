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
          'w-100': true,
          attending: data.item.date_long.attending
        }">
          {{ date(data.item.date_short) }}
          <br class="hidecell" />
          {{ start(data.item.date_short) }} <span class="d-none d-md-inline">- {{ end(data.item.date_short) }}</span>
          <br class="d-block d-md-none" />
        </div>
        <div :class="{
          'text-left': true,
          'hidecell': true,
          'pl-3': true,
           attending: data.item.date_long.attending
        }">
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
          attending: data.item.title.attending
        }">
          <!-- eslint-disable-next-line -->
          <b>{{ data.item.title.venue ? data.item.title.venue : data.item.title.location }}<b-badge v-if="data.item.title.online" variant="primary" pill class="nounderline">{{ __('events.online') }}</b-badge></b>
        </a>
      </template>

      <template slot="head(invited)">
        <div class="hidecell">
          <b-img class="icon" src="/images/mail_ico.svg" :title="__('groups.volunteers_invited')" />
        </div>
      </template>
      <template slot="cell(invited)" slot-scope="data" v-bind:attending="attending">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          attending: data.item.invited.attending
        }">
          {{ data.item.invited.invited || '0' }}
        </div>
      </template>

      <template slot="head(volunteers)">
        <div class="hidecell">
          <b-img class="icon" src="/images/participants.svg" :title="__('groups.volunteers_confirmed')" />
        </div>
      </template>
      <template slot="cell(volunteers)" slot-scope="data" v-bind:attending="attending">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          attending: data.item.volunteers.attending
        }">
          {{ data.item.volunteers.volunteers || '0' }}
        </div>
      </template>

      <template slot="head(actions)">
        <span />
      </template>
      <template slot="cell(actions)" slot-scope="data" v-bind:attending="attending" v-bind:upcoming="upcoming">
        <div :class="{
          attending: data.item.actions.attending
        }">
          <div v-if="upcoming(data.item.actions)">
            <div v-if="data.item.actions.requiresModeration" class="cell-warning">
              <span v-if="data.item.actions.canModerate">
                <a :href="'/party/edit/' + data.item.actions.idevents">{{ __('partials.event_requires_moderation') }}</a>
              </span>
              <span v-else>
                {{ __('partials.event_requires_moderation_by_an_admin') }}
              </span>
            </div>
            <div v-else class="hidecell">
              <div v-if="data.item.actions.attending" class="text-black font-weight-bold d-flex justify-content-around">
                <span>
                  {{ __('events.youre_going') }}
                </span>
              </div>
              <!-- "all" or "nearby" events are for ones where we're not a member, so show a join button. -->
              <b-btn variant="primary" :href="'/group/join/' + data.item.actions.group.idgroups" v-else-if="data.item.actions.all || data.item.actions.nearby">
                {{ __('groups.join_group_button') }}
              </b-btn>
              <!-- We can't RSVP if the event is starting soon. -->
              <b-btn variant="primary" :href="'/party/join/' + data.item.actions.idevents" :disabled="startingSoon(data.item.actions)" v-else>
                {{ __('events.RSVP') }}
              </b-btn>
            </div>
          </div>
        </div>
      </template>

      <template slot="head(participants_count)">
        <div class="hidecell">
          <b-img class="icon" src="/images/participants.svg" :title="__('groups.participants_attended')" />
        </div>
      </template>
      <template slot="cell(participants_count)" slot-scope="data" v-bind:attending="attending">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          'cell-danger': data.item.participants_count.participants_count === 0,
          attending: data.item.participants_count.attending
        }">
          {{ data.item.participants_count.participants_count || '0' }}
        </div>
      </template>

      <template slot="head(volunteers_count)">
        <div class="hidecell">
          <b-img class="icon" src="/icons/volunteer_ico-thick.svg" :title="__('groups.volunteers_attended')" />
        </div>
      </template>
      <template slot="cell(volunteers_count)" slot-scope="data" v-bind:attending="attending">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          'cell-danger': data.item.volunteers_count.volunteers_count === 0,
          attending: data.item.volunteers_count.attending
        }">
          {{ data.item.volunteers_count.volunteers_count || '0' }}
        </div>
      </template>

      <template slot="head(ewaste)">
        <div class="hidecell">
          <b-img class="icon" src="/images/trash.svg" :title="__('groups.waste_prevented')" />
        </div>
      </template>
      <template slot="cell(ewaste)" slot-scope="data" v-bind:attending="attending" v-bind="stats">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          attending: data.item.ewaste.attending
        }"
         v-if="stats(data.item.ewaste)"
        >
          {{ Math.round(stats(data.item.ewaste).ewaste) }} kg
        </div>
      </template>

      <template slot="head(co2)">
        <div class="hidecell">
          <b-img class="icon" src="/images/cloud_empty.svg" :title="__('groups.co2_emissions_prevented')" />
        </div>
      </template>
      <template slot="cell(co2)" slot-scope="data" v-bind:attending="attending" v-bind="stats">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          attending: data.item.co2.attending
        }"
         v-if="stats(data.item.co2)"
        >
          {{ Math.round(stats(data.item.co2)).co2 }} kg
        </div>
      </template>

      <template slot="head(fixed_devices)">
        <div class="hidecell">
          <b-img class="icon" src="/images/fixed.svg" :title="__('groups.fixed_items')" />
        </div>
      </template>
      <template slot="cell(fixed_devices)" slot-scope="data" v-bind:attending="attending" v-bind="stats">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          attending: data.item.fixed_devices.attending
        }"
         v-if="stats(data.item.fixed_devices)"
        >
          {{ stats(data.item.fixed_devices).fixed_devices || '0'}}
        </div>
      </template>

      <template slot="head(repairable_devices)">
        <div class="hidecell">
          <b-img class="icon" src="/images/repairable_ico.svg" :title="__('groups.repairable_items')" />
        </div>
      </template>
      <template slot="cell(repairable_devices)" slot-scope="data" v-bind:attending="attending" v-bind="stats">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          attending: data.item.repairable_devices.attending
        }"
         v-if="stats(data.item.repairable_devices)"
        >
          {{ stats(data.item.repairable_devices).repairable_devices || '0' }}
        </div>
      </template>

      <template slot="head(dead_devices)">
        <div class="hidecell">
          <b-img class="icon" src="/images/dead_ico.svg" :title="__('groups.end_of_life_items')" />
        </div>
      </template>
      <template slot="cell(dead_devices)" slot-scope="data" v-bind:attending="attending" v-bind="stats">
        <div :class="{
          'hidecell': true,
          'cell-number': true,
          attending: data.item.dead_devices.attending
        }"
           v-if="stats(data.item.dead_devices)"
        >
          {{ stats(data.item.dead_devices).dead_devices || '0' }}
        </div>
      </template>

      <!--      TODO No devices warning -->

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
          { key: 'participants_count', label: 'Participants', sortable: true},
          { key: 'volunteers_count', label: 'Volunteers', sortable: true},
          { key: 'ewaste', label: 'ewaste', sortable: true},
          { key: 'co2', label: 'co2', sortable: true},
          { key: 'fixed_devices', label: 'Fixed Devices', sortable: true},
          { key: 'repairable_devices', label: 'Repairable Devices', sortable: true},
          { key: 'dead_devices', label: 'Dead Devices', sortable: true},
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
            actions: e,
            participants_count: e,
            volunteers_count: e,
            ewaste: e,
            co2: e,
            fixed_devices: e,
            repairable_devices: e,
            dead_devices: e,
          }
        })
      } else {
        return this.events.map(e => {
          return {
            date_short: e,
            date_long: e,
            title: e,
            invited: e,
            volunteers: e,
            actions: e
          }
        })
      }
    },
  },
  mounted() {
    // We have some classes which we want to apply to the table cells to colour the background.  The use of slots
    // and the way b-table works precludes us from applying them to the TD directly.  There are CSS attempts to do this
    // which don't work very well, and CSS has:() isn't supported yet.  So we have to hack this via JS - look for
    // the relevant classes and apply them to the parent too.
    this.$nextTick(() => {
      // this.$el.querySelector()
    })
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
    stats(event) {
      console.log("Stats", event, this.$store.getters['events/getStats'](event.idevents))
      return this.$store.getters['events/getStats'](event.idevents)
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
    display: block;
  }
}

/deep/ .nounderline {
  text-decoration: none !important;
}

/deep/ .icon {
  width: 30px;
}
</style>