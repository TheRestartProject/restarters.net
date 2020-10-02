<template>
  <div>
    <div v-if="canedit">
      <b-dropdown variant="primary" :text="translatedGroupActions" v-if="canedit">
        <b-dropdown-item :href="'/group/edit/' + groupId">
          {{ translatedEditGroup }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/party/create/' + groupId">
          {{ translatedAddEvent }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#invite-to-group">
          {{ translatedInviteVolunteers }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/nearby/' + groupId">
          {{ translatedVolunteersNearby }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#group-share-stats">
          {{ translatedShareGroupStatus }}
        </b-dropdown-item>
      </b-dropdown>
    </div>
    <div v-else>
      <b-dropdown variant="primary" :text="translatedGroupActions" v-if="canedit">
        <b-dropdown-item data-toggle="modal" data-target="#invite-to-group" v-if="ingroup">
          {{ translatedInviteVolunteers }}
        </b-dropdown-item>
        <b-dropdown-item :href="'/group/join/' + groupId" v-else>
          {{ translatedJoinGroup }}
        </b-dropdown-item>
        <b-dropdown-item  data-toggle="modal" data-target="#group-share-stats">
          {{ translatedShareGroupStatus }}
        </b-dropdown-item>
      </b-dropdown>
    </div>
  </div>
</template>
<script>
import group from '../mixins/group'

export default {
  mixins: [ group ],
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
    }
  },
  methods: {
  }
}
</script>