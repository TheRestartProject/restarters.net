<template>
  <CollapsibleSection collapsed :count="volunteers.length">
    <template slot="title">
      {{ translatedTitle }}
    </template>
    <template slot="content">
      <div class="mt-2">
        <div v-if="volunteers.length">
          <div class="maxheight" :key="'confirm-' + volunteers.length">
            <GroupVolunteer v-for="a in volunteers" :key="'group-' + a.idusers_groups" :volunteer="a" :canedit="canedit" v-if="!a.deleted_at" :group-id="groupId" />
          </div>
          <div class="d-flex justify-content-between">
            <a class="justify-content-end" href="#" data-toggle="modal" data-target="#invite-to-group">
              {{ translatedInviteToGroup }}
            </a>
            <a class="justify-content-end" href="#" data-toggle="modal" data-target="#group-volunteers">
              {{ translatedSeeAll }}
            </a>
          </div>
        </div>
        <p v-else>
          {{ translatedNoVolunteers }}.
        </p>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import group from '../mixins/group'
import GroupVolunteer from './GroupVolunteer'
import CollapsibleSection from './CollapsibleSection'
import Group from '../mixins/group'

export default {
  components: {Group, CollapsibleSection, GroupVolunteer},
  mixins: [group],
  props: {
    groupId: {
      type: Number,
      required: true
    },
    group: {
      type: Object,
      required: true
    },
    volunteers:  {
      type: Array,
      required: true
    },
    // TODO LATER In due course the permissions should be handled by having the user in the store and querying that, rather
    // than passing down props.
    canedit: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    translatedTitle() {
      return this.$lang.get('groups.volunteers')
    },
    translatedSeeAll() {
      return this.pluralise(this.$lang.get('groups.see_all_volunteers', {
        count: this.volunteers.length
      }), this.volunteers.length)
    },
    translatedNoVolunteers() {
      return this.$lang.get('groups.no_volunteers')
    },
    translatedInviteToGroup() {
      return this.$lang.get('groups.invite_to_group');
    }
  },
  created() {
    // The list of volunteers is passed from the server to the client via a prop on this component.  When we are created
    // we put it in the store.  From then on we get the data from the store so that we get reactivity.
    //
    // Further down the line this initial data might be provided either by an API call from the client to the server,
    // or from Vue server-side rendering, where the whole initial state is passed to the client.
    //
    // Similarly the group should be in the store and passed just by id, but we haven't introduced a group store yet.
    this.$store.dispatch('volunteers/set', {
      groupId: this.groupId,
      volunteers: this.volunteers
    })
  },
  methods: {
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.volunteer-tabs {
  height: 380px;

  ::v-deep .nav-item {
    width: 50%;
  }
}

.maxheight {
  max-height: 240px;
  min-height: 240px;
  overflow-y: auto;
  overflow-x: hidden
}

h2 {
  font-size: 24px;
  font-weight: bold;
}

h3 {
  font-size: 18px;
  font-weight: bold;
}

.icon {
  width: 30px;
}

.warningbox {
  border: 1px solid $brand-danger;
}
</style>