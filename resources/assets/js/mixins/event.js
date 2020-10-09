// This mixin includes lots of function relating to events.
// TODO LATER Because we're moving slowly away from blade templates to Vue we define a lot of props in here.
// Gradually some the props should move out of this mixin into individual component definitions, or
// into computed props in here.
import { DATE_FORMAT, GUEST, HOST, RESTARTER } from '../constants'
import moment from 'moment'

export default {
  props: {
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
  computed: {
    event() {
      return this.$store.getters['events/get'](this.idevents)
    },
    volunteerCount() {
      return this.event && this.event.volunteers ? this.event.volunteers.length : 0
    },
    upcoming() {
      let ret = false;

      if (this.event) {
        const start = new moment(this.event.event_date + ' ' + this.event.start)
        ret = start.isAfter()
      }

      return ret
    },
    finished() {
      let ret = false;

      if (this.event) {
        const end = new moment(this.event.event_date + ' ' + this.event.end)
        ret = end.isBefore()
      }

      return ret
    },
    inProgress() {
      return !this.upcoming && !this.finished
    },
    start() {
      return this.event.start.substring(0, 5)
    },
    end() {
      return this.event.end.substring(0, 5)
    },
    date() {
      return this.event ? (new moment(this.event.event_date).format(DATE_FORMAT)) : null
    },
    dayofmonth() {
      return this.event ? (new moment(this.event.event_date).format('D')) : null
    },
    month() {
      return this.event ? (new moment(this.event.event_date).format('MMM').toUpperCase()) : null
    },
    attendees() {
      // Everyone, both invited and confirmed.
      return this.$store.getters['attendance/byEvent'](this.idevents)
    },
    confirmed() {
      return this.attendees.filter((a) => {
        return a.confirmed
      })
    },
    invited() {
      return this.attendees.filter((a) => {
        return !a.confirmed
      })
    },
    participants() {
      return this.confirmed.filter((a) => {
        return a.role === GUEST
      })
    },
    volunteers() {
      return this.confirmed.filter((a) => {
        return a.role === HOST || a.role === RESTARTER
      })
    },
    volunteerCountMismatch() {
      return this.volunteerCount !== this.volunteers.length
    }
  }
}