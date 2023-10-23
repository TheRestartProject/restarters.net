<template>
  <div>
    <div class="pt-2 pb-2 blackbord d-flex justify-content-between">
      <div class="d-flex w-100">
        <a :href="'/profile/' + volunteer.volunteer.id">
          <b-img-lazy :src="profile" class="profile mr-2" rounded="circle" @error.native="brokenProfileImage" />
        </a>
        <div class="namewidth flex-grow-1">
          <div class="d-flex flex-column">
            <div :class="{
            lineheight: true,
            'font-weight-bold': host,
            'd-flex': true,
            'flex-wrap': true
            }" :title="volunteer.volunteer.name">
            <a :href="'/profile/' + volunteer.volunteer.id" class="pr-1 overflow-hidden ellipsis text-black">
              {{ volunteer.volunteer.name }}
            </a>
              <span class="host" v-if="host">
              {{ __('partials.host') }}
            </span>
            </div>
            <div :id="'skills-' + volunteer.volunteer.id" :class="{
             'small': true,
             'd-flex': true,
             'clickme' : true,
             'text-muted': noskills
            }">
              <div data-toggle="popover" data-placement="left" :data-content="skillList">
                <b-img-lazy src="/images/star.svg" :class="{
                   'star': true,
                   'mr-1': true,
                   'faded': noskills
                  }" /> {{ skillCount }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <b-dropdown v-if="canedit" variant="none" ref="dropdown" class="edit-dropdown" no-caret>
        <b-dropdown-item :href="'/group/make-host/' + idgroups + '/' + volunteer.user" v-if="volunteer.role === restarter">{{ __('groups.make_host') }}</b-dropdown-item>
        <b-dropdown-item target="_blank" rel="noopener" :href="'/group/remove-volunteer/' + idgroups + '/' + volunteer.user">{{ __('groups.remove_volunteer') }}</b-dropdown-item>
      </b-dropdown>
      <button class="dropdown-toggle d-none" />
    </div>
    <b-alert variant="danger" v-if="error">
      {{ __('partials.something_wrong') }}: {{ error }}
    </b-alert>
    <ConfirmModal @confirm="remove" ref="confirm" />
  </div>
</template>
<script>
import { DEFAULT_PROFILE, HOST, RESTARTER } from '../constants'
import ConfirmModal from './ConfirmModal'
import volunteers from '../store/volunteers'

export default {
  components: {ConfirmModal},
  props: {
    idgroups: {
      type: Number,
      required: true
    },
    volunteer: {
      type: Object,
      required: true
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  data () {
    return {
      error: null,
      restarter: RESTARTER,
      show: false
    }
  },
  computed: {
    profile() {
      return this.volunteer ? this.volunteer.profilePath : DEFAULT_PROFILE
    },
    host() {
      return this.volunteer.role === HOST
    },
    noskills() {
      return !this.volunteer.user_skills || !this.volunteer.user_skills.length
    },
    skillCount() {
      let ret = null
      let skills = this.volunteer.user_skills
      let len = skills && skills.length ? skills.length : 0
      ret = len + ' ' + this.$lang.choice('partials.skills', len)
      return ret
    },
    skillList() {
      let ret = null

      let skills = this.volunteer.user_skills

      if (skills) {
        let names = []
        skills.forEach((s) => {
          names.push(s.skill_name)
        })

        ret = names.join(', ')
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
          userId: this.volunteer.user,
          eventId: this.volunteer.event,
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
