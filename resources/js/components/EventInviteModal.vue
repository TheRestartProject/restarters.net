<template>
  <b-modal
      v-model="showModal"
      no-stacking
      no-body
      size="lg"
      title-class="w-100"
  >
    <template slot="modal-title">
      <div class="d-flex justify-content-between w-100">
        <h5 id="inviteToEventLabel">{{ __('events.invite_restarters_modal_heading') }}</h5>
        <b-button variant="link"  @click="toggleInvite" v-if="invitingViaEmail" class="d-flex pt-0">
          <b-img src="/icons/link_ico.svg"  class="icon mr-1" />
          {{ __('events.shareable_link') }}
        </b-button>
        <b-button variant="link" @click="toggleInvite" v-else class="d-flex pt-0">
          <b-img src="/images/mail_ico.svg" class="icon mr-1 mt-1" />
          {{ __('events.email_invite') }}
        </b-button>
      </div>
    </template>

    <div v-if="invitingViaEmail">
      <form action="/party/invite" method="post" ref="form">
        <input type="hidden" name="_token" :value="CSRF" />
        <input type="hidden" name="group_name" :value="groupName">
        <input type="hidden" id="event_id" name="event_id" :value="idevents">

        <div id="invite_div" class="form-group">
          <label for="manual_invite_box">{{ __('events.manual_invite_box') }}:</label>
          <input id="manual_invite_box" type="email" inputmode="text" multiple name="manual_invite_box" class="form-control" autocomplete="off" onblur="reportValidity()" v-model="emails">
        </div>
        <small class="after-offset">{{ __('events.type_email_addresses_message') }}</small>
        <div class="form-check" v-if="canedit">
          <label class="form-check-label" for="invites_to_volunteers">
            <input type="checkbox" name="invite_group" class="form-check-input" id="invites_to_volunteers" v-model="volunteers">
            {{ __('events.send_invites_to_restarters_tickbox') }}
          </label>
        </div>
        <br>
        <hr/>
        <div class="form-group">
          <label for="message_to_restarters">{{ __('events.message_to_restarters') }}:</label>
          <textarea name="message_to_restarters" id="message_to_restarters" class="form-control field" :placeholder="__('events.sample_text_message_to_restarters')" rows="3"></textarea>
        </div>
      </form>
    </div>
    <div v-else>
      <div class="form-group">
        <label for="shareable_link_box">{{ __('events.shareable_link_box') }}:</label>
        <input type="text" id="shareable_link_box" name="shareable_link_box" class="form-control" autocomplete="off" :value="event.ShareableLink">
      </div>
      <small class="after-offset">{{ __('events.type_shareable_link_message') }}</small>
    </div>

    <template slot="modal-footer" slot-scope="cancel">
      <div class="d-flex flex-row justify-content-between align-items-center">
        <b-button variant="link" @click="showModal = false">{{ __('events.cancel_invites_link') }}</b-button>
        <b-button variant="primary" @click="submit" v-if="invitingViaEmail">{{ __('events.send_invite_button') }}</b-button>
        <b-button variant="primary"v-else @click="showModal = false">{{ __('groups.done_button') }}</b-button>
      </div>
    </template>

  </b-modal>
</template>
<script>
export default {
  props: {
    groupName: {
      type: String,
      required: true
    },
    idevents: {
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
      showModal: false,
      invitingViaEmail: true,
      volunteers: false,
      emails: ""
    }
  },
  computed: {
    CSRF () {
      return this.$store.getters['auth/CSRF']
    },
    event() {
      return this.$store.getters['events/get'](this.idevents)
    },
  },
  watch: {
    async volunteers(val) {
      const volunteers = await this.$store.dispatch('events/getVolunteers', {
        idevents: this.idevents
      })

      // Add any which are not present.
      let emails = this.emails.split(',')
      volunteers.forEach(function(volunteer) {
        if (emails.indexOf(volunteer.email) == -1) {
          emails.push(volunteer.email)
        }
      })

      // remove empty values from emails
      emails = emails.filter(function(email) {
        return email.length > 0
      })

      this.emails = emails.join(',')
    }
  },
  methods: {
    show() {
      this.showModal = true
    },
    toggleInvite() {
      this.invitingViaEmail = !this.invitingViaEmail
    },
    submit() {
      // The submit will redirect us.
      this.$refs.form.submit()
      this.showModal = false
    },
  }
}
</script>
<style scoped lang="scss">
.icon {
  width: 13px;
}
</style>