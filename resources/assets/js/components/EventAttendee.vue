<template>
  <div class="pl-4 pr-4">
    <div class="pt-2 pb-2 blackbord d-flex justify-content-between">
      <div class="d-flex w-100">
        <b-img-lazy :src="profile" class="profile mr-2" rounded="circle" @error.native="brokenProfileImage" />
        <div class="namewidth flex-grow-1">
          <div class="d-flex flex-column">
            <div :class="{
            lineheight: true,
            'font-weight-bold': host,
            'd-flex': true,
            'flex-wrap': true
            }" :title="attendee.volunteer.name">
            <span class="pr-1 overflow-hidden ellipsis">
              {{ attendee.volunteer.name }}
            </span>
              <span class="host" v-if="host">
              {{ translatedHost }}
            </span>
            </div>
          <div :id="'skills-' + attendee.volunteer.id" data-toggle="popover" data-placement="top" :data-content="skillList" :class="{
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
      {{ translatedSomethingWrong }}: {{ error }}
    </b-alert>
    <ConfirmModal @confirm="remove" ref="confirm" />
  </div>
</template>
<script>
import { DEFAULT_PROFILE, HOST } from '../constants'
import ConfirmModal from './ConfirmModal'

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
    profile() {
      return this.attendee ? this.attendee.profilePath : DEFAULT_PROFILE
    },
    host() {
      return this.attendee.role === HOST
    },
    noskills() {
      return !this.attendee.volunteer.user_skills || !this.attendee.volunteer.user_skills.length
    },
    skillCount() {
      let ret = null
      let skills = this.attendee.volunteer.user_skills
      ret = (skills && skills.length ? skills.length : '0') + ' ' + this.$lang.choice(this.$lang.get('partials.skills'), skills.length)
      return ret
    },
    skillList() {
      let ret = null
      let skills = this.attendee.volunteer.user_skills

      if (skills) {
        let names = []
        skills.forEach((s) => {
          names.push(s.skill_name.skill_name)
        })

        ret = names.join(', ')
      }

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
          idevents: this.attendee.event,
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