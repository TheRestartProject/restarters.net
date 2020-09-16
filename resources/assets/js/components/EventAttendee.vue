<template>
  <div class="pl-4 pr-4">
    <div class="pt-2 pb-2 blackbord d-flex justify-content-between">
      <div class="d-flex">
        <b-img-lazy :src="profile" class="profile mr-2" rounded="circle" @error.native="brokenProfileImage" />
        <div class="d-flex flex-column">
          <div :class="{
            lineheight: true,
            'font-weight-bold': host
            }">
            {{ attendee.volunteer.name }}
            <span class="host pl-1" v-if="host">
              {{ translatedHost }}
            </span>
          </div>
          <div v-if="skills" class="small d-flex">
           <b-img-lazy src="/images/star.svg" class="star mr-1" /> {{ skills }}
          </div>
        </div>
      </div>
      <div v-if="attendee.confirmed">
        <b-img src="/icons/delete_ico_red.svg" />
      </div>
    </div>
<!--    TODO Make remvoe work. Only host or admin can remove-->
  </div>
</template>
<script>
import { DEFAULT_PROFILE, HOST } from '../constants'

export default {
  props: {
    attendee: {
      type: Object,
      required: true
    }
  },
  computed: {
    profile() {
      return this.attendee.volunteer && this.attendee.volunteer.profilePath ? this.attendee.volunteer.profilePath : DEFAULT_PROFILE
    },
    host() {
      return this.attendee.role === HOST
    },
    translatedHost() {
      return this.$lang.get('partials.host')
    },
    skills() {
      let ret = null
      let skills = this.attendee.volunteer.user_skills

      if (skills && skills.length) {
        ret = skills.length + ' ' + this.pluralise(this.$lang.get('partials.skills'), skills.length)
      }

      return ret
    }
  },
  methods: {
    brokenProfileImage(event) {
      event.target.src = DEFAULT_PROFILE
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';

.blackbord {
  border-bottom: 1px solid $black;
}

.profile {
  width: 40px;
  height: 40px;
  border: 1px solid $black;
}

.lineheight {
  line-height: 1;
}

.star {
  width: 16px;
}

.host {
  text-transform: uppercase;
  color: $brand-light;
}
</style>