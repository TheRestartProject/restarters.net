<template>
  <div>
    <div v-if="event">
      <EventHeading :idevents="idevents" :canedit="canedit" :in-group="inGroup" :attending="attending" />
      <div class="layout">
        <div>
          <EventDetails class="pr-md-3" :idevents="idevents" :hosts="hosts" :calendar-links="calendarLinks" />
          <EventDescription class="pr-md-3" :idevents="idevents" />
        </div>
        <div>
          <EventAttendance class="pl-md-3" :idevents="idevents" :canedit="canedit" />
        </div>
      </div>
      <EventImages :images="images" v-if="images && images.length" />
      <div v-if="inProgress || finished">
        <EventStats :idevents="idevents" :stats="stats" />
        <EventDevices :idevents="idevents" :canedit="canedit" :devices="devices" :clusters="clusters" :brands="brands" :barrier-list="barrierList" />
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
    },
    idevents: {
      type: Number,
      required: true
    },
    attendance:  {
      type: Array,
      required: false,
      default: function () { return [] }
    },
    invitations:  {
      type: Array,
      required: false,
      default: function () { return [] }
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    isAttending: {
      type: Boolean,
      required: false,
      default: false
    },
    attending: {
      type: Object,
      required: false,
      default: null
    },
    inGroup: {
      type: Boolean,
      required: false,
      default: false
    },
    devices: {
      type: Array,
      required: false,
      default: null
    },
    clusters: {
      type: Array,
      required: false,
      default: null
    },
    brands: {
      type: Array,
      required: false,
      default: null
    },
    barrierList: {
      type: Array,
      required: false,
      default: null
    },
    hosts: {
      type: Array,
      required: false,
      default: null
    },
    calendarLinks: {
      type: Object,
      required: false,
      default: null
    },
    images: {
      type: Array,
      required: false,
      default: null
    },
    cluster: {
      type: Array,
      required: false,
      default: null
    }
  },
  mounted() {
    // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
    // and so that as/when it changes then reactivity updates all the views.
    //
    // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
    // TODO LATER We add some properties to the group before adding it to the store.  These should move into
    // computed properties once we have good access to the session on the client, and there should be a separate store
    // for volunteers.
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
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr;
  }
}
</style>