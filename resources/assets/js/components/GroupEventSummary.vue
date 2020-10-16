<template>
  <b-tr>
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
      <b-btn variant="primary" :href="'/party/join/' + idevents" v-else>
        {{ translatedRSVP }}
      </b-btn>
    </b-td>
    <b-td v-if="!upcoming">
    </b-td>
    <b-td v-if="!upcoming">
    </b-td>
    <b-td v-if="!upcoming">
    </b-td>
    <b-td v-if="!upcoming">
    </b-td>
    <b-td v-if="!upcoming">
    </b-td>
    <b-td v-if="!upcoming">
    </b-td>
    <b-td v-if="!upcoming">
    </b-td>
  </b-tr>
</template>
<script>
import event from '../mixins/event'

export default {
  mixins: [ event ],
  computed: {
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