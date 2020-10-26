<template>
  <div>
    <CollapsibleSection class="lineheight d-none d-md-block" collapsed :count="upcoming.length" count-badge :heading-level="headingLevel">
      <template slot="title">
        <div class="d-flex justify-content-between w-100">
          <div>
            {{ translatedTitle }}
            <b-btn v-if="calendarCopyUrl" class="ml-2" variant="primary" @click="showCalendar">
              <b-img-lazy src="/images/subs_cal_ico.svg" />
            </b-btn>
          </div>
          <b-btn variant="primary" href="/party/create" class="align-self-center">
            <span class="d-none d-md-block">
              {{ translatedAddEvent }}
            </span>
          </b-btn>
        </div>
      </template>
      <template slot="content">
        <b-tabs class="ourtabs w-100">
          <b-tab active title-item-class="w-50" class="pt-2">
            <template slot="title">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ translatedUpcoming }}</b> ({{ upcoming.length }})
                </div>
              </div>
            </template>
            <p v-if="!upcoming.length">
              {{ translatedNoUpcoming }}.
            </p>
            <b-table-simple v-else sticky-header="50vh" responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
              <GroupEventsTableHeading />
              <b-tbody class="table-height">
                <GroupEventSummary v-for="e in upcomingToShow" :key="'event-' + e.idevents" :idevents="e.idevents" :canedit="canedit" />
              </b-tbody>
            </b-table-simple>
            <div class="text-center" v-if="limit">
              <b-btn variant="link" :href="'/party/group/' + groupId">
                {{ translatedSeeAll }}
              </b-btn>
            </div>
          </b-tab>
          <b-tab title-item-class="w-50" class="pt-2">
            <template slot="title">
              <div class="d-flex justify-content-between">
                <div>
                  <b>{{ translatedPast }}</b> ({{ past.length }})
                </div>
              </div>
            </template>
            <p v-if="!past.length">
              {{ translatedNoPast }}.
            </p>
            <b-table-simple v-else responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
              <GroupEventsTableHeading past />
              <b-tbody class="table-height">
                <GroupEventSummary v-for="e in pastToShow" :key="'event-' + e.idevents" :idevents="e.idevents" :canedit="canedit" />
              </b-tbody>
            </b-table-simple>
            <div class="text-center" v-if="limit">
              <b-btn variant="link" :href="'/party/group/' + groupId">
                {{ translatedSeeAll }}
              </b-btn>
            </div>
          </b-tab>
        </b-tabs>
      </template>
    </CollapsibleSection>
    <div class="d-block d-md-none">
      <h2 class="border-bottom-thick d-flex justify-content-between w-100 pt-2">
        {{ translatedTitle }}
        <b-btn v-if="calendarCopyUrl" class="ml-2" variant="primary" @click="showCalendar">
          <b-img-lazy class="mobileicon" src="/images/subs_cal_ico.svg" />
        </b-btn>
        <b-btn variant="primary" href="/party/create" class="align-self-center">
        <span class="d-block d-md-none">
          {{ translatedAddEventMobile }}
        </span>
        </b-btn>
      </h2>
      <CollapsibleSection class="lineheight" collapsed :count="upcoming.length" count-class="black" :heading-level="headingSubLevel">
        <template slot="title">
          <span class="text-uppercase">
            {{ translatedUpcoming }}
          </span>
        </template>
        <template slot="content">
          <p v-if="!upcoming.length">
            {{ translatedNoUpcoming }}.
          </p>
          <b-table-simple v-else sticky-header="50vh" responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
            <GroupEventsTableHeading />
            <b-tbody class="table-height">
              <GroupEventSummary v-for="e in upcomingToShow" :key="'event-' + e.idevents" :idevents="e.idevents" :canedit="canedit" />
            </b-tbody>
          </b-table-simple>
          <div class="text-right" v-if="limit">
            <b-btn variant="link" :href="'/party/group/' + groupId">
              {{ translatedSeeAll }}
            </b-btn>
          </div>
        </template>
      </CollapsibleSection>
      <CollapsibleSection class="lineheight" collapsed :count="past.length" count-class="black" :heading-level="headingSubLevel">
        <template slot="title">
          <span class="text-uppercase">
          {{ translatedPast }}
          </span>
        </template>
        <template slot="content">
          <p v-if="!upcoming.length">
            {{ translatedNoPastEvents }}.
          </p>
          <b-table-simple v-else sticky-header="50vh" responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
            <GroupEventsTableHeading past />
            <b-tbody class="table-height">
              <GroupEventSummary v-for="e in pastToShow" :key="'event-' + e.idevents" :idevents="e.idevents" :canedit="canedit" />
            </b-tbody>
          </b-table-simple>
          <div class="text-right" v-if="limit">
            <b-btn variant="link" :href="'/party/group/' + groupId">
              {{ translatedSeeAll }}
            </b-btn>
          </div>
        </template>
      </CollapsibleSection>
    </div>
    <CalendarAddModal ref="calendar" :copy-url="calendarCopyUrl" :edit-url="calendarEditUrl" v-if="calendarCopyUrl">
      <template slot="title">
        {{ translatedCalendarTitle }}
      </template>
      <template slot="description">
        {{ translatedCalendarDescription }}
      </template>
    </CalendarAddModal>
  </div>
