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
        <b-dropdown-item @click="confirmDelete" v-if="!inProgress && !finished">
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
          <b-dropdown-item data-toggle="modal" data-target="#event-invite-to" v-if="attending && upcoming">
            {{ __('events.invite_volunteers') }}
          </b-dropdown-item>
          <b-dropdown-item :href="'/party/join/' + idevents" v-else>
            {{ __('events.RSVP') }}
          </b-dropdown-item>
        </div>
        <b-dropdown-item :href="'/group/join/' + event.the_group.idgroups" v-if="!inGroup">
          {{ __('events.follow_group') }}
        </b-dropdown-item>
      </div>
      <div v-else>
        <b-dropdown-item data-toggle="modal" data-target="#event-share-stats" v-if="finished">
          {{ __('events.share_event_stats') }}
        </b-dropdown-item>
        <div v-else>
          <b-dropdown-item :href="'/group/join/' + event.the_group.idgroups" v-if="!inGroup">
            {{ __('events.follow_group') }}
          </b-dropdown-item>
          <b-dropdown-item data-toggle="modal" data-target="#event-invite-to" v-if="attending && upcoming">
            {{ __('events.invite_volunteers') }}
          </b-dropdown-item>
          <b-dropdown-item :href="'/party/join/' + idevents" v-else>
            {{ __('events.RSVP') }}
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
    attending: {
      type: Object,
      required: false,
      default: null
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
    }
  }
}
</script>