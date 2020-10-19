<template>
  <CollapsibleSection class="lineheight" collapsed :count="upcoming.length" count-badge>
    <template slot="title">
      <div class="d-flex justify-content-between w-100">
        <div>
          {{ translatedTitle }}
          <b-btn v-if="calendarCopyUrl" class="ml-2" variant="none" @click="showCalendar">
            <b-img-lazy src="/images/subs_cal_ico.svg" />
          </b-btn>
        </div>
        <b-btn variant="primary" href="/party/create" class="align-self-center">
          {{ translatedAddEvent }}
        </b-btn>
      </div>
      <CalendarAddModal ref="calendar" :copy-url="calendarCopyUrl" :edit-url="calendarEditUrl" v-if="calendarCopyUrl">
        <template slot="title">
          {{ translatedCalendarTitle }}
        </template>
        <template slot="description">
          {{ translatedCalendarDescription }}
        </template>
      </CalendarAddModal>
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
          <b-table-simple v-else responsive class="pl-0 pl-md-3 pr-0 pr-md-3 pb-2 mb-2" table-class="m-0 leave-tables-alone">
            <GroupEventsTableHeading />
            <b-tbody class="borders">
              <GroupEventSummary v-for="e in upcomingFirst" :key="'event-' + e.idevents" :idevents="e.idevents" />
            </b-tbody>
          </b-table-simple>
          <div class="text-right">
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
            <b-tbody class="borders">
              <GroupEventSummary v-for="e in pastFirst" :key="'event-' + e.idevents" :idevents="e.idevents" />
            </b-tbody>
          </b-table-simple>
          <div class="text-right">
            <b-btn variant="link" :href="'/party/group/' + groupId">
              {{ translatedSeeAll }}
            </b-btn>
          </div>
        </b-tab>
      </b-tabs>
    </template>
  </CollapsibleSection>
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
    }
  },
  data () {
    return {
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
    translatedSeeAll() {
      return this.$lang.get('events.event_all')
    },
    translatedCalendarTitle() {
      return this.$lang.get('groups.calendar_copy_title')
    },
    translatedCalendarDescription() {
      return this.$lang.get('groups.calendar_copy_description')
    },
    past() {
      return this.events.filter(e => {
          const start = new moment(e.event_date + ' ' + e.start)
          return start.isBefore()
      })
    },
    pastFirst() {
      return this.past.slice(0, 3)
    },
    upcoming() {
      return this.events.filter(e => {
        const start = new moment(e.event_date + ' ' + e.start)
        return start.isAfter()
      })
    },
    upcomingFirst() {
      return this.upcoming.slice(0, 3)
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
</style>