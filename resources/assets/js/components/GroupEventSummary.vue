<template>
  <b-tr v-if="event && stats" :class="{
    'text-center': true,
    attending: event.attending
    }">
    <b-td class="datecell" width="76px">
      <div class="datebox d-flex flex-column">
        <span class="day align-top">{{ dayofmonth }}</span>
        <span class="month">
          {{ month }}
        </span>
      </div>
    </b-td>
    <b-td class="date text-left pl-3">
      {{ date }}
      <br class="d-none d-md-block" />
      {{ start }} <span class="d-none d-md-inline">- {{ end }}</span>
      <br class="d-block d-md-none" />
      <b class="d-block d-md-none"><a :href="'/party/view/' + idevents">{{ event.venue ? event.venue : event.location }}</a></b>
    </b-td>
    <b-td class="text-left d-none d-md-table-cell">
      <b><a :href="'/party/view/' + idevents">{{ event.venue ? event.venue : event.location }}</a></b>
    </b-td>
    <b-td v-if="upcoming" class="d-none d-md-table-cell">
      {{ event.allinvitedcount }}
    </b-td>
    <b-td v-if="upcoming" class="d-none d-md-table-cell">
      {{ event.volunteers }}
    </b-td>
    <b-td v-if="upcoming && event.requiresModeration" class="cell-warning">
      <span v-if="event.canModerate">
        <a :href="'/party/edit/' + idevents">{{ translatedRequiresModeration }}</a>
      </span>
      <span v-else>
        {{ translatedRequiresModerationByAnAdmin }}
      </span>
    </b-td>
    <b-td v-else-if="upcoming">
      <div v-if="event.attending" class="text-black font-weight-bold d-flex d-none d-md-table-cell">
        <span>
          {{ translatedYoureGoing }}
        </span>
      </div>
      <!-- We can't RSVP if the event is starting soon. -->
      <b-btn variant="primary" :href="'/party/join/' + idevents" :disabled="startingSoon" v-else>
        {{ translatedRSVP }}
      </b-btn>
    </b-td>
    <b-td v-if="!upcoming" :class="{
      'cell-danger': event.participants_count === 0,
      'd-none': true,
      'd-md-table-cell': true
    }
    ">
      {{ event.participants_count }}
    </b-td>
    <b-td v-if="!upcoming" :class="{
      'cell-danger': event.volunteers_count <= 1,
      'd-none': true,
      'd-md-table-cell': true
    }
    ">
      {{ event.volunteers_count }}
    </b-td>
    <b-td colspan="5" v-if="noDevices" class="cell-danger text-center d-none d-md-table-cell">
      {{ translatedNoDevices }}
      <a :href="'/party/view/' + this.idevents">
        {{ translatedAddADevice}}
      </a>
    </b-td>
    <b-td v-if="!upcoming && !noDevices" class="d-none d-md-table-cell">
      {{ Math.round(stats.ewaste) }}kg
    </b-td>
    <b-td v-if="!upcoming && !noDevices" class="d-none d-md-table-cell">
      {{ Math.round(stats.co2) }}kg
    </b-td>
    <b-td v-if="!upcoming && !noDevices" class="d-none d-md-table-cell">
      {{ stats.fixed_devices }}
    </b-td>
    <b-td v-if="!upcoming && !noDevices" class="d-none d-md-table-cell">
      {{ stats.repairable_devices }}
    </b-td>
    <b-td v-if="!upcoming && !noDevices" class="d-none d-md-table-cell">
      {{ stats.dead_devices }}
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
    noDevices() {
      // Whether there are no devices at this event, and we have permissions to do something about that.
      return this.event && this.stats && (this.stats.fixed_devices + this.stats.repairable_devices + this.stats.dead_devices === 0) && this.canedit && this.finished
    },
    translatedRequiresModeration() {
      return this.$lang.get('partials.event_requires_moderation')
    },
    translatedRequiresModerationByAnAdmin() {
      return this.$lang.get('partials.event_requires_moderation_by_an_admin')
    },
    translatedNoDevices() {
      return this.$lang.get('partials.no_devices_added')
    },
    translatedAddADevice() {
      return this.$lang.get('partials.add_a_device')
    },
    translatedYoureGoing() {
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

.attending {
  background-color: $brand-grey;

  .datecell {
    padding-top: 9px;
    padding-bottom: 9px;
    padding-left: 9px;
    padding-right: 9px;
    text-align: center;
    background-color: $black;

    .datebox {
      background-color: $black;
      color: $white;
    }
  }
}

.date {
  line-height: 1.3rem;
  text-align: center;
  padding-top: 13px;
  width: 150px;
  font-size: 15px;
}

.fullsize {
  font-size: 100%;
}
</style>