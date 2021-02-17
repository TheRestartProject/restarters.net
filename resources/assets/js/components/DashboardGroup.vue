<template>
  <div>
    <div class="d-flex">
      <b-img-lazy :src="group.image" class="profile mr-2" @error.native="brokenProfileImage" v-if="group.image" />
      <b-img-lazy :src="defaultProfile" class="profile mr-2" v-else />
      <div class="d-flex flex-column justify-content-center">
        <a :href="'/group/view/' + group.idgroups">{{ group.name }}</a>
      </div>
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