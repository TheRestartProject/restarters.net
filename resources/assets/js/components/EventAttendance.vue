<template>
  <CollapsibleSection collapsed :count="attendees.length">
    <template slot="title">
      {{ translatedTitle }}
    </template>
    <template slot="content">
      <div class="mt-2">
        <p v-if="!upcoming && volunteerCountMismatch" class="warningbox small text-danger p-1">
          {{ translatedMismatch }}
        </p>
        <div :class="{
      attendance: true,
      upcoming: upcoming
      }">
          <div class="counts">
            <div v-if="!upcoming" class="count-participants">
              <b>
                <b-img src="/icons/group_ico.svg" class="mr-2" />
                {{ translatedParticipants }}
              </b>
              <EventAttendanceCount :count="event.participants" class="mt-2 mb-4" @change="changeParticipants($event)" :canedit="canedit" />
            </div>
            <div v-if="!upcoming" class="count-volunteers">
              <b>
                <b-img src="/icons/volunteer_ico.svg" class="mr-2" />
                {{ translatedVolunteers }}
              </b>
              <EventAttendanceCount :count="event.volunteers" class="mt-2"  @change="changeVolunteers($event)" :canedit="canedit" />
            </div>
          </div>
          <div class="spacer" />
          <div class="thetabs">
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
                  <!-- TODO LATER In due course these modals should become Vue components.-->
                  <a data-toggle="modal" data-target="#event-all-attended" href="#" class="mr-2">
                    {{ translatedSeeAll }}
                  </a>
                </div>
                <div v-else>
                  <div class="d-flex justify-content-between">
                    <b-btn variant="link" data-toggle="modal" data-target="#event-add-volunteer">
                      {{ translatedAddVolunteer }}
                    </b-btn>
                  <b-btn variant="link" data-toggle="modal" data-target="#event-all-attended" href="#">
                    {{ translatedSeeAll }}
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
                    {{ translatedSeeAll }}
                  </a>
                </div>
              </b-tab>
            </b-tabs>
          </div>
        </div>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import event from '../mixins/event'
import EventAttendanceCount from './EventAttendanceCount'
import EventAttendee from './EventAttendee'
import CollapsibleSection from './CollapsibleSection'

export default {
  components: {CollapsibleSection, EventAttendee, EventAttendanceCount},
  mixins: [event],
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
    translatedSeeAll() {
      return this.$lang.get('events.see_all')
    },
    translatedNoConfirmed() {
      return this.$lang.get('events.confirmed_none')
    },
    translatedNoInvited() {
      return this.$lang.get('events.invited_none')
    },
    translatedInviteToJoin() {
      return this.$lang.get('events.invite_to_join')
    },
    translatedMismatch() {
      return this.$lang.get('events.warning_volunteers_message')
    },
    translatedAddVolunteer() {
      return this.$lang.get('events.add_volunteer_modal_heading')
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
      idevents: this.idevents,
      attendees: attendees
    })
  },
  methods: {
    async changeParticipants(val) {
      let ret = await axios.post('/party/update-quantity', {
        quantity: val,
        event_id: this.idevents
      }, {
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        }
      })
    },
    async changeVolunteers(val) {
      let ret = await axios.post('/party/update-volunteerquantity', {
        quantity: val,
        event_id: this.idevents
      }, {
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        }
      })

      if (ret && ret.data && ret.data.success) {
        this.volunteerCount = val
      }
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.attendance {
  display: grid;
  grid-template-columns: 1fr 50px minmax(0, 2fr);
  grid-template-rows: auto auto;

  &.upcoming {
    grid-template-columns: 0 0px 1fr;
    grid-template-rows: auto auto;
  }

  @include media-breakpoint-down(sm) {
    grid-template-columns: 100%;
  }
}

.counts {
  grid-column: 1 / 2;
  grid-row: 1 / 2;

  display: grid;
  grid-template-columns: 1fr 1fr !important;
  grid-template-rows: auto auto !important;

  @include media-breakpoint-down(sm) {
    grid-column: 1 / 2;
    grid-row: 1 / 2;

    grid-template-columns: 1fr;
    grid-template-rows: auto auto;
  }
}

.count-participants {
  grid-column: 1 / 2;
  grid-row: 1 / 2;
}

.count-volunteers {
  grid-column: 1 / 2;
  grid-row: 2 / 3;

  @include media-breakpoint-down(sm) {
    grid-column: 2 / 3;
    grid-row: 1 / 2;
  }
}

.spacer {
  grid-column: 2 / 3;
  grid-row: 1 / 2;

  @include media-breakpoint-down(sm) {
    display: none;
  }
}

.thetabs {
  grid-column: 3 / 4;
  grid-row: 1 / 2;

  @include media-breakpoint-down(sm) {
    grid-column: 1 / 2;
    grid-row: 2 / 3;
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
  overflow-x: hidden
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

.warningbox {
  border: 1px solid $brand-danger;
}
</style>