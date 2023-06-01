// This mixin includes function relating to events.
import { DATE_FORMAT, GUEST, HOST, RESTARTER } from '../constants'
import moment from 'moment'

export default {
  computed: {
    event() {
      return this.$store.getters['events/get'](this.idevents)
    },
    attending() {
      return this.event ? this.event.attending : false
    },
    volunteerCount() {
      return this.event && this.event.volunteers ? this.event.volunteers : 0
    },
    approved() {
      return this.event && this.event.approved
    },
    upcoming() {
      // Use server value as this is timezone aware.
      return this.event && this.event.upcoming
    },
    finished() {
      // Use server value as this is timezone aware.
      return this.event && this.event.finished
    },
    inProgress() {
      // Use server value as this is timezone aware.
      return this.event && this.event.inprogress
    },
    startingSoon() {
      // Use server value as this is timezone aware.
      return this.event && this.event.startingsoon
    },
    start() {
      // Local time.
      return this.event ? this.event.start_local : null
    },
    end() {
      // Local time.
      return this.event ? this.event.end_local : null
    },
    date() {
      // Local time.
      moment.locale(this.$lang.getLocale())
      return this.event ? (new moment(this.event.event_date_local).format(DATE_FORMAT)) : null
    },
    dayofmonth() {
      // Local time.
      return this.event ? (new moment(this.event.event_date_local).format('DD')) : null
    },
    month() {
      // Local time.
      return this.event ? (new moment(this.event.event_date_local).format('MMM').toUpperCase()) : null
    },
    timezone() {
      return this.event ? this.event.timezone : null
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
  }
}