<template>
  <div class="attendance">
    Attendance for {{ eventId }}
    {{ attendees }}
  </div>
</template>
<script>

export default {
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
      console.log("Attendees", this.$store.getters['attendance/byEvent'](this.eventId))
      return this.$store.getters['attendance/byEvent'](this.eventId)
    }
  },
  created() {
    // The attendance is passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    //
    // Further down the line this initial data might be provided either by an API call from the client to the server,
    // or from Vue server-side rendering, where the whole initial state is passed to the client.
    console.log("Set initial attendees in store", this.eventId, this.attendance)
    this.$store.dispatch('attendance/set', {
      eventId: this.eventId,
      attendees: this.attendance
    })
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';
</style>