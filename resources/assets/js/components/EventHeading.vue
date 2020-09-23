<template>
  <div class="border-top-very-thick border-bottom-thin mb-3">
    <div class="d-flex flex-wrap mt-4 mb-4">
      <div class="bord d-flex w-50">
        <div class="datebox">
          <span class="day">{{ date }}</span> <br />
          {{ month }}
        </div>
        <h1 class="ml-3 mr-3">
          {{ event.venue ? event.venue : event.location }}
        </h1>
      </div>
      <div class="pl-md-4 d-flex w-50">
        <div class="d-flex justify-content-between w-100">
          <div class="d-flex">
            <b-img @error="brokenGroupImage" :src="groupImage" class="groupImage" />
            <div v-html="translatedOrganised" class="ml-2"/>
          </div>
          <div>
            <b-dropdown variant="primary" :text="translatedEventActions">
              <b-dropdown-item :href="'/party/edit/' + eventId">
                {{ translatedEditEvent }}
              </b-dropdown-item>
              <b-dropdown-item @click="confirmDelete">
                {{ translatedDeleteEvent }}
              </b-dropdown-item>
              <div v-if="finished">
                <b-dropdown-item data-toggle="modal" data-target="#event-request-review">
                  {{ translatedRequestReview }}
                </b-dropdown-item>
                <b-dropdown-item data-toggle="modal" data-target="#event-share-stats">
                  {{ translatedShareEventStats }}
                </b-dropdown-item>
              </div>
              <div v-else-if="upcoming">
                <b-dropdown-item data-toggle="modal" data-target="#event-invite-to" v-if="canInvite">
                  {{ translatedInviteVolunteers }}
                </b-dropdown-item>
                <b-dropdown-item :href="'/party/join/' + eventId" v-else>
                  {{ translatedRSVP }}
                </b-dropdown-item>
              </div>
              <b-dropdown-item :href="'/group/join/' + event.the_group.idgroups" v-if="!inGroup">
                {{ translatedFollowGroup }}
              </b-dropdown-item>
            </b-dropdown>
            <ConfirmModal @confirm="confirmedDelete" ref="confirmdelete" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { DATE_FORMAT, DEFAULT_PROFILE } from '../constants'
import moment from 'moment'
import ExternalLink from './ExternalLink'
import ConfirmModal from './ConfirmModal'

// TODO Discuss criteria for event delete with Neil.

export default {
  components: {ConfirmModal, ExternalLink},
  props: {
    eventId: {
      type: Number,
      required: true
    },
    event: {
      type: Object,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    attending: {
      type: Object,
      required: true
    },
    inGroup: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    upcoming() {
      const now = new Date().getTime()
      const date = new Date(this.event.event_date).getTime()
      return date > now
    },
    finished() {
      const now = new Date().getTime()
      const date = new Date(this.event.event_date)
      return date < now
    },
    inProgress() {
      const now = new Date().getTime()
      const start = new Date(this.event.event_date + ' ' + this.event.start).getTime()
      const end = new Date(this.event.event_date + ' ' + this.event.start).getTime()
      return now >= start && now <= end
    },
    canInvite() {
      // TODO Check this logic with Neil
      return this.upcoming && this.attending && this.attending.role === HOST;
    },
    start() {
      return this.event.start.substring(0, 5)
    },
    end() {
      return this.event.end.substring(0, 5)
    },
    date() {
      return new moment(this.event.event_date).format('D')
    },
    month() {
      return new moment(this.event.event_date).format('MMM').toUpperCase()
    },
    groupImage() {
      return this.event.the_group && this.event.the_group.group_image ? ('/uploads/mid_' + this.event.the_group.group_image.image.path) : DEFAULT_PROFILE
    },
    translatedOrganised() {
      // TODO not good to construct HTML here, but we will fix this when we change past events to use this component.
      console.log("Event", this.event)
      return this.$lang.get('events.organised_by', {
        group: '<br /><b><a href="/group/view/' + this.event.the_group.idgroups  + '">' + this.event.the_group.name.trim() + '</a></b>'
      })
    },
    translatedEventActions() {
      return this.$lang.get('events.event_actions').toUpperCase()
    },
    translatedEditEvent() {
      return this.$lang.get('events.edit_event')
    },
    translatedDeleteEvent() {
      return this.$lang.get('events.delete_event')
    },
    translatedRequestReview() {
      return this.$lang.get('events.request_review')
    },
    translatedShareEventStats() {
      return this.$lang.get('events.share_event_stats')
    },
    translatedInviteVolunteers() {
      return this.$lang.get('events.invite_volunteers')
    },
    translatedRSVP() {
      return this.$lang.get('events.RSVP')
    }
  },
  methods: {
    brokenGroupImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
    confirmDelete() {
      this.$refs.confirmdelete.show()
    },
    async confirmedDelete() {
      // TODO When events move into the store this should become a store action.
      let ret = await axios.post('/party/delete/' + this.eventId, {
        id: this.eventId
      }, {
        headers: {
          'X-CSRF-TOKEN': $("input[name='_token']").val()
        }
      })

      window.location = '/party'
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.border-top-very-thick {
  border-top: 5px solid $black;
}

.border-bottom-thin {
  border-bottom: 1px solid $black;
}

.bord {
  @include media-breakpoint-up(md) {
    border-right: 1px solid $black;
  }
}

.groupImage {
  width: 50px;
  height: 50px;
  object-fit: cover;
}

.datebox {
  color: white;
  background-color: $black;
  text-align: center;
  min-width: 70px;
  min-height: 70px;
  max-width: 70px;
  max-height: 70px;
  padding-top: 8px;
  font-weight: bold;

  .day {
    font-size: 1.7rem;
    line-height: 1.7rem;
  }
}
</style>