<template>
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
</template>
<script>
import ConfirmModal from './ConfirmModal'

export default {
  components: {ConfirmModal},
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
      required: false,
      default: null
    },
    inGroup: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    canInvite() {
      // TODO Check this logic with Neil
      return this.upcoming && this.attending && this.attending.role === HOST;
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
    },
    translatedFollowGroup() {
      return this.$lang.get('events.follow_group')
    }
  },
  methods: {
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