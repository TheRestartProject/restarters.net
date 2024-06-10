<template>
  <div v-if="group">
    <AlertBanner />
    <div class="alert alert-success" v-if="haveLeft" v-html="translatedHaveLeft" />
    <GroupHeading
        :idgroups="idgroups"
        :canedit="canedit"
        :can-see-delete="canSeeDelete"
        :can-perform-delete="canPerformDelete"
        :ingroup="ingroup"
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

    <div class="vue w-100 mt-md-50">
      <GroupStats :idgroups="idgroups" :stats="groupStats "/>
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

    <div class="d-flex flex-wrap flex-md-nowrap pt-4">
      <div class="w-100 mt-md-50 mr-md-4">
        <GroupDevicesWorkedOn :idgroups="idgroups" :stats="deviceStats" class="pt-2 dashbord" />
      </div>
      <div class="w-100 mt-md-50">
        <GroupDevicesMostRepaired :idgroups="idgroups" :devices="topDevices" class="pt-2 dashbord mt-4 mt-md-0" />
      </div>
    </div>

    <GroupDevicesBreakdown :idgroups="idgroups" :cluster-stats="clusterStats" />
  </div>
</template>
<script>
import GroupHeading from './GroupHeading'
import GroupDescription from './GroupDescription'
import GroupVolunteers from './GroupVolunteers'
import GroupStats from './GroupStats'
import GroupEvents from './GroupEvents'
import GroupDevicesWorkedOn from './GroupDevicesWorkedOn'
import GroupDevicesMostRepaired from './GroupDevicesMostRepaired'
import GroupDevicesBreakdown from './GroupDevicesBreakdown'
import AlertBanner from './AlertBanner'
import auth from '../mixins/auth'

export default {
  components: {
    GroupDevicesBreakdown,
    GroupDevicesMostRepaired,
    GroupDevicesWorkedOn,
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
    volunteers: {
      type: Array,
      required: true
    },
    canedit: {
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
    clusterStats: {
      type: Object,
      required: true
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
  },
  data () {
    return {
      haveLeft: false
    }
  },
  computed: {
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
    },
    translatedHaveLeft() {
      return this.$lang.get('groups.now_unfollowed', {
        name: this.group.name,
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
    // computed properties once we have good access to the session on the client, and there should be a separate store
    // for volunteers, shared between groups and events.
    this.initialGroup.idgroups = this.idgroups
    this.initialGroup.canedit = this.canedit
    this.initialGroup.ingroup = this.ingroup
    this.initialGroup.volunteers = this.volunteers

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