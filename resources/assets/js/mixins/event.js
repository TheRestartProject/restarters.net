// This mixin includes lots of function relating to events.
// TODO LATER In due course the event will move into the store and we'll just pass the id.  All the other props will then
// become computed data in here.
import { DATE_FORMAT, GUEST, HOST, RESTARTER } from '../constants'
import moment from 'moment'
const htmlToText = require('html-to-text');

export default {
  props: {
    eventId: {
      type: Number,
      required: true
    },
    event: {
      type: Object,
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
    attending: {
      type: Object,
      required: false,
      default: null
    },
    inGroup: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      volunteerCount: null
    }
  },
  mounted() {
    // TODO LATER this should be removed when the events are moved into a store.
    this.volunteerCount = this.event.volunteers
  },
  computed: {
    upcoming() {
      const start = new moment(this.event.event_date + ' ' + this.event.start)
      return start.isAfter()
    },
    finished() {
      const end = new moment(this.event.event_date + ' ' + this.event.end)
      return end.isBefore()
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
      return new moment(this.event.event_date).format(DATE_FORMAT)
    },
    attendees() {
      // Everyone, both invited and confirmed.
      return this.$store.getters['attendance/byEvent'](this.eventId)
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
      console.log("Calc mismatch");
      return this.volunteerCount !== this.volunteers.length
    },
    canInvite() {
      // TODO Check this logic with Neil
      return this.upcoming && this.attending && this.attending.role === HOST;
    },
    free_text() {
      // Strip HTML
      let ret = htmlToText.fromString(this.event.free_text);

      // Remove duplicate blank lines.
      ret = ret.replace(/(\r\n|\r|\n){2,}/g, '$1\n');

      return ret
    },
  }
}