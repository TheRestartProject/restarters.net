<template>
  <div :class="{
    minHeight: minHeight
}">
    <GroupEventsScrollTableFilters
        v-if="filters"
        :events="events"
        :title.sync="searchTitle"
        :country.sync="searchCountry"
        :start.sync="searchStart"
        :end.sync="searchEnd"
        @calendarOpen="minHeight = true"
        @calendarClose="minHeight = false"
        @countryOpen="minHeight = true"
        @countryClose="minHeight = false"
    />
    <b-table
        :fields="fields"
        :items="filteredItems"
        sort-null-last
        :sort-compare="sortCompare"
        :sort-by="sortBy"
        :sort-desc="sortDesc"
        sticky-header="50vh"
        responsive
        class="mt-2 pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2"
        table-class="m-0 leave-tables-alone"
        :tbody-tr-class="rowClass"
    >

      <template slot="head(date_short)">
        <span />
      </template>
      <template slot="cell(date_short)" slot-scope="data">
        <GroupEventsScrollTableDateShort :idevents="data.item.date_short.idevents" />
      </template>

      <template slot="head(date_long)">
        <div class="text-left">
          <b-img class="icon mt-3 ml-3" src="/images/clock.svg" />
        </div>
      </template>
      <template slot="cell(date_long)" slot-scope="data">
        <GroupEventsScrollTableDateLong :idevents="data.item.date_long.idevents" />
      </template>

      <template slot="head(title)">
        <span />
      </template>
      <template slot="cell(title)" slot-scope="data" v-bind:addGroupName="addGroupName">
        <b><EventTitle :idevents="data.item.title.idevents" component="a" :href="'/party/view/' + data.item.title.idevents" /></b>
        <div class="hidecell">
          <span v-if="addGroupName && data.item.title.group" class="small">
            <a :href="'/group/view/' + data.item.title.group.idgroups">{{ data.item.title.group.name }}</a>
          </span>
        </div>
      </template>

      <template slot="head(invited)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/images/mail_ico.svg" :title="__('groups.volunteers_invited')" />
        </div>
      </template>
      <template slot="cell(invited)" slot-scope="data">
        <GroupEventsScrollTableNumber :value="data.item.invited.allinvitedcount" />
      </template>

      <template slot="head(volunteers)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/images/participants.svg" :title="__('groups.volunteers_confirmed')" />
        </div>
      </template>
      <template slot="cell(volunteers)" slot-scope="data">
        <GroupEventsScrollTableNumber :value="data.item.volunteers.volunteers" />
      </template>

      <template slot="head(actions)">
        <span />
      </template>
      <template slot="cell(actions)" slot-scope="data" v-bind:upcoming="upcoming">
        <GroupEventsScrollTableActions :idevents="data.item.actions.idevents" class="actionsHeight" />
      </template>

      <template slot="head(participants_count)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/images/participants.svg" :title="__('groups.participants_attended')" />
        </div>
      </template>
      <template slot="cell(participants_count)" slot-scope="data">
        <GroupEventsScrollTableNumber :value="data.item.participants_count.participants_count" />
      </template>

      <template slot="head(volunteers_count)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/icons/volunteer_ico-thick.svg" :title="__('groups.volunteers_attended')" />
        </div>
      </template>
      <template slot="cell(volunteers_count)" slot-scope="data">
        <GroupEventsScrollTableNumber :value="data.item.volunteers_count.volunteers_count" />
      </template>

      <template slot="head(waste)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/images/trash.svg" :title="__('groups.waste_prevented')" />
        </div>
      </template>
      <template slot="cell(waste)" slot-scope="data" v-bind="stats">
        <div v-if="noDevices(data.item.waste)" class="d-none d-md-block">
          {{ __('partials.no_devices_added') }}
          <a :href="'/party/view/' + data.item.waste.idevents">
            {{ __('partials.add_a_device') }}
          </a>
        </div>
        <GroupEventsScrollTableNumber v-else :value="Math.round(stats(data.item.waste).waste_total)" units="kg" />
      </template>

      <template slot="head(co2)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/images/cloud_empty.svg" :title="__('groups.co2_emissions_prevented')" />
        </div>
      </template>
      <template slot="cell(co2)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="Math.round(stats(data.item.co2).co2_total)" units="kg" />
      </template>

      <template slot="head(fixed_devices)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/images/fixed.svg" :title="__('groups.fixed_items')" />
        </div>
      </template>
      <template slot="cell(fixed_devices)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="stats(data.item.fixed_devices).fixed_devices" v-if="stats(data.item.fixed_devices)" />
      </template>

      <template slot="head(repairable_devices)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/images/repairable_ico.svg" :title="__('groups.repairable_items')" />
        </div>
      </template>
      <template slot="cell(repairable_devices)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="stats(data.item.repairable_devices).repairable_devices" v-if="stats(data.item.repairable_devices)" />
      </template>

      <template slot="head(dead_devices)">
        <div class="hidecell text-center">
          <b-img class="icon mt-3" src="/images/dead_ico.svg" :title="__('groups.end_of_life_items')" />
        </div>
      </template>
      <template slot="cell(dead_devices)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="stats(data.item.dead_devices).dead_devices" v-if="stats(data.item.dead_devices)" />
      </template>

    </b-table>
  </div>
