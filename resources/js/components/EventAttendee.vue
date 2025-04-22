<template>
  <div class="pl-4 pr-4">
    <div class="pt-2 pb-2 blackbord d-flex justify-content-between">
      <div class="d-flex w-100">
        <a :href="link">
          <b-img-lazy :src="profile" class="profile mr-2" rounded="circle" @error.native="brokenProfileImage" />
        </a>
        <div class="namewidth flex-grow-1">
          <div class="d-flex flex-column">
            <div :class="{
            lineheight: true,
            'font-weight-bold': host,
            'd-flex': true,
            'flex-wrap': true
            }" :title="name">
            <a :href="link" class="pr-1 overflow-hidden ellipsis text-black">
              {{ name }}
            </a>
              <span class="host" v-if="host">
              {{ __('partials.host') }}
            </span>
            </div>
          <div :id="'skills-' + attendee.idevents_users" data-toggle="popover" data-placement="top" :data-content="skillList" :class="{
             'small': true,
             'd-flex': true,
             'clickme' : true,
             'text-muted': noskills
            }">
              <b-img-lazy src="/images/star.svg" :class="{
             'star': true,
             'mr-1': true,
             'faded': noskills
            }" /> {{ skillCount }}
            </div>
          </div>
        </div>
      </div>
      <b-btn variant="none" v-if="attendee.confirmed && canedit" @click="confirm" class="p-0">
        <b-img src="/icons/delete_ico_red.svg" />
      </b-btn>
    </div>
    <b-alert variant="danger" v-if="error">
      {{ __('partials.something_wrong') }}: {{ error }}
    </b-alert>
    <ConfirmModal @confirm="remove" ref="confirm" />
  </div>
</template>
<script>
import { DEFAULT_PROFILE, HOST } from '../constants'
import ConfirmModal from './ConfirmModal.vue'

export default {
  components: {ConfirmModal},
  props: {
    attendee: {
      type: Object,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      error: null
    }
  },
  computed: {
    link() {
      if (this.attendee.user) {
        return '/profile/' + this.attendee.user
      } else {
        return null
      }
    },
    name() {
      if (this.attendee.volunteer) {
        // Volunteer registered on Restarters.
        return this.attendee.volunteer.name
      } else {
        // Not registered.
        return this.attendee.fullName
      }
    },
    profile() {
      return this.attendee ? this.attendee.profilePath : DEFAULT_PROFILE
    },
    host() {
      return this.attendee.role === HOST
    },
    noskills() {
      return !this.attendee.volunteer || !this.attendee.volunteer.user_skills || !this.attendee.volunteer.user_skills.length
    },
    skillCount() {
      let ret = null

      if (this.attendee.volunteer) {
        let skills = this.attendee.volunteer.user_skills
        ret = (skills && skills.length ? skills.length : '0') + ' ' + this.$lang.choice('partials.skills', skills.length)
      } else {
        ret = '0 ' + this.$lang.choice('partials.skills', 0)
      }

      return ret
    },
    skillList() {
      let ret = null

      if (this.attendee.volunteer) {
        let skills = this.attendee.volunteer.user_skills

        if (skills) {
          let names = []
          skills.forEach((s) => {
            names.push(s.skill_name.skill_name)
          })

          ret = names.join(', ')
        }
      }

      return ret
    },
  },
  methods: {
    brokenProfileImage(event) {
      event.target.src = DEFAULT_PROFILE
    },
    async remove() {
      try {
        await this.$store.dispatch('attendance/remove', {
          id: this.attendee.idevents_users
        })
      } catch (e) {
        this.error = e.message
      }
    },
    confirm() {
      this.$refs.confirm.show()
    }
  },
  mounted() {
    this.$nextTick(() => {
      // For some reason b-popup doesn't work.  This is probably an interaction between the global JS, Bootstrap and
      // Bootstrap Vue, but it's not obvious what.  Enable this popover directly here.
      $('[data-toggle="popover"]').popover()
    })
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

.namewidth {
  max-width: calc(100% - 60px);
}

.ellipsis {
  text-overflow: ellipsis;
}
</style>
