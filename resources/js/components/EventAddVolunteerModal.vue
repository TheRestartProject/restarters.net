<template>
  <b-modal
      id="addvolunteermodal"
      v-model="showModal"
      :title="translatedAddVolunteerModalHeading"
      no-stacking
  >
    <template slot="default">
      <div class="form-group">
        <label for="group_member">{{ translatedGroupMember }}:</label>
        <b-select v-model="user" :options="options" id="group_member" />
      </div>
      <div v-if="user === 'not-registered'">
        <div class="form-group">
          <label for="full_name">{{ translatedFullName }}:</label>
          <b-input id="full_name" type="text" class="form-control field" v-model="fullName" :placeholder="translatedFullNameHelper" />
        </div>

        <div class="form-group">
          <label for="volunteer_email_address">{{ translatedVolunteerEmailAddress }}:</label>
          <b-input type="email" v-model="volunteerEmailAddress" id="volunteer_email_address" />
        </div>
        <small class="after-offset">{{ translatedMessageVolunteerEmailAddress }}</small>
      </div>
    </template>
    <template slot="modal-footer" slot-scope="{ ok, cancel }">
      <!-- eslint-disable-next-line -->
      <b-button variant="primary" @click="submit" v-html="translatedVolunteerAttended" :disabled="disabled" />
    </template>
  </b-modal>
</template>
<script>
const axios = require('axios')

export default {
  props: {
    idevents: {
      type: Number,
      required: true,
    }
  },
  data: function() {
    return {
      showModal: false,
      user: null,
      fullName: null,
      volunteerEmailAddress: null
    }
  },
  computed: {
    options() {
      const ret = [
        {
          value: null,
          text: this.translatedOptionDefault
        }
      ]

      this.groupVolunteers.forEach((v) => {
        ret.push({
          value: v.id,
          text: v.name
        })
      })

      ret.push({
        value: 'not-registered',
        text: this.translatedOptionNotRegistered
      })

      return ret
    },
    disabled() {
      // Blank name is allowed.
      return this.user === null || (this.user === 'not-registered' && !this.volunteerEmailAddress)
    },
    translatedOptionDefault() {
      return this.$lang.get('events.option_default')
    },
    translatedAddVolunteerModalHeading() {
      return this.$lang.get('events.add_volunteer_modal_heading')
    },
    translatedGroupMember() {
      return this.$lang.get('events.group_member')
    },
    translatedVolunteerAttended() {
      return this.$lang.get('events.volunteer_attended_button')
    },
    translatedOptionNotRegistered() {
      return this.$lang.get('events.option_not_registered')
    },
    translatedFullName() {
      return this.$lang.get('events.full_name')
    },
    translatedFullNameHelper() {
      return this.$lang.get('events.full_name_helper')
    },
    translatedVolunteerEmailAddress() {
      return this.$lang.get('events.volunteer_email_address')
    },
    translatedMessageVolunteerEmailAddress() {
      return this.$lang.get('events.message_volunteer_email_address')
    }
  },
  methods: {
    show() {
      this.showModal = true
    },
    hide() {
      this.showModal = false
    },
    async submit() {
      // TODO We don't handle failures very well.  One way to do this is to move the axios requests into store actions,
      // which we will do at some point, and then have a generic "something went wrong" popup to alert the user, as
      // well as Sentry to alert us.
      await axios.put('/api/party/' + this.idevents + '/volunteers', {
        'user': this.user,
        'full_name': this.fullName,
        'volunteer_email_address': this.volunteerEmailAddress
      })

      this.hide()
    }
  }
}
</script>