</template>
<script>
import moment from 'moment'
import InfiniteLoading from 'vue-infinite-loading'
import GroupEventsScrollTableDateShort from './GroupEventsScrollTableDateShort.vue'
import GroupEventsScrollTableDateLong from './GroupEventsScrollTableDateLong.vue'
import GroupEventsScrollTableNumber from './GroupEventsScrollTableNumber.vue'
import GroupEventsScrollTableActions from './GroupEventsScrollTableActions.vue'
import GroupEventsScrollTableFilters from './GroupEventsScrollTableFilters.vue'
import EventTitle from './EventTitle.vue'

export default {
  components: {
    EventTitle,
    GroupEventsScrollTableFilters,
    GroupEventsScrollTableActions,
    GroupEventsScrollTableNumber,
    GroupEventsScrollTableDateLong,
    GroupEventsScrollTableDateShort,
    InfiniteLoading
  },
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
    },
    sortBy: {
      type: String,
      required: false,
      default: 'date_long'
    },
    sortDesc: {
      type: Boolean,
      required: false,
      default: true
    }
  },
  data () {
    return {
      searchTitle: null,
      searchCountry: null,
      searchStart: null,
      searchEnd: null,
      minHeight: false
    }
  },
  computed: {
    fields() {
      if (this.past) {
        return [
          { key: 'date_short', label: 'Short Date', tdClass: 'pl-0 pr-0 datetd' },
          { key: 'date_long', label: 'Long Date', sortable: true },
          { key: 'title', label: 'Event Title', sortable: true },
          { key: 'actions', label: 'Actions', },
          { key: 'participants_count', label: 'Participants', sortable: true, tdClass: this.dangerIfZero},
          { key: 'volunteers_count', label: 'Volunteers', sortable: true, tdClass: this.dangerIfOne},
          { key: 'waste', label: 'waste', sortable: true, tdClass: this.noDevicesError},
          { key: 'co2', label: 'co2', sortable: true},
          { key: 'fixed_devices', label: 'Fixed Devices', sortable: true},
          { key: 'repairable_devices', label: 'Repairable Devices', sortable: true},
          { key: 'dead_devices', label: 'Dead Devices', sortable: true},
        ]
      } else {
        return [
          { key: 'date_short', label: 'Short Date', tdClass: 'pl-0 pr-0 datetd' },
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
            waste: e,
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
    filteredItems() {
      return this.items.filter(item => {
        // Any of the fields contains the event.
        const event = item.title

        let match = true

        if (this.searchTitle) {
          const title = event.venue ? event.venue : event.location
          match &= title.toLowerCase().indexOf(this.searchTitle.toLowerCase()) !== -1
        }

        if (this.searchCountry) {
          match &= event.group.country && event.group.country.toLowerCase().indexOf(this.searchCountry.country.toLowerCase()) !== -1
        }

        if (this.searchStart || this.searchEnd) {
          // Either or both can be set.  This allows searching for all past or all future.
          const date = new moment(event.event_date_local)

          if (this.searchStart && this.searchEnd) {
            match &= date.isBetween(new moment(this.searchStart), new moment(this.searchEnd), undefined, '[]')
          } else if (this.searchStart) {
            match &= date.isSameOrAfter(new moment(this.searchStart))
          } else {
            match &= date.isSameOrBefore(new moment(this.searchEnd))
          }
        }

        return match
      })
    }
  },
  methods: {
    sortCompare(aRow, bRow, key, sortDesc, formatter, compareOptions, compareLocale) {
      const a = aRow[key]
      const b = bRow[key]
      let ret = 0

      try {
        switch (key) {
          case 'date_short':
          case 'date_long':
            if (this.past) {
              // Show past events most recent first.
              ret = new moment(a.event_start_utc).unix() - new moment(b.event_start_utc).unix()
            } else {
              ret = new moment(b.event_start_utc).unix() - new moment(a.event_start_utc).unix()
            }
            break
          case 'title':
            const atitle = a.venue ? a.venue : a.location
            const btitle = b.venue ? b.venue : b.location
            ret = atitle.toLowerCase().localeCompare(btitle.toLowerCase())
            break
          case 'participants_count':
          case 'volunteers_count':
          case 'waste':
          case 'co2':
          case 'fixed_devices':
          case 'repairable_devices':
          case 'dead_devices':
          case 'invited':
          case 'volunteers':
            ret = parseInt(a['stats'][key]) - parseInt(b['stats'][key])
            break
          default:
            ret = toString(a).localeCompare(toString(b), compareLocale, compareOptions)
            break
        }
      } catch (e) {
        console.error("Sort exception", e)
      }

      if (!this.sortDesc) {
        ret = -ret
      }

      return ret
    },
    stats(event) {
      return this.$store.getters['events/getStats'](event.idevents)
    },
    noDevices(event) {
      const stats = this.stats(event)

      return stats && (stats.fixed_devices + stats.repairable_devices + stats.dead_devices === 0) && this.canedit && event.finished
    },
    rowClass(item) {
      // This gets called to supply a class for the tr of the table.  We want to highlight the rows where we are
      // attending.
      //
      // The data structure is confusing; the parameter here is the data structure for the whole row, which
      // contains properties for each field.  We set each of those up in items() to be the event, so we can just
      // pick any property (title, say) to get access to the event.
      let ret = ''

      if (item && item.title && item.title.attending) {
        // Highlight rows where we are attending
        ret = 'attending'
      }

      return ret
    },
    dangerIfZero(event, key, item) {
      // We want to flag some cells if they contain zero values.
      return event[key] <= 0 ? 'cell-danger': ''    },
    dangerIfOne(event, key, item) {
      // We want to flag some cells if they contain one or less.
      return event[key] <= 1 ? 'cell-danger': ''    },
    noDevicesError(event, key, item) {
      // We want to flag if there are no devices when there should be.
      //
      // We can't use colspan in b-table, so this warning will get squeezed into this cell rather than replacing
      // all the stats as it used to.
      return this.noDevices(event) ? 'cell-danger' : ''
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

::v-deep .hidecell {
  display: none;

  @include media-breakpoint-up(md) {
    display: block;
  }
}

::v-deep .nounderline {
  text-decoration: none !important;
}

::v-deep .icon {
  width: 30px;
  height: 30px;
}

::v-deep {
  .datetd {
    width: 87px;
    min-height: 87px;
  }
}

::v-deep .table.b-table > thead > tr {
  background-position-x: center !important;
}

::v-deep .attending {
  background-color: $brand-grey;

  .datetd {
    background-color: $black;

    .datecell {
      background-color: $black;

      .datebox {
        background-color: $black;
        color: $white;
      }
    }
  }
}

// The multiselect is used in a few places, and we have some inconsistencies in styling.  Here we force it to match
// the behaviour of the inputs.
::v-deep .multiselect {
  &.multiselect--active {
    border: 0 !important;

    input {
      margin-left: 6px;
      margin-top: 2px;
      margin-bottom: 4px;
    }
  }

  .multiselect__tags {
    padding: 2px 40px 3px 12px !important;
    border: 2px solid #222 !important;
  }
}

::v-deep td[aria-colindex="6"] {
  // Hack so we can get the cell warning full height.
  height: 1px;

  div {
    height: 100%
  }
}

.minHeight {
  min-height: 330px;
}

.actionsHeight {
  height: unset !important;
}
</style>