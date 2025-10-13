<template>
  <div>
    <AlertBanner v-if="banner" />
    <CollapsibleSection class="lineheight" collapsed :count="upcomingOrActive.length" count-badge :heading-level="headingLevel">
      <template slot="title">
        <div class="d-flex flex-wrap flex-column flex-md-row">
          <div v-if="group">{{ group.name }}</div> {{ translatedTitle }}
          <div>
            <b-btn v-if="calendarCopyUrl" class="ml-0 ml-md-2" variant="primary" @click="showCalendar">
              <b-img-lazy :src="imageUrl('/images/subs_cal_ico.svg')" />
            </b-btn>
          </div>
        </div>
      </template>
      <template slot="title-right">
        <div class="d-flex flex-wrap w-100 justify-content-end mt-1 mt-md-0">
          <b-btn variant="primary" :href="'/export/groups/' + idgroups + '/events'" class="d-none d-md-block align-self-center text-nowrap mr-2" v-if="addButton">
            {{ __('groups.export_event_list') }}
          </b-btn>
          <b-btn variant="primary" href="/party/create" class="align-self-center text-nowrap mr-2 mr-md-0" v-if="addButton">
            <span class="d-none d-md-block">
              {{ __('events.add_new_event') }}
            </span>
            <span class="d-block d-md-none">
              {{ __('dashboard.add_event') }}
            </span>
          </b-btn>
        </div>
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
import images from '../mixins/images'
import moment from 'moment'
import CalendarAddModal from './CalendarAddModal.vue'
import GroupEventsTab from './GroupEventsTab.vue'
import CollapsibleSection from './CollapsibleSection.vue'
import AlertBanner from './AlertBanner.vue'

export default {
  components: {
    CollapsibleSection,
    GroupEventsTab,
    CalendarAddModal,
    AlertBanner,
  },
  mixins: [ group, images ],
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
    banner: {
      type: Boolean,
      required: false,
      default: false
    }
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
        return this.__('groups.no_other_nearby_events')
      } else {
        // We don't have a location - nudge to add one.
        return this.__('events.no_location')
      }
    },
    translatedTitle() {
      // If we have a group then we are putting the name elsewhere and just want "events" (force the plural).  Otherwise
      // "Your events".
      let ret = this.group ? this.__('groups.events') : this.__('events.your_events')
      ret = ret.charAt(0).toUpperCase() + ret.slice(1)
      return ret
    },
    translatedCalendarTitle() {
      return this.__('groups.calendar_copy_title', {
        group: this.group ? this.group.name : this.__('groups.groups_title1').toLowerCase()
      })
    },
    translatedCalendarDescription() {
      return this.__('groups.calendar_copy_description', {
        group: this.group ? this.group.name : this.__('groups.groups_title1').toLowerCase()
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
