<template>
  <CollapsibleSection collapsed :count="attendees.length">
    <template slot="title">
      {{ translatedTitle }}
    </template>
    <template slot="content">
      <div :class="{
      attendance: true,
      'mt-2': true,
      upcoming: upcoming
      }">
        <div>
          <div v-if="!upcoming">
            <h3>
              <b-img src="/icons/group_ico.svg" class="mr-2" />
              {{ translatedParticipants }}
            </h3>
            <EventAttendanceCount :count="participants.length" class="mt-2 mb-4" @change="changeParticipants($event)" :canedit="canedit" />
            <h3>
              <b-img src="/icons/volunteer_ico.svg" class="mr-2" />
              {{ translatedVolunteers }}
            </h3>
            <EventAttendanceCount :count="volunteers.length" class="mt-2"  @change="changeVolunteers($event)" :canedit="canedit" />
          </div>
        </div>
        <div />
        <div>
          <b-tabs class="ourtabs attendance-tabs w-100">
            <b-tab active title-item-class="w-50" class="pt-2">
              <template slot="title">
                <b>{{ translatedConfirmed }}</b> ({{ confirmed.length }})
              </template>
              <div v-if="confirmed.length" class="maxheight" :key="'confirm-' + confirmed.length">
                <EventAttendee v-for="a in confirmed" :key="'eventattendee-' + a.idevents_users" :attendee="a" :canedit="canedit" />
              </div>
              <p v-else>
                {{ translatedNoConfirmed }}
              </p>
              <hr />
              <div v-if="upcoming" class="d-flex justify-content-end">
                <!--              TODO In due course these modals should become Vue components.-->
                <a data-toggle="modal" data-target="#event-all-attended" href="#" class="mr-2">
                  {{ translatedSeeAllConfirmed }}
                </a>
              </div>
              <div v-else>
                <div class="d-flex justify-content-between">
                  <b-btn variant="link">
                    TODO Add.
                  </b-btn>
                  <b-btn variant="link">
                    {{ translatedSeeAllAttended }}
                  </b-btn>
                </div>
              </div>
            </b-tab>
            <b-tab title-item-class="w-50" class="pt-2">
              <template slot="title">
                <b>{{ translatedInvited }}</b> ({{ invited.length }})
              </template>
              <div v-if="invited.length" class="maxheight">
                <EventAttendee v-for="a in invited" :key="'eventattendee-' + a.idevents_users" :attendee="a" />
              </div>
              <p v-else>
                {{ translatedNoInvited }}
              </p>
              <hr />
              <div v-if="upcoming" class="d-flex justify-content-between">
                <a data-toggle="modal" data-target="#event-invite-to" href="#" class="ml-2">
                  <img class="icon" src="/images/add-icon.svg" />
                  {{ translatedInviteToJoin }}
                </a>
                <a data-toggle="modal" data-target="#event-all-volunteers" href="#" class="mr-2" v-if="invited.length">
                  {{ translatedSeeAllInvited }}
                </a>
              </div>
            </b-tab>
          </b-tabs>
        </div>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import { GUEST, HOST, RESTARTER } from '../constants'
import event from '../mixins/event'
import EventAttendanceCount from './EventAttendanceCount'
import EventAttendee from './EventAttendee'
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {CollapsibleSection, EventAttendee, EventAttendanceCount},
  mixins: [event],
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
      required: true
    },
    invitations:  {
      type: Array,
      required: true
    },
    // TODO In due course the permissions should be handled by having the user in the store and querying that, rather
    // than passing down props.
    canedit: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
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
    },
    translatedSeeAllConfirmed() {
      return this.$lang.get('events.see_all_confirmed')
    },
    translatedSeeAllInvited() {
      return this.$lang.get('events.see_all_invited')
    },
    translatedSeeAllAttended() {
      return this.$lang.get('events.see_all_attended')
    },
    translatedNoConfirmed() {
      return this.$lang.get('events.confirmed_none')
    },
    translatedNoInvited() {
      return this.$lang.get('events.invited_none')
    },
    translatedInviteToJoin() {
      return this.$lang.get('events.invite_to_join')
    }
  },
  created() {
    // The attendance is passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    //
    // Further down the line this initial data might be provided either by an API call from the client to the server,
    // or from Vue server-side rendering, where the whole initial state is passed to the client.
    //
    // Similarly the event should be in the store and passed just by id, but we haven't introduced an event store yet.
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

  &.upcoming {
    grid-template-columns: 0 0px 1fr;
  }
}

.attendance-tabs {
  height: 380px;

  ::v-deep .nav-item {
    width: 50%;
  }
}

.maxheight {
  max-height: 240px;
  min-height: 240px;
  overflow-y: auto;
}

h2 {
  font-size: 24px;
  font-weight: bold;
}

h3 {
  font-size: 18px;
  font-weight: bold;
}

.icon {
  width: 30px;
}
</style>