<template>
  <b-tr v-if="event" :class="{
    'text-center': true,
    attending: event.attending
    }">
    <b-td class="datecell">
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
      <b class="d-block d-md-none"><EventTitle :idevents="idevents" component="a" :href="'/party/view/' + idevents" /></b>
    </b-td>
    <b-td class="text-left d-none d-md-table-cell">
      <b><EventTitle :idevents="idevents" component="a" :href="'/party/view/' + idevents" /></b>
      <span v-if="addGroupName" class="small">
        <br />
        <a :href="'/group/view/' + event.group.idgroups">{{ event.group.name }}</a>
      </span>
    </b-td>
    <b-td v-if="upcoming" class="d-none d-md-table-cell cell-number">
      {{ event.allinvitedcount }}
    </b-td>
    <b-td v-if="upcoming" class="d-none d-md-table-cell cell-number">
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
    <b-td v-else-if="upcoming" class="d-none d-md-table-cell">
      <div v-if="event.attending" class="text-black font-weight-bold d-flex justify-content-around">
        <span>
          {{ translatedYoureGoing }}
        </span>
      </div>
      <!-- "all" or "nearby" events are for ones where we're not a member, so should a join button. -->
      <b-btn variant="primary" :href="'/group/join/' + event.group.idgroups" v-else-if="event.all || event.nearby">
        {{ translatedJoinGroup }}
      </b-btn>
      <!-- We can't RSVP if the event is starting soon. -->
      <b-btn variant="primary" :href="'/party/join/' + idevents" :disabled="startingSoon" v-else>
        {{ translatedRSVP }}
      </b-btn>
    </b-td>
    <b-td v-if="!upcoming" :class="{
      'cell-danger': event.participants_count === 0,
      'd-none': true,
      'd-md-table-cell': true,
      'cell-number': true
    }
    ">
      {{ event.participants_count }}
    </b-td>
    <b-td v-if="!upcoming" :class="{
      'cell-danger': event.volunteers_count <= 1,
      'd-none': true,
      'd-md-table-cell': true,
      'cell-number': true
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
    <b-td v-if="!upcoming && !noDevices && stats" class="d-none d-md-table-cell cell-number">
      {{ Math.round(stats.ewaste) }} kg
    </b-td>
    <b-td v-if="!upcoming && !noDevices && stats" class="d-none d-md-table-cell cell-number">
      {{ Math.round(stats.co2) }} kg
    </b-td>
    <b-td v-if="!upcoming && !noDevices && stats" class="d-none d-md-table-cell cell-number">
      {{ stats.fixed_devices }}
    </b-td>
    <b-td v-if="!upcoming && !noDevices && stats" class="d-none d-md-table-cell cell-number">
      {{ stats.repairable_devices }}
    </b-td>
    <b-td v-if="!upcoming && !noDevices && stats" class="d-none d-md-table-cell cell-number">
      {{ stats.dead_devices }}
    </b-td>
  </b-tr>
</template>
<script>
import event from '../mixins/event'
import EventTitle from './EventTitle'

export default {
  components: {EventTitle},
  mixins: [ event ],
  props: {
    idevents: {
      type: Number,
      required: true
    },
    addGroupName: {
      type: Boolean,
      required: false,
    }
  },
  computed: {
    stats() {
      return this.$store.getters['events/getStats'](this.idevents)
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
    translatedJoinGroup() {
      return this.$lang.get('groups.join_group_button')
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
    width: 76px !important;

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

.cell-number {
  width: 60px;
}
</style>