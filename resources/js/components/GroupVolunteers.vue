<template>
  <CollapsibleSection collapsed :count="volunteers.length">
    <template slot="title">
      <span>
        {{ __('groups.volunteers') }}
        <span v-if="volunteers.length" class="font-weight-normal">
          ({{ volunteers.length}})
        </span>
      </span>
    </template>
    <template slot="content">
      <div class="mt-2">
        <div v-if="volunteers.length">
          <div class="maxheight" :key="'confirm-' + volunteers.length">
            <GroupVolunteer v-for="a in volunteers" :key="'group-' + a.id" :id="a.id" :canedit="canedit" :candemote="candemote" />
          </div>
          <div class="d-flex justify-content-between">
            <a class="justify-content-end" href="#" data-toggle="modal" data-target="#invite-to-group">
              {{ __('groups.invite_to_group') }}
            </a>
          </div>
        </div>
        <p v-else>
          {{ __('groups.no_volunteers') }}.
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
    idgroups: {
      type: Number,
      required: true
    }
  },
  mounted() {
    // Get the list of group volunteers
    this.$store.dispatch('volunteers/fetchGroup', this.idgroups)
  },
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
