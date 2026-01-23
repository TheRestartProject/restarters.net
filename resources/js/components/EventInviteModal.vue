<template>
  <b-modal
      id="eventinvitemodal"
      v-model="showModal"
      :title="__('events.invite_restarters_modal_heading')"
      no-stacking
      size="lg"
  >
    <template slot="default">
      <div class="form-group">
        <label for="manual_invite_box">{{ __('events.manual_invite_box') }}:</label>
        <b-form-textarea
            id="manual_invite_box"
            v-model="manualInviteBox"
            rows="3"
            :placeholder="__('events.manual_invite_placeholder') || 'Enter email addresses separated by commas'"
        />
      </div>

      <div class="form-check" v-if="canedit">
        <b-form-checkbox
            id="invites_to_volunteers"
            v-model="inviteGroupMembers"
            @change="onCheckboxChange"
        >
          {{ __('events.send_invites_to_restarters_tickbox', { group: groupName }) }}
        </b-form-checkbox>
      </div>
      <br v-if="canedit" />

      <small class="after-offset">{{ __('events.type_email_addresses_message') }}</small>
      <hr/>

      <div class="form-group">
        <label for="message_to_restarters">{{ __('events.message_to_restarters') }}:</label>
        <b-form-textarea
            id="message_to_restarters"
            v-model="messageToRestarters"
            rows="3"
            :placeholder="__('events.sample_text_message_to_restarters')"
        />
      </div>
    </template>

    <template slot="modal-footer">
      <a href="#" class="text-dark mb-0 mr-auto" @click.prevent="hide">{{ __('events.cancel_invites_link') }}</a>
      <b-button variant="primary" @click="submit" :disabled="!canSubmit">
        {{ __('events.send_invite_button') }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
import axios from 'axios'

export default {
  props: {
    idevents: {
      type: Number,
      required: true,
    },
    canedit: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data() {
    return {
      showModal: false,
      manualInviteBox: '',
      messageToRestarters: '',
      inviteGroupMembers: false,
      groupId: null,
      groupName: '',
      memberEmails: []
    }
  },
  computed: {
    canSubmit() {
      return this.manualInviteBox.trim().length > 0
    },
    groupVolunteers() {
      return this.groupId ? this.$store.getters['volunteers/byGroup'](this.groupId) : []
    }
  },
  methods: {
    async show() {
      // Reset form state
      this.manualInviteBox = ''
      this.messageToRestarters = ''
      this.inviteGroupMembers = false

      // Get the event details to get group info
      const event = await this.$store.dispatch('events/fetch', {
        id: this.idevents
      })

      this.groupId = event.group.id
      this.groupName = event.group.name

      // Fetch group volunteers to get their emails
      await this.$store.dispatch('volunteers/fetchGroup', this.groupId)

      // Extract emails from volunteers
      this.memberEmails = this.groupVolunteers
        .filter(v => v.email)
        .map(v => v.email)

      this.showModal = true
    },
    hide() {
      this.$emit('hide')
      this.showModal = false
    },
    onCheckboxChange(checked) {
      if (checked && this.memberEmails.length > 0) {
        // Add member emails to textarea
        const currentEmails = this.manualInviteBox.trim()
        if (currentEmails) {
          // Append to existing emails, avoiding duplicates
          const existingEmails = currentEmails.split(',').map(e => e.trim().toLowerCase())
          const newEmails = this.memberEmails.filter(email =>
            !existingEmails.includes(email.trim().toLowerCase())
          )
          if (newEmails.length > 0) {
            this.manualInviteBox = currentEmails + ', ' + newEmails.join(', ')
          }
        } else {
          this.manualInviteBox = this.memberEmails.join(', ')
        }
      } else if (!checked && this.memberEmails.length > 0) {
        // Remove member emails from textarea when unchecked
        const currentEmails = this.manualInviteBox.trim()
        if (currentEmails) {
          const memberEmailList = this.memberEmails.map(e => e.trim().toLowerCase())
          const remainingEmails = currentEmails.split(',')
            .map(e => e.trim())
            .filter(email => !memberEmailList.includes(email.toLowerCase()))
          this.manualInviteBox = remainingEmails.join(', ')
        }
      }
    },
    async submit() {
      try {
        await axios.post('/party/invite', {
          event_id: this.idevents,
          group_name: this.groupName,
          manual_invite_box: this.manualInviteBox,
          message_to_restarters: this.messageToRestarters,
          invite_group: this.inviteGroupMembers ? 1 : 0
        }, {
          headers: {
            'X-CSRF-TOKEN': this.$store.getters['auth/CSRF']
          }
        })

        this.hide()
        // Refresh the page to show updated invitations
        window.location.reload()
      } catch (error) {
        console.error('Failed to send invites:', error)
      }
    }
  }
}
</script>
