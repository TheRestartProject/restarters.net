<template>
  <div>
    <h2>{{ translatedTitle }}</h2>
    <div class="attendance mt-4">
      <div>
        <div>
          <h3>
            <b-img src="/icons/volunteer_ico.svg" class="mr-2" />
            {{ translatedParticipants }}
          </h3>
          <EventAttendanceCount :count="participants.length" class="mt-2 mb-4" />
          <h3>
            <b-img src="/icons/volunteer_ico.svg" class="mr-2" />
            {{ translatedVolunteers }}
          </h3>
          <EventAttendanceCount :count="volunteers.length" class="mt-2" />
        </div>
      </div>
      <div />
      <div>
        <b-tabs class="ourtabs attendance-tabs w-100">
          <b-tab active title-item-class="w-50">
            <template slot="title">
              <b>{{ translatedConfirmed }}</b> ({{ confirmed.length }})
            </template>
            <p>
              <EventAttendee v-for="a in confirmed" :key="'eventattendee-' + a.idevents_users" :attendee="a" />
            </p>
          </b-tab>
          <b-tab title-item-class="w-50">
            <template slot="title">
              <b>{{ translatedInvited }}</b> ({{ invited.length }})
            </template>
            <p>
              Content 2
            </p>
          </b-tab>
        </b-tabs>
      </div>
    </div>
  </div>
</template>
<script>
import { GUEST, HOST, RESTARTER } from '../constants'
import EventAttendanceCount from './EventAttendanceCount'
import EventAttendee from './EventAttendee'

// TODO +/- modals

export default {
  components: {EventAttendee, EventAttendanceCount},
  props: {
    eventId: {
      type: Number,
      required: true
    },
    attendance:  {
      type: Array,
      required: true
    },
    invitations:  {
      type: Array,
      required: true
    }
  },
  computed: {
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
    translatedTitle() {
      return this.$lang.get('events.event_attendance')
    },
    translatedVolunteers() {
      return this.$lang.get('events.stat-2')
    },
    translatedParticipants() {
      return this.$lang.get('events.stat-0')
    },
    translatedConfirmed() {
      return this.$lang.get('events.confirmed')
    },
    translatedInvited() {
      return this.$lang.get('events.invited')
    }

  },
  created() {
    // The attendance is passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    //
    // Further down the line this initial data might be provided either by an API call from the client to the server,
    // or from Vue server-side rendering, where the whole initial state is passed to the client.
    let attendees = []

    this.attendance.forEach((a) => {
      a.confirmed = true
      attendees.push(a)
    })

    this.invitations.forEach((a) => {
      a.confirmed = false
      attendees.push(a)
    })

    this.$store.dispatch('attendance/set', {
      eventId: this.eventId,
      attendees: attendees
    })
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.attendance {
  display: grid;
  grid-template-columns: 1fr 50px 2fr;
}

.attendance-tabs {
  height: 350px;

  ::v-deep .nav-item {
    width: 50%;
  }
}

h3 {
  font-family: Asap;
  font-size: 18px;
  font-weight: bold;
}
</style>