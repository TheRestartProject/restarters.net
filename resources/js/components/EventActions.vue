<template>
  <div>
    <b-dropdown variant="primary" :text="__('events.event_actions').toUpperCase()" class="deepnowrap">
      <div v-if="canedit">
        <b-dropdown-item :href="'/party/edit/' + idevents">
          {{ __('events.edit_event') }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/party/duplicate/' + idevents">
          {{ __('events.duplicate_event') }}
        </b-dropdown-item>
        <b-dropdown-item @click="confirmDelete" v-if="candelete">
          {{ __('events.delete_event') }}
        </b-dropdown-item>
        <b-dropdown-item @click="confirmDelete" v-else-if="isAdmin" disabled>
          {{ __('events.delete_event') }}
        </b-dropdown-item>
        <div v-if="finished">
          <b-dropdown-item data-toggle="modal" data-target="#event-request-review">
            {{ __('events.request_review') }}
          </b-dropdown-item>
          <b-dropdown-item data-toggle="modal" data-target="#event-share-stats">
            {{ __('events.share_event_stats') }}
          </b-dropdown-item>
        </div>
        <div v-else>
          <b-dropdown-item v-if="isAttending && upcoming && approved" @click="invite">
            {{ __('events.invite_volunteers') }}
          </b-dropdown-item>
          <b-dropdown-item v-b-tooltip.hover  @click="invite" v-else-if="isAttending && upcoming" :title="__('events.invite_when_approved')" disabled>
            {{ __('events.invite_volunteers') }}
          </b-dropdown-item>
          <b-dropdown-item :href="'/party/join/' + idevents" v-else>
            {{ __('events.RSVP') }}
          </b-dropdown-item>
          <b-dropdown-item :href="'/group/join/' + event.group.idgroups" v-if="!inGroup">
            {{ __('events.follow_group') }}
          </b-dropdown-item>
        </div>
      </div>
      <div v-else>
        <b-dropdown-item data-toggle="modal" data-target="#event-share-stats" v-if="finished">
          {{ __('events.share_event_stats') }}
        </b-dropdown-item>
        <div v-else>
          <b-dropdown-item :href="'/group/join/' + event.group.idgroups" v-if="!inGroup">
            {{ __('events.follow_group') }}
          </b-dropdown-item>
          <b-dropdown-item v-if="attending && upcoming" @click="invite">
            {{ __('events.invite_volunteers') }}
          </b-dropdown-item>
          <b-dropdown-item :href="'/party/join/' + idevents" v-else>
            {{ __('events.RSVP') }}
          </b-dropdown-item>
        </div>
      </div>
    </b-dropdown>
    <ConfirmModal @confirm="confirmedDelete" :message="__('events.confirm_delete')" ref="confirmdelete" />
    <EventInviteModal ref="invite" :idevents="idevents" :groupName="event.group.name.trim()" :canedit="canedit" />
  </div>
</template>
<script>
import event from '../mixins/event'
import ConfirmModal from './ConfirmModal'
import EventInviteModal from './EventInviteModal'

export default {
  components: {ConfirmModal, EventInviteModal},
  mixins: [ event ],
  props: {
    idevents: {
      type: Number,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
    candelete: {
      type: Boolean,
      required: false,
      default: false
    },
    isAttending: {
      type: Boolean,
      required: false,
      default: false
    },
    isAdmin: {
      type: Boolean,
      required: true
    },
    inGroup: {
      type: Boolean,
      required: false,
      default: false
    },
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
    },
    invite() {
      this.$refs.invite.show()
    }
  }
}
</script>