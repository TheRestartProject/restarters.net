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
import event from '../mixins/event'
import ConfirmModal from './ConfirmModal'
import map from '../mixins/map'

export default {
  components: {ConfirmModal},
  mixins: [ event ],
  computed: {
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
      // TODO LATER When events move into the store this should become a store action.
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