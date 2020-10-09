<template>
  <div>
    <b-dropdown variant="primary" :text="translatedEventActions" class="deepnowrap">
      <div v-if="canedit">
        <b-dropdown-item :href="'/party/edit/' + idevents">
          {{ translatedEditEvent }}
        </b-dropdown-item>
        <b-dropdown-item @click="confirmDelete" v-if="!inProgress && !finished">
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
        <div v-else>
          <b-dropdown-item data-toggle="modal" data-target="#event-invite-to" v-if="attending && upcoming">
            {{ translatedInviteVolunteers }}
          </b-dropdown-item>
          <b-dropdown-item :href="'/party/join/' + idevents" v-else>
            {{ translatedRSVP }}
          </b-dropdown-item>
        </div>
        <b-dropdown-item :href="'/group/join/' + event.the_group.idgroups" v-if="!inGroup">
          {{ translatedFollowGroup }}
        </b-dropdown-item>
      </div>
      <div v-else>
        <b-dropdown-item data-toggle="modal" data-target="#event-share-stats" v-if="finished">
          {{ translatedShareEventStats }}
        </b-dropdown-item>
        <div v-else>
          <b-dropdown-item :href="'/group/join/' + event.the_group.idgroups" v-if="!inGroup">
            {{ translatedFollowGroup }}
          </b-dropdown-item>
          <b-dropdown-item data-toggle="modal" data-target="#event-invite-to" v-if="attending && upcoming">
            {{ translatedInviteVolunteers }}
          </b-dropdown-item>
          <b-dropdown-item :href="'/party/join/' + idevents" v-else>
            {{ translatedRSVP }}
          </b-dropdown-item>
        </div>
      </div>
    </b-dropdown>
    <ConfirmModal @confirm="confirmedDelete" ref="confirmdelete" />
  </div>
</template>
<script>
import event from '../mixins/event'
import ConfirmModal from './ConfirmModal'

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
      await this.$store.dispatch('events/delete', {
        idevents: this.idevents
      })

      // TODO LATER Assumes always works.
      window.location = '/party'
    }
  }
}
</script>