<template>
  <div>
    <GroupEventsScrollTableFilters v-if="filters" />
    <b-table
        :fields="fields"
        :items="items"
        sort-null-last
        :sort-compare="sortCompare"
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
        <span />
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
          <span v-if="addGroupName" class="small">
            <a :href="'/group/view/' + data.item.title.group.idgroups">{{ data.item.title.group.name }}</a>
          </span>
        </div>
      </template>

      <template slot="head(invited)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/images/mail_ico.svg" :title="__('groups.volunteers_invited')" />
        </div>
      </template>
      <template slot="cell(invited)" slot-scope="data">
        <GroupEventsScrollTableNumber :value="data.item.invited.allinvitedcount" />
      </template>

      <template slot="head(volunteers)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/images/participants.svg" :title="__('groups.volunteers_confirmed')" />
        </div>
      </template>
      <template slot="cell(volunteers)" slot-scope="data">
        <GroupEventsScrollTableNumber :value="data.item.volunteers.volunteers" />
      </template>

      <template slot="head(actions)">
        <span />
      </template>
      <template slot="cell(actions)" slot-scope="data" v-bind:upcoming="upcoming">
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
      </template>

      <template slot="head(participants_count)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/images/participants.svg" :title="__('groups.participants_attended')" />
        </div>
      </template>
      <template slot="cell(participants_count)" slot-scope="data">
        <GroupEventsScrollTableNumber :value="data.item.participants_count.participants_count" />
      </template>

      <template slot="head(volunteers_count)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/icons/volunteer_ico-thick.svg" :title="__('groups.volunteers_attended')" />
        </div>
      </template>
      <template slot="cell(volunteers_count)" slot-scope="data">
        <GroupEventsScrollTableNumber :value="data.item.volunteers_count.volunteers_count" />
      </template>

      <template slot="head(ewaste)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/images/trash.svg" :title="__('groups.waste_prevented')" />
        </div>
      </template>
      <template slot="cell(ewaste)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="Math.round(stats(data.item.ewaste).ewaste)" units="kg" />
      </template>

      <template slot="head(co2)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/images/cloud_empty.svg" :title="__('groups.co2_emissions_prevented')" />
        </div>
      </template>
      <template slot="cell(co2)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="Math.round(stats(data.item.co2).co2)" units="kg" />
      </template>

      <template slot="head(fixed_devices)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/images/fixed.svg" :title="__('groups.fixed_items')" />
        </div>
      </template>
      <template slot="cell(fixed_devices)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="stats(data.item.fixed_devices).fixed_devices" v-if="stats(data.item.fixed_devices)" />
      </template>

      <template slot="head(repairable_devices)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/images/repairable_ico.svg" :title="__('groups.repairable_items')" />
        </div>
      </template>
      <template slot="cell(repairable_devices)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="stats(data.item.repairable_devices).repairable_devices" v-if="stats(data.item.repairable_devices)" />
      </template>

      <template slot="head(dead_devices)">
        <div class="hidecell text-center">
          <b-img class="icon" src="/images/dead_ico.svg" :title="__('groups.end_of_life_items')" />
        </div>
      </template>
      <template slot="cell(dead_devices)" slot-scope="data" v-bind="stats">
        <GroupEventsScrollTableNumber :value="stats(data.item.dead_devices).dead_devices" v-if="stats(data.item.dead_devices)" />
      </template>

      <!--      TODO No devices warning -->
      <!--      TODO Sorting-->

    </b-table>
  </div>
</template>
<script>
import GroupEventSummary from './GroupEventSummary'
import GroupEventsTableHeading from './GroupEventsTableHeading'
import InfiniteLoading from 'vue-infinite-loading'
import GroupEventsScrollTableFilters from './GroupEventsScrollTableFilters'
import GroupEventTableDate from './GroupEventTableDate'
import GroupEventsScrollTableDateShort from './GroupEventsScrollTableDateShort'
import GroupEventsScrollTableDateLong from './GroupEventsScrollTableDateLong'
import EventTitle from './EventTitle'
import GroupEventsScrollTableNumber from './GroupEventsScrollTableNumber'
import moment from 'moment'

export default {
  components: {
    GroupEventsScrollTableNumber,
    EventTitle,
    GroupEventsScrollTableDateLong,
    GroupEventsScrollTableDateShort,
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
          { key: 'participants_count', label: 'Participants', sortable: true, tdClass: this.dangerIfZero},
          { key: 'volunteers_count', label: 'Volunteers', sortable: true, tdClass: this.dangerIfZero},
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
    stats(event) {
      return this.$store.getters['events/getStats'](event.idevents)
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
      return event[key] <= 0 ? 'cell-danger': ''
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.fullsize {
  font-size: 100%;
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

/deep/ .attending {
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
</style>