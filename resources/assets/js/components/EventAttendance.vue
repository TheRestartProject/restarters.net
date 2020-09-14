<template>
  <div>
    <h2>{{ translatedTitle }}</h2>
    {{ attendees }}
    <div class="attendance">
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
        Block
      </div>
    </div>
  </div>
</template>
<script>
import { GUEST, HOST, RESTARTER } from '../constants'
import EventAttendanceCount from './EventAttendanceCount'

export default {
  components: {EventAttendanceCount},
  props: {
    eventId: {
      type: Number,
      required: true
    },
    attendance:  {
      type: Array,
      required: true
    }
  },
  computed: {
    attendees() {
      return this.$store.getters['attendance/byEvent'](this.eventId)
    },
    participants() {
      return this.attendees.filter((a) => {
        console.log("Participant", a)
        return a.role === GUEST
      })
    },
    volunteers() {
      return this.attendees.filter((a) => {
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
    }
  },
  created() {
    // The attendance is passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    //
    // Further down the line this initial data might be provided either by an API call from the client to the server,
    // or from Vue server-side rendering, where the whole initial state is passed to the client.
    this.$store.dispatch('attendance/set', {
      eventId: this.eventId,
      attendees: this.attendance
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

h3 {
  font-family: Asap;
  font-size: 18px;
  font-weight: bold;
}
</style>