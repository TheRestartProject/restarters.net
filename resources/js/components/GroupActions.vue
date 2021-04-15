<template>
  <div>
    <div v-if="canedit">
      <b-dropdown variant="primary" :text="translatedGroupActions" class="deepnowrap">
        <b-dropdown-item :href="'/group/edit/' + idgroups" v-if="canedit">
          {{ translatedEditGroup }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/party/create/' + idgroups" v-if="canedit">
          {{ translatedAddEvent }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#invite-to-group" v-if="canedit">
          {{ translatedInviteVolunteers }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/nearby/' + idgroups" v-if="canedit">
          {{ translatedVolunteersNearby }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#group-share-stats" v-if="canedit">
          {{ translatedShareGroupStatus }}
        </b-dropdown-item>
        <b-dropdown-item data-toggle="modal" @click="leaveGroup" v-if="ingroup">
          {{ translatedLeaveGroup }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/join/' + idgroups" v-else>
          {{ translatedJoinGroup }}
        </b-dropdown-item>
      </b-dropdown>
    </div>
    <div v-else>
      <b-dropdown variant="primary" :text="translatedGroupActions" class="deepnowrap">
        <b-dropdown-item data-toggle="modal" data-target="#invite-to-group" v-if="ingroup">
          {{ translatedInviteVolunteers }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/join/' + idgroups" v-else>
          {{ translatedJoinGroup }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#group-share-stats">
          {{ translatedShareGroupStatus }}
        </b-dropdown-item>
        <b-dropdown-item data-toggle="modal" @click="leaveGroup" v-if="ingroup">
          {{ translatedLeaveGroup}}
        </b-dropdown-item>
      </b-dropdown>
    </div>
    <ConfirmModal :key="'leavegroupmodal-' + idgroups" ref="confirmLeave" @confirm="leaveConfirmed" :message="translatedConfirmLeaveGroup" />
  </div>
</template>
<script>
import group from '../mixins/group'
import ConfirmModal from './ConfirmModal'

export default {
  components: {ConfirmModal},
  mixins: [ group ],
  props: {
    idgroups: {
      type: Number,
      required: true
    }
  },
  computed: {
    translatedGroupActions() {
      return this.$lang.get('groups.group_actions')
    },
    translatedEditGroup() {
      return this.$lang.get('groups.edit_group')
    },
    translatedAddEvent() {
      return this.$lang.get('groups.add_event')
    },
    translatedInviteVolunteers() {
      return this.$lang.get('groups.invite_volunteers')
    },
    translatedVolunteersNearby() {
      return this.$lang.get('groups.volunteers_nearby')
    },
    translatedShareGroupStatus() {
      return this.$lang.get('groups.share_group_stats')
    },
    translatedJoinGroup() {
      return this.$lang.get('groups.join_group_button')
    },
    translatedLeaveGroup() {
      return this.$lang.get('groups.leave_group_button')
    },
    translatedConfirmLeaveGroup() {
      return this.$lang.get('groups.leave_group_confirm')
    }
  },
  methods: {
    leaveGroup() {
      this.$refs.confirmLeave.show()
    },
    async leaveConfirmed() {
      await this.$store.dispatch('groups/unfollow', {
        idgroups: this.idgroups
      })

      this.$emit('left')
    }
  }
}
</script>