</template>
<script>
import group from '../mixins/group'
import CollapsibleSection from './CollapsibleSection'
import GroupEventsTableHeading from './GroupEventsTableHeading'
import moment from 'moment'
import GroupEventSummary from './GroupEventSummary'
import CalendarAddModal from './CalendarAddModal'

export default {
  components: {CalendarAddModal, GroupEventSummary, CollapsibleSection, GroupEventsTableHeading},
  mixins: [ group ],
  props: {
    events: {
      type: Array,
      required: true
    },
    calendarCopyUrl: {
      type: String,
      required: false,
      default: null
    },
    calendarEditUrl: {
      type: String,
      required: false,
      default: null
    },
    limit: {
      type: Number,
      required: false,
      default: null
    },
    headingLevel: {
      type: String,
      required: true
    },
    headingSubLevel: {
      type: String,
      required: true
    }
  },
  computed: {
    translatedTitle() {
      return this.$lang.get('groups.group_events')
    },
    translatedUpcoming() {
      return this.$lang.get('groups.upcoming_active')
    },
    translatedNoUpcoming() {
      return this.$lang.get('groups.no_upcoming_events')
    },
    translatedNoPastEvents() {
      return this.$lang.get('groups.no_past_events')
    },
    translatedPast() {
      return this.$lang.get('groups.past')
    },
    translatedAddEvent() {
      return this.$lang.get('events.add_new_event')
    },
    translatedAddEventMobile() {
      return this.$lang.get('events.add_new_event_mobile')
    },
    translatedSeeAll() {
      return this.$lang.get('events.event_all')
    },
    translatedCalendarTitle() {
      return this.$lang.get('groups.calendar_copy_title', {
        group: this.group.name
      })
    },
    translatedCalendarDescription() {
      return this.$lang.get('groups.calendar_copy_description', {
        group: this.group.name
      })
    },
    past() {
      return this.events.filter(e => {
          const start = new moment(e.event_date + ' ' + e.start)
          return start.isBefore()
      })
    },
    pastToShow() {
      return this.limit ? this.past.slice(0, this.limit) : this.past
    },
    upcoming() {
      return this.events.filter(e => {
        const start = new moment(e.event_date + ' ' + e.start)
        return start.isAfter()
      })
    },
    upcomingToShow() {
      return this.limit ? this.upcoming.slice(0, this.limit) : this.upcoming
    }
  },
  methods: {
    showCalendar() {
      this.$refs.calendar.show()
    }
  },
  created() {
    // The events are passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    this.$store.dispatch('events/setList', {
      events: this.events
    })
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.lineheight {
  line-height: 2;
}

.readmore {
  white-space: pre-wrap !important;
}

.icon {
  width: 20px;
  margin-bottom: 3px;
}

.lower {
  text-transform: lowercase;
}

::v-deep .table-height {
  height: 600px;
  overflow-y: scroll;
}

.mobileicon {
  height: 16px
}

.border-bottom-thick {
  border-top: 5px solid $black;
}
</style>