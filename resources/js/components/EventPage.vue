<template>
  <div v-if="event">
    <EventHeading :idevents="idevents" :canedit="canedit" :candelete="candelete" :is-admin="isAdmin" :in-group="inGroup" :is-attending="isAttending" />
    <div class="ep-layout">
      <div>
        <EventDetails class="pr-md-3" :idevents="idevents" :hosts="hosts" :calendar-links="calendarLinks" :is-attending="isAttending" :discourse-thread="discourseThread" />
        <EventDescription class="pr-md-3" :idevents="idevents" />
      </div>
      <div>
        <EventAttendance class="pl-md-3" :idevents="idevents" :canedit="canedit" :invitations="invitations" />
      </div>
    </div>
    <EventImages :images="images" v-if="images && images.length" />
    <div>
      <EventStats :idevents="idevents" />
      <EventDevices id="devices-section":idevents="idevents" :canedit="canedit || isAttending" :devices="devices" :clusters="clusters" :brands="brands" :barrier-list="barrierList" />
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
import auth from '../mixins/auth'

export default {
  components: {EventDevices, EventStats, EventImages, EventAttendance, EventDescription, EventDetails, EventHeading},
  mixins: [ event, auth ],
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
    candelete: {
      type: Boolean,
      required: false,
      default: false
    },
    isAdmin: {
      type: Boolean,
      required: true
    },
    isAttending: {
      type: Boolean,
      required: false,
      default: false
    },
    discourseThread: {
      type: String,
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

    this.$store.dispatch('attendance/set', {
      idevents: this.idevents,
      attendees: [...this.attendance, ...this.invitations]
    })

    if (window && window.location && window.location.hash) {
      setTimeout(() => {
        this.waitFor(window.location.hash.substring(1))()
      }, 2000)
    }
  },
  methods: {
    waitFor(id) {
      const self = this

      return function() {
        const elmnt = document.getElementById(id)

        if (elmnt) {
          elmnt.scrollIntoView()
        } else {
          setTimeout(self.waitFor(id), 100)
        }
      }
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.ep-layout {
  display: grid;
  grid-template-columns: 1fr;
  grid-template-rows: auto auto;

  @include media-breakpoint-up(md) {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr;
  }
}
</style>