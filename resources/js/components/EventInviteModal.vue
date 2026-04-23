<template>
  <b-modal
      id="eventinvitemodal"
      v-model="showModal"
      :title="__('events.invite_restarters_modal_heading')"
      no-stacking
      size="lg"
  >
    <template slot="default">
      <div class="form-group" v-if="canedit && groupVolunteers && groupVolunteers.length > 0">
        <label>{{ __('events.select_group_members') }}:</label>
        <multiselect
            v-model="selectedMembers"
            :options="groupVolunteers"
            :multiple="true"
            :close-on-select="false"
            :clear-on-select="false"
            :preserve-search="true"
            :placeholder="__('events.select_members_placeholder')"
            track-by="user"
            :custom-label="memberLabel"
            :taggable="false"
            :selectedLabel="__('partials.remove')"
            selectLabel=""
            deselectLabel=""
        >
          <template slot="tag" slot-scope="{ option, remove }">
            <span class="multiselect__tag">
              <span>{{ option.name }}</span>
              <i aria-hidden="true" tabindex="1" class="multiselect__tag-icon" @click="remove(option)"></i>
            </span>
          </template>
          <template slot="option" slot-scope="{ option }">
            <span>{{ option.name }}</span>
          </template>
        </multiselect>
        <div class="form-check mt-2">
          <b-form-checkbox
              id="invites_to_volunteers"
              v-model="inviteGroupMembers"
              @change="onCheckboxChange"
          >
            {{ __('events.send_invites_to_restarters_tickbox', { group: groupName }) }}
          </b-form-checkbox>
        </div>
      </div>

      <div class="form-group">
        <label for="manual_invite_box">{{ __('events.manual_invite_box') }}:</label>
        <multiselect
            id="manual_invite_box"
            ref="emailMultiselect"
            v-model="manualEmails"
            :options="[]"
            :multiple="true"
            :taggable="true"
            :close-on-select="false"
            :clear-on-select="true"
            :placeholder="__('events.manual_invite_placeholder')"
            tag-placeholder="Press enter or tab to add email"
            @tag="addEmailTag"
            @keydown.native.tab="onTabKey"
            :class="{ 'has-invalid-email': hasInvalidEmail }"
        >
          <template slot="tag" slot-scope="{ option, remove }">
            <span class="multiselect__tag" :class="{ 'invalid-email': !isValidEmail(option) }">
              <span>{{ option }}</span>
              <i aria-hidden="true" tabindex="1" class="multiselect__tag-icon" @click="remove(option)"></i>
            </span>
          </template>
          <template slot="noOptions">
            <span></span>
          </template>
        </multiselect>
      </div>

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
      <b-button variant="primary" @mousedown.prevent="submit" :disabled="!canSubmit">
        {{ __('events.send_invite_button') }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
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
      manualEmails: [],
      messageToRestarters: '',
      inviteGroupMembers: false,
      groupId: null,
      groupName: '',
      selectedMembers: []
    }
  },
  computed: {
    canSubmit() {
      // Can submit if there are selected members OR manual email entries
      return this.selectedMembers.length > 0 || this.manualEmails.length > 0
    },
    hasInvalidEmail() {
      return this.manualEmails.some(email => !this.isValidEmail(email))
    },
    groupVolunteers() {
      if (!this.groupId) return []
      const volunteers = this.$store.getters['volunteers/byGroup'](this.groupId)
      return volunteers || []
    },
    // Combine selected member emails with manual email entries
    allEmails() {
      const memberEmails = this.selectedMembers
        .filter(v => v.email)
        .map(v => v.email)
      return [...memberEmails, ...this.manualEmails].join(', ')
    }
  },
  methods: {
    memberLabel(member) {
      return member.name
    },
    isValidEmail(email) {
      return email && email.indexOf('@') !== -1
    },
    addEmailTag(newTag) {
      // Strip commas and trim the tag, then add if not already present
      const trimmed = newTag.replace(/,/g, '').trim()
      if (trimmed && !this.manualEmails.includes(trimmed)) {
        this.manualEmails.push(trimmed)
      }
    },
    onTabKey(event) {
      // Get the current search/input value from the multiselect
      const multiselect = this.$refs.emailMultiselect
      if (multiselect && multiselect.search) {
        event.preventDefault()
        this.addEmailTag(multiselect.search)
        multiselect.search = ''
      }
    },
    async show() {
      // Reset form state
      this.manualEmails = []
      this.messageToRestarters = ''
      this.inviteGroupMembers = false
      this.selectedMembers = []

      // Get the event details to get group info
      const event = await this.$store.dispatch('events/fetch', {
        id: this.idevents
      })

      this.groupId = event.group.id
      this.groupName = event.group.name

      // Fetch group volunteers to get their emails
      await this.$store.dispatch('volunteers/fetchGroup', this.groupId)

      this.showModal = true
    },
    hide() {
      this.$emit('hide')
      this.showModal = false
    },
    onCheckboxChange(checked) {
      if (checked) {
        // Select all group members with emails
        this.selectedMembers = this.groupVolunteers.filter(v => v.email)
      } else {
        // Deselect all members
        this.selectedMembers = []
      }
    },
    submit() {
      // Create and submit a form (the controller expects form submission, not AJAX)
      const form = document.createElement('form')
      form.method = 'POST'
      form.action = '/party/invite'

      // Get CSRF token directly from meta tag for reliability
      const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content

      const fields = {
        '_token': csrfToken,
        'event_id': this.idevents,
        'group_name': this.groupName,
        'manual_invite_box': this.allEmails,
        'message_to_restarters': this.messageToRestarters
      }

      for (const [name, value] of Object.entries(fields)) {
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = name
        input.value = value
        form.appendChild(input)
      }

      document.body.appendChild(form)
      form.submit()
    }
  }
}
</script>

<style scoped>
/* Vue 2 deep selector for multiselect styling */
.invalid-email {
  background-color: #f8d7da !important;
  border-color: #dc3545 !important;
}

.has-invalid-email >>> .multiselect__tags {
  border: 2px solid #dc3545;
}

/* Target multiselect tags directly */
>>> .multiselect__tag.invalid-email {
  background-color: #f8d7da !important;
  border: 1px solid #dc3545 !important;
}
</style>
