<template>
  <div>
    <div v-if="canedit">
      <b-dropdown variant="primary" :text="__('groups.group_actions')" class="deepnowrap" id="groupactions">
        <b-dropdown-item :href="'/group/edit/' + idgroups" v-if="canedit">
          {{ __('groups.edit_group') }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/party/create/' + idgroups" v-if="canedit">
          {{ __('groups.add_event') }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#invite-to-group" v-if="canedit">
          {{ __('groups.invite_volunteers') }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#group-share-stats" v-if="canedit">
          {{ __('groups.share_group_stats') }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/export/devices/group/' + idgroups">
          {{ __('devices.export_group_data') }}
        </b-dropdown-item>
        <b-dropdown-item data-toggle="modal" @click="leaveGroup" v-if="ingroup" class="leavegroup">
          {{ __('groups.leave_group_button') }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/join/' + idgroups" v-else>
          {{ __('groups.join_group_button') }}
        </b-dropdown-item>
        <b-dropdown-item @click="deleteGroup" v-if="canSeeDelete && canPerformDelete">
          {{ __('groups.delete_group') }}
        </b-dropdown-item>
        <b-dropdown-item v-if="canSeeDelete && !canPerformDelete" disabled>
          {{ __('groups.delete_group') }}
        </b-dropdown-item>
        <b-dropdown-item @click="archiveGroup" v-if="canPerformArchive" :disabled="group.archived_at">
          {{ __('groups.archive_group') }}
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
    <ConfirmModal :key="'deletegroupmodal-' + idgroups" ref="confirmDelete" @confirm="deleteConfirmed" :message="__('groups.delete_group_confirm', {
      name: group.name
    })" />
    <ConfirmModal :key="'archivegroupmodal-' + idgroups" ref="confirmArchive" @confirm="archiveConfirmed" :message="__('groups.archive_group_confirm', {
      name: group.name
    })" />
  </div>
</template>
<script>
import group from '../mixins/group'
import ConfirmModal from './ConfirmModal.vue'

export default {
  components: {ConfirmModal},
  mixins: [ group ],
  props: {
    idgroups: {
      type: Number,
      required: true
    },
    canSeeDelete: {
      type: Boolean,
      required: false,
      default: false
    },
    canPerformDelete: {
      type: Boolean,
      required: false,
      default: false
    },
    canPerformArchive: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  computed: {
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
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
    },
    deleteGroup() {
      this.$refs.confirmDelete.show()
    },
    archiveGroup() {
      this.$refs.confirmArchive.show()
    },
    async deleteConfirmed() {
      window.location = '/group/delete/' + this.idgroups
    },
    async archiveConfirmed() {
      const group = this.group
      group.id = this.idgroups
      group.archived_at = (new Date()).toISOString()
      group.description = group.free_text

      // Make sure we don't stomp on the networks.
      delete group.networks

      await this.$store.dispatch('groups/edit', group)

      await this.$store.dispatch('groups/fetch', {
        id: this.idgroups
      })
    }
  }
}
</script>