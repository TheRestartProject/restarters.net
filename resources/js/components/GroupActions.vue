<template>
  <div>
    <div v-if="canedit">
      <b-dropdown variant="primary" :text="__('groups.group_actions')" class="deepnowrap">
        <b-dropdown-item :href="'/group/edit/' + idgroups" v-if="canedit">
          {{ __('groups.edit_group') }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/party/create/' + idgroups" v-if="canedit">
          {{ __('groups.add_event') }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#invite-to-group" v-if="canedit">
          {{ __('groups.invite_volunteers') }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/nearby/' + idgroups" v-if="canedit">
          {{ __('groups.volunteers_nearby') }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#group-share-stats" v-if="canedit">
          {{ __('groups.share_group_stats') }}
        </b-dropdown-item>
        <b-dropdown-item data-toggle="modal" @click="leaveGroup" v-if="ingroup">
          {{ __('groups.leave_group_button') }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/join/' + idgroups" v-else>
          {{ __('groups.join_group_button') }}
        </b-dropdown-item>
      </b-dropdown>
    </div>
    <div v-else>
      <b-dropdown variant="primary" :text="__('groups.group_actions')" class="deepnowrap">
        <b-dropdown-item data-toggle="modal" data-target="#invite-to-group" v-if="ingroup">
          {{ __('groups.invite_volunteers') }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/join/' + idgroups" v-else>
          {{ __('groups.join_group_button') }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#group-share-stats">
          {{ __('groups.share_group_stats') }}
        </b-dropdown-item>
        <b-dropdown-item data-toggle="modal" @click="leaveGroup" v-if="ingroup">
          {{ __('groups.leave_group_button') }}
        </b-dropdown-item>
      </b-dropdown>
    </div>
    <ConfirmModal :key="'leavegroupmodal-' + idgroups" ref="confirmLeave" @confirm="leaveConfirmed" :message="__('groups.leave_group_confirm')" />
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