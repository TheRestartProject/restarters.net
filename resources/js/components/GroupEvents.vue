<template>
  <div>
    <CollapsibleSection class="lineheight" collapsed :count="upcomingOrActive.length" count-badge :heading-level="headingLevel">
      <template slot="title">
        <div class="d-flex justify-content-between w-100">
        <div>
          <span v-if="group">{{ group.name }}</span> {{ translatedTitle }}
          <b-btn v-if="calendarCopyUrl" class="ml-2" variant="primary" @click="showCalendar">
            <b-img-lazy src="/images/subs_cal_ico.svg" />
          </b-btn>
        </div>
        </div>
      </template>
      <template slot="title-right">
        <b-btn variant="primary" href="/party/create" class="align-self-center text-nowrap" v-if="addButton">
          {{ __('events.add_new_event') }}
        </b-btn>
      </template>
      <template slot="content">
        <b-tabs class="ourtabs w-100">
          <GroupEventsTab active :limit="limit" :events="upcomingOrActive" :canedit="canedit" :add-group-name="addGroupName" title="groups.upcoming_active" noneMessage="groups.no_upcoming_events" />
          <GroupEventsTab :limit="limit" :events="past" :canedit="canedit" :add-group-name="addGroupName" title="groups.past" noneMessage="groups.no_past_events" past />
        </b-tabs>
      </template>
    </CollapsibleSection>
    <CollapsibleSection class="lineheight mt-4" collapsed :count="upcomingOrActive.length" count-badge :heading-level="headingLevel" v-if="showOther">
      <template slot="title">
        <div class="d-flex justify-content-between w-100">
          <div>
            <span v-if="group">{{ group.name }}</span> {{ __('events.other_events') }}
          </div>
        </div>
      </template>
      <template slot="content">
        <b-tabs class="ourtabs w-100">
          <GroupEventsTab active :limit="limit" :events="nearby" :canedit="canedit" :add-group-name="addGroupName" title="groups.nearby" :noneMessage="nearbyNoneMessage" />
          <GroupEventsTab :limit="limit" :events="all" :canedit="canedit" :add-group-name="addGroupName" title="groups.all" noneMessage="groups.no_other_events" filters />
        </b-tabs>
      </template>
    </CollapsibleSection>
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
import moment from 'moment'
import CalendarAddModal from './CalendarAddModal'
import GroupEventsTab from './GroupEventsTab'
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {
    CollapsibleSection,
    GroupEventsTab,
    CalendarAddModal,
  },
  mixins: [ group ],
  props: {
    idgroups: {
      type: Number,
      required: false,
      default: null
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
    },
    addButton: {
      type: Boolean,
      required: true
    },
    addGroupName: {
      type: Boolean,
      required: false
    },
    initialEvents: {
      type: Array,
      required: false,
      default: null
    },
    showOther: {
      type: Boolean,
      required: false,
      default: false
    },
    location: {
      type: String,
      required: false,
      default: null
    },
  },
  computed: {
    events() {
      return this.$store.getters['events/getByGroup'](this.idgroups).sort((a,b) => new moment(a.event_start_utc).unix() - new moment(b.event_start_utc).unix())
    },
    reverse() {
      return this.$store.getters['events/getByGroup'](this.idgroups).sort((a,b) => new moment(b.event_start_utc).unix() - new moment(a.event_start_utc).unix())
    },
    past() {
      return this.reverse.filter(e => e.finished && !e.nearby && !e.all)
    },
    upcomingOrActive() {
      return this.events.filter(e => (e.upcoming || e.inprogress) && !e.nearby && !e.all)
    },
    nearby() {
      return this.events.filter(e => e.nearby)
    },
    all() {
      return this.events.filter(e => e.all)
    },
    nearbyNoneMessage() {
      if (this.location) {
        // We have a location, so we can say that there are no other nearby events.
        return this.$lang.get('groups.no_other_nearby_events')
      } else {
        // We don't have a location - nudge to add one.
        return this.$lang.get('events.no_location')
      }
    },
    translatedTitle() {
      // If we have a group then we are putting the name elsewhere and just want "events" (force the plural).  Otherwise
      // "Your events".
      let ret = this.group ? this.$lang.choice('groups.events', {
        value: 2
      }) : this.$lang.get('events.your_events')

      ret = ret.charAt(0).toUpperCase() + ret.slice(1)
      return ret
    },
    translatedCalendarTitle() {
      return this.$lang.get('groups.calendar_copy_title', {
        group: this.group ? this.group.name : this.$lang.get('groups.groups_title1').toLowerCase()
      })
    },
    translatedCalendarDescription() {
      return this.$lang.get('groups.calendar_copy_description', {
        group: this.group ? this.group.name : this.$lang.get('groups.groups_title1').toLowerCase()
      })
    },
  },
  methods: {
    showCalendar() {
      this.$refs.calendar.show()
    },
  },
  mounted () {
    // Data can be passed from the blade template to us via props.
    if (this.initialEvents) {
      this.initialEvents.forEach(e => {
        this.$store.dispatch('events/setStats', {
          idevents: e.idevents,
          stats: e.stats
        })
      })

      this.$store.dispatch('events/setList', {
        events: this.initialEvents
      })
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.lineheight {
  line-height: 2;
}

.lower {
  text-transform: lowercase;
}

.ourtabs {
  max-height: 600px;
  overflow-y: hidden;
}

.border-bottom-thick {
  border-top: 5px solid $black;
}

::v-deep .fontsize {
  //Override standard sizes for cosmetic purposes.
  font-size: 18px !important;
}
</style>
