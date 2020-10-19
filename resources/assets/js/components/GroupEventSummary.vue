<template>
  <b-tr v-if="event && stats">
    <b-td>
      <div class="datebox d-flex flex-column">
        <span class="day align-top">{{ dayofmonth }}</span>
        <span class="month">
          {{ month }}
        </span>
      </div>
    </b-td>
    <b-td class="date">
      {{ date }} <br />
      {{ start }} - {{ end }}
    </b-td>
    <b-td>
      <b>{{ event.venue ? event.venue : event.location }}</b>
    </b-td>
    <b-td v-if="upcoming">
      {{ event.allinvitedcount }}
    </b-td>
    <b-td v-if="upcoming">
      {{ event.volunteers }}
    </b-td>
    <b-td v-if="upcoming">
      <div v-if="event.attending">
        {{ translatedYourGoing }}
      </div>
      <!-- We can't RSVP if the event is starting soon. -->
      <b-btn variant="primary" :href="'/party/join/' + idevents" :disabled="startingSoon" v-else>
        {{ translatedRSVP }}
      </b-btn>
    </b-td>
    <b-td v-if="!upcoming">
      {{ event.participants_count }}
    </b-td>
    <b-td v-if="!upcoming">
      {{ event.volunteers_count }}
    </b-td>
    <b-td v-if="!upcoming">
      {{ Math.round(stats.ewaste) }}kg
    </b-td>
    <b-td v-if="!upcoming">
      {{ Math.round(stats.co2) }}kg
    </b-td>
    <b-td v-if="!upcoming">
      {{ stats.fixed_devices }}
    </b-td>
    <b-td v-if="!upcoming">
      {{ stats.repairable_devices }}
    </b-td>
    <b-td v-if="!upcoming">
      {{ stats.dead_devices }}
<!--      TODO Event requires approval-->
    </b-td>
  </b-tr>
</template>
<script>
import event from '../mixins/event'

export default {
  mixins: [ event ],
  computed: {
    stats() {
      // TODO LATER Consider whether these should be in the event or the store.
      return this.event ? this.event.stats : null
    },
    translatedYourGoing() {
      return this.$lang.get('events.youre_going')
    },
    translatedRSVP() {
      return this.$lang.get('events.RSVP')
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.datebox {
  text-align: center;
  padding-top: 8px;
  font-weight: bold;

  .day {
    font-size: 1.7rem;
    line-height: 1.7rem;
  }

  .month {
    line-height: 1rem;
  }
}

.date {
  line-height: 1.3rem;
  text-align: center;
  padding-top: 13px;
  width: 150px;
}
</style>