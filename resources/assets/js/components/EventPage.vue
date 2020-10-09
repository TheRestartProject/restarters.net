<template>
  <div>
    <div v-if="event">
      <EventHeading v-bind="$props" />
      <div class="d-flex flex-wrap">
        <div class="w-xs-100 w-md-50">
          <div class="vue">
            <EventDetails class="pr-md-3" v-bind="$props" />
          </div>
          <div class="vue">
            <EventDescription class="pr-md-3" v-bind="$props" />
          </div>
        </div>
        <div class="w-xs-100 w-md-50 vue">
          <EventAttendance class="pl-md-3" v-bind="$props" />
        </div>
      </div>
      <EventImages v-bind="$props" v-if="images && images.length" />
      <div v-if="inProgress || finished">
        <EventStats :idevents="idevents" />
        <EventDevices v-bind="$props" />
      </div>
    </div>
    <div v-else>
<!--      TODO LATER Error page for missing event?-->
    </div>
  </div>
</template>
<script>
import event from '../mixins/event'
import EventHeading from './EventHeading'
import EventDetails from './EventDetails'
import EventDescription from './EventDescription'
import EventAttendance from './EventAttendance'
import EventImages from './EventImages'
import EventStats from './EventStats'
import EventDevices from './EventDevices'

export default {
  components: {EventDevices, EventStats, EventImages, EventAttendance, EventDescription, EventDetails, EventHeading},
  mixins: [ event ],
  props: {
    initialEvent: {
      type: Object,
      required: true
    },
    stats: {
      type: Object,
      required: false
    }
  },
  mounted() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // TODO LATER We're not quite there with this yet - there are still some props which we pass down to child components.
    // Once we resolve that, we can remove the use of $props, which isn't great as it hides exactly what each component is using.
    // But let's not bite off too much at once.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    this.initialEvent.idevents = this.idevents
    this.$store.dispatch('events/set', this.initialEvent)

    this.$store.dispatch('events/setStats', {
      idevents: this.idevents,
      stats: this.stats
    })

    if (this.devices && this.devices.length) {
      this.$store.dispatch('devices/set', {
        idevents: this.idevents,
        devices: this.devices
      })
    }
  }
}
</script>