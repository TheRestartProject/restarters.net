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
  <div v-else class="vue-placeholder vue-placeholder-large">
    <div class="vue-placeholder-content">{{ __('partials.loading') }}...</div>
  </div>
</template>
<script>
import GroupHeading from './GroupHeading.vue'
import GroupDescription from './GroupDescription.vue'
import GroupVolunteers from './GroupVolunteers.vue'
import GroupStats from './GroupStats.vue'
import GroupEvents from './GroupEvents.vue'
import GroupDevicesWorkedOn from './GroupDevicesWorkedOn.vue'
import GroupDevicesMostRepaired from './GroupDevicesMostRepaired.vue'
import GroupDevicesBreakdown from './GroupDevicesBreakdown.vue'
import AlertBanner from './AlertBanner.vue'
import auth from '../mixins/auth'
import axios from 'axios'

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
    events: {
      type: Array,
      required: true
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
      return this.__('groups.now_unfollowed', {
        name: this.group.name,
        link: '/group/view/' + this.group.id
      })
    },
    permissions() {
      return (this.group && this.group.permissions) || {}
    },
    canedit() {
      return this.permissions.can_edit || false
    },
    candemote() {
      return this.permissions.can_demote || false
    },
    canSeeDelete() {
      return this.permissions.can_see_delete || false
    },
    canPerformDelete() {
      return this.permissions.can_perform_delete || false
    },
    canPerformArchive() {
      return this.permissions.can_perform_archive || false
    }
  },
  async mounted () {
    // Fetch the group - including this user's UI permission flags - from the API, rather than relying on
    // server-hydrated Blade props.  We put it in the store for all components to use, and so that as/when it
    // changes then reactivity updates all the views.  While the fetch is in flight, `group` in the store is
    // undefined and the template shows a loading placeholder.
    //
    // We fetch directly here (rather than via the groups/fetch action) so that we can add the old-style field
    // aliases some sibling components still expect (see below) before the group ever lands in the store, and
    // so we commit it exactly once - the shared groups/fetch action is also used by components which want the
    // raw API shape as-is (e.g. GroupAddEdit.vue), so we don't want to change what it commits.
    try {
      const ret = await axios.get('/api/v2/groups/' + this.idgroups)
      const group = ret.data.data

      // A handful of sibling components (GroupVolunteers, GroupEvents, ...) read canedit/candemote/ingroup off
      // the stored group object itself via the `group` mixin, rather than as props - and some still expect
      // old-style (Eloquent) field names.  Add compatibility aliases rather than changing those components
      // (out of scope here) - mirrors the newToOld() shim already used elsewhere in the groups store.
      group.idgroups = this.idgroups
      group.canedit = group.permissions ? group.permissions.can_edit : false
      group.candemote = group.permissions ? group.permissions.can_demote : false
      group.ingroup = this.ingroup
      group.free_text = group.description

      if (group.image) {
        group.group_image = { image: { path: group.image } }
      }

      this.$store.dispatch('groups/set', group)
    } catch (e) {
      console.error('Group fetch failed', e)
    }

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