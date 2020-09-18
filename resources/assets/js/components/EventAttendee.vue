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
            <span class="flex-shrink-1">
              {{ attendee.volunteer.name }}
            </span>
            <span class="host pl-1" v-if="host">
              {{ translatedHost }}
            </span>
          </div>
          <div :class="{
             'small': true,
             'd-flex': true,
             'text-muted': noskills
            }">
           <b-img-lazy src="/images/star.svg" :class="{
             'star': true,
             'mr-1': true,
             'faded': noskills
            }" /> {{ skills }}
          </div>
        </div>
      </div>
      <b-btn variant="none" v-if="attendee.confirmed" @click="remove" class="p-0">
        <b-img src="/icons/delete_ico_red.svg" />
      </b-btn>
    </div>
    <b-alert variant="danger" v-if="error">
      {{ translatedSomethingWrong }}: {{ error }}
    </b-alert>
<!--    TODO Only host or admin can remove-->
<!--    TODO Remove confirm-->
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
  data () {
    return {
      error: null
    }
  },
  computed: {
    profile() {
      return this.attendee.volunteer && this.attendee.volunteer.profilePath ? this.attendee.volunteer.profilePath : DEFAULT_PROFILE
    },
    host() {
      return this.attendee.role === HOST
    },
    noskills() {
      return !this.attendee.volunteer.user_skills || !this.attendee.volunteer.user_skills.length
    },
    skills() {
      let ret = null
      let skills = this.attendee.volunteer.user_skills
      ret = (skills && skills.length ? skills.length : '0') + ' ' + this.pluralise(this.$lang.get('partials.skills'), skills.length)
      return ret
    },
    translatedHost() {
      return this.$lang.get('partials.host')
    },
    translatedSomethingWrong() {
      return this.$lang.get('partials.something_wrong')
    },
  },
  methods: {
    brokenProfileImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
    async remove() {
      try {
        await this.$store.dispatch('attendance/remove', {
          userId: this.attendee.user,
          eventId: this.attendee.event,
        })
      } catch (e) {
        this.error = e.message
      }
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
  white-space: nowrap;
  text-overflow: ellipsis;
}

.star {
  width: 16px;
}

.host {
  text-transform: uppercase;
  color: $brand-light;
}

.faded {
  opacity: 0.5;
}
</style>