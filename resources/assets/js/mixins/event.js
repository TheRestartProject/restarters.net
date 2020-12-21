// This mixin includes function relating to events.
import { DATE_FORMAT, GUEST, HOST, RESTARTER } from '../constants'
import moment from 'moment'

export default {
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
    startingSoon() {
      return this.upcoming && !this.finished && (new moment().isSame(this.event.event_date, 'day'))
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
      return this.event ? (new moment(this.event.event_date).format('DD')) : null
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