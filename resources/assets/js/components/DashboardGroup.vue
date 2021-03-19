<template>
  <div>
    <div class="d-flex justify-content-between flex-wrap">
      <div class="d-flex">
        <b-img-lazy :src="groupImage" class="profile mr-2" @error.native="brokenProfileImage" />
        <div class="d-flex flex-column justify-content-center">
          <a :href="'/group/view/' + group.idgroups">{{ group.name }}</a>
        </div>
      </div>
      <b-btn variant="primary" :href="'/group/join/' + group.idgroups" class="mt-2 mt-md-0">
        {{ translatedFollowGroup }}
      </b-btn>
    </div>
    <hr />
  </div>
</template>
<script>
import { DEFAULT_PROFILE } from '../constants'

export default {
  props: {
    group: {
      type: Object,
      required: true
    }
  },
  computed: {
    defaultProfile() {
      return DEFAULT_PROFILE
    },
    groupImage() {
      return this.group && this.group.group_image && this.group.group_image.image ? ('/uploads/mid_' + this.group.group_image.image.path) : DEFAULT_PROFILE
    },
    translatedFollowGroup() {
      return this.$lang.get('events.follow_group')
    }
  },
  methods: {
    brokenProfileImage(event) {
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

.profile {
  border: 1px solid black;
  width: 48px;
  height: 48px;
}

a {
  text-decoration: underline;
  color: #222;
}

hr {
  border-top: 1px solid black;
}
</style>