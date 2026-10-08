<template>
  <div v-if="group">
    <AlertBanner />
    <div class="alert alert-success" v-if="haveLeft" v-html="translatedHaveLeft" />
    <GroupHeading
        :idgroups="idgroups"
        :canedit="canedit"
        :can-see-delete="canSeeDelete"
        :can-perform-delete="canPerformDelete"
        :can-perform-archive="canPerformArchive"
        :ingroup="ingroup"
        :reporting-url="reportingUrl || null"
        @left="haveLeft = true"
    />

    <div class="d-flex flex-wrap">
      <div class="w-xs-100 w-md-50">
        <GroupDescription class="pr-md-3" :idgroups="idgroups" :discourse-group="discourseGroup" />
      </div>
      <div class="w-xs-100 w-md-50">
        <GroupVolunteers class="pl-md-3" :idgroups="idgroups" :canedit="canedit" />
      </div>
    </div>

    <div v-if="statsLayout !== 'after'" class="vue w-100 mt-md-50 stats-before-events">
      <GroupStats
          :idgroups="idgroups"
          :stats="groupStats"
          :device-stats="deviceStats"
          :top-devices="topDevices"
          :reporting-url="reportingUrl || null"
          :part="statsLayout === 'split' ? 'headline' : 'all'"
          :compact="statsLayout === 'compact'"
      />
    </div>

    <hr style="color: white; border-top: 1px solid black;" />
    <GroupEvents
        heading-level="h2"
        heading-sub-level="h3"
        :idgroups="idgroups"
        :canedit="canedit"
        :limit="3"
        :calendar-copy-url="calendarCopyUrl"
        :calendar-edit-url="calendarEditUrl"
        add-button
    />

    <div v-if="statsLayout === 'after' || statsLayout === 'split'" class="vue w-100 mt-md-50 stats-after-events">
      <GroupStats
          :idgroups="idgroups"
          :stats="groupStats"
          :device-stats="deviceStats"
          :top-devices="topDevices"
          :reporting-url="reportingUrl || null"
          :part="statsLayout === 'split' ? 'items' : 'all'"
      />
    </div>

  </div>
</template>
<script>
import GroupHeading from './GroupHeading.vue'
import GroupDescription from './GroupDescription.vue'
import GroupVolunteers from './GroupVolunteers.vue'
import GroupStats from './GroupStats.vue'
import GroupEvents from './GroupEvents.vue'
import AlertBanner from './AlertBanner.vue'
import auth from '../mixins/auth'

// Where the stats go, while we choose between them (#922):
// - compact: all of them before the events, achievements over impact beside items worked on over most repaired
// - after: all of them after the events
// - split: achievements and impact before the events, items worked on and most repaired after
// ?stats_layout= on the page address picks one, so they can be compared.
const STATS_LAYOUTS = ['compact', 'after', 'split']
const DEFAULT_STATS_LAYOUT = 'compact'

export default {
  components: {
    GroupEvents,
    GroupStats,
    GroupVolunteers,
    GroupDescription,
    GroupHeading,
    AlertBanner
  },
  mixins: [ auth ],
  props: {
    idgroups: {
      type: Number,
      required: true
    },
    initialGroup: {
      type: Object,
      required: true
    },
    events: {
      type: Array,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    candemote: {
      type: Boolean,
      required: false,
      default: false
    },
    canSeeDelete: {
      type: Boolean,
      required: false,
      default: false
    },
    canPerformDelete: {
      type: Boolean,
      required: false,
      default: false
    },
    canPerformArchive: {
      type: Boolean,
      required: false,
      default: false
    },
    ingroup: {
      type: Boolean,
      required: false,
      default: false
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
    groupStats: {
      required: true,
      type: Object
    },
    deviceStats: {
      required: true,
      type: Object
    },
    topDevices: {
      type: Array,
      required: true
    },
    discourseGroup: {
      type: String,
      required: false,
      default: null
    },
    reportingUrl: {
      type: String,
      required: false,
      default: null
    },
  },
  data () {
    return {
      haveLeft: false
    }
  },
  computed: {
    statsLayout() {
      try {
        const asked = new URLSearchParams(window.location.search).get('stats_layout')

        if (STATS_LAYOUTS.includes(asked)) {
          return asked
        }
      } catch (e) {
        // No usable address; use the default.
      }

      return DEFAULT_STATS_LAYOUT
    },
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
    },
    translatedHaveLeft() {
      // The string wraps :name in an <a> and this is rendered with v-html, so the group
      // name has to be escaped here.
      return this.__('groups.now_unfollowed', {
        name: this.escapeHtml(this.group.name),
        link: '/group/view/' + this.group.id
      })
    }
  },
  mounted () {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    // TODO LATER We add some properties to the group before adding it to the store.  These should move into
    // computed properties once we have good access to the session on the client.
    this.initialGroup.idgroups = this.idgroups
    this.initialGroup.canedit = this.canedit
    this.initialGroup.candemote = this.candemote
    this.initialGroup.ingroup = this.ingroup

    this.$store.dispatch('groups/set', this.initialGroup)

    this.events.forEach(e => {
      this.$store.dispatch('events/setStats', {
        idevents: e.idevents,
        stats: e.stats
      })
    })

    this.$store.dispatch('events/setList', {
      events: this.events
    })
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
.dashbord {
  border-top: 3px dashed grey;
}
</style>