<template>
  <div>
    <div class="d-flex justify-content-between mb-3">
      <h1 class="d-block d-md-none">{{ __('groups.groups') }}</h1>
      <GroupActions :idgroups="idgroups" :can-see-delete="canSeeDelete" :can-perform-delete="canPerformDelete"
                    :can-perform-archive="canPerformArchive"
                    class="d-block d-md-none" @left="$emit('left')"/>
    </div>
    <div class="border-top-very-thick border-bottom-thin mb-3">
      <div class="d-flex flex-wrap mt-4 mb-3 mb-md-3">
        <div class="bord d-flex w-xs-100 w-md-50">
          <b-img @error="brokenGroupImage" :src="groupImage" class="groupImage align-self-start mr-4 mb-3"/>
          <h1>
            {{ group.name }}
          </h1>
        </div>
        <div class="pl-md-4 d-flex w-xs-100 w-md-50 maybeborder pt-3 p-md-0 d-flex flex-column justify-content-center">
          <div class="d-flex justify-content-between w-100">
            <div class="flex-wrap">
              <b>{{ group.location }}</b> <br/>
              <ExternalLink v-if="group.website" :href="group.website">{{ __('groups.website') }}</ExternalLink>
            </div>
            <GroupActions :idgroups="idgroups" :can-see-delete="canSeeDelete" :can-perform-delete="canPerformDelete"
                          :can-perform-archive="canPerformArchive"
                          class="d-none d-md-block" @left="$emit('left')"/>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import {DEFAULT_PROFILE} from '../constants'
import group from '../mixins/group'
import GroupActions from './GroupActions'
import ExternalLink from './ExternalLink'

export default {
  components: {ExternalLink, GroupActions},
  mixins: [group],
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
    groupImage() {
      return this.group && this.group.group_image && this.group.group_image.image ? ('/uploads/mid_' + this.group.group_image.image.path) : DEFAULT_PROFILE
    },
  },
  methods: {
    brokenGroupImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.border-top-very-thick {
  border-top: 5px solid $black;
}

.border-bottom-thin {
  border-bottom: 1px solid $black;
}

.bord {
  @include media-breakpoint-up(md) {
    border-right: 1px solid $black;
  }
}

.groupImage {
  width: 67px;
}

.maybeborder {
  @include media-breakpoint-down(sm) {
    border-top: 1px solid $black;
  }
}

.centreme {
  align-items: center;
  display: flex !important;
}
</style>