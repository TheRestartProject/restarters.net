<template>
  <div>
    <div class="pt-2 pb-2 blackbord d-flex justify-content-between">
      <div class="d-flex w-100">
        <b-img-lazy :src="profile" class="profile mr-2" rounded="circle" @error.native="brokenProfileImage" />
        <div class="namewidth flex-grow-1">
          <div class="d-flex flex-column">
            <div :class="{
            lineheight: true,
            'font-weight-bold': volunteer.host,
            'd-flex': true,
            'flex-wrap': true
            }" :title="volunteer.name">
            <span class="pr-1 overflow-hidden ellipsis">
              {{ volunteer.name }}
            </span>
              <span class="host" v-if="volunteer.host">
              {{ __('partials.host') }}
            </span>
            </div>
            <div :id="'skills-' + volunteer.id" :class="{
             'small': true,
             'd-flex': true,
             'clickme' : true,
             'text-muted': noskills
            }">
              <div v-b-tooltip.hover :title="skillList">
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
        <b-dropdown-item @click="removeVolunteer">{{ __('groups.remove_volunteer') }}</b-dropdown-item>
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
    id: {
      type: Number,
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
    volunteer() {
      return this.$store.getters['volunteers/byIDGroup'](this.id)
    },
    profile() {
      return this.volunteer ? this.volunteer.image : DEFAULT_PROFILE
    },
    noskills() {
      return !this.volunteer.skills || !this.volunteer.skills.length
    },
    skillCount() {
      let ret = null
      let skills = this.volunteer.skills
      let len = skills && skills.length ? skills.length : 0
      ret = len + ' ' + this.$lang.choice('partials.skills', len)
      return ret
    },
    skillList() {
      let ret = null

      let skills = this.volunteer.skills

      if (skills) {
        let names = []
        skills.forEach((s) => {
          names.push(s.name)
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
    async removeVolunteer() {
      try {
        await this.$store.dispatch('volunteers/remove', this.volunteer.id)
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
