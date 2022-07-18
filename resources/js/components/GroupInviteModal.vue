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
        <h5 id="inviteToGroupLabel">{{ __('groups.invite_restarters_modal_heading') }}</h5>
        <b-button variant="link"  @click="toggleInvite" v-if="invitingViaEmail" class="d-flex pt-0">
          <b-img src="/icons/link_ico.svg"  class="icon mr-1" />
          {{ __('groups.shareable_link') }}
        </b-button>
        <b-button variant="link" @click="toggleInvite" v-else class="d-flex pt-0">
          <b-img src="/images/mail_ico.svg" class="icon mr-1 mt-1" />
          {{ __('groups.email_invite') }}
        </b-button>
      </div>
    </template>

    <div v-if="invitingViaEmail">
      <form action="/group/invite" method="post" ref="form">
        <input type="hidden" name="_token" :value="CSRF" />
        <input type="hidden" name="group_name" :value="group.name.trim()">
        <input type="hidden" id="group_id" name="group_id" :value="idgroups">

        <div id="invite_div" class="form-group">
          <label for="manual_invite_box">{{ __('groups.email_addresses_field') }}:</label>
          <input id="manual_invite_box" type="email" inputmode="text" multiple name="manual_invite_box" class="form-control" autocomplete="off" onblur="reportValidity()" v-model="emails">
        </div>
        <small class="after-offset">{{ __('groups.type_email_addresses_message') }}</small>
        <br>
        <hr/>
        <div class="form-group">
          <label for="message_to_restarters">{{ __('groups.message_header') }}:</label>
          <textarea name="message_to_restarters" id="message_to_restarters" class="form-control field" :placeholder="__('groups.message_example_text')" rows="3"></textarea>
        </div>
      </form>
    </div>
    <div v-else>
      <div class="form-group">
        <label for="shareable_link_box">{{ __('groups.shareable_link_box') }}:</label>
        <input type="text" id="shareable_link_box" name="shareable_link_box" class="form-control" autocomplete="off" :value="group.ShareableLink">
      </div>
      <small class="after-offset">{{ __('groups.type_shareable_link_message') }}</small>
    </div>

    <template slot="modal-footer" slot-scope="cancel">
      <div class="d-flex flex-row justify-content-between align-items-center">
        <b-button variant="link" @click="showModal = false">{{ __('groups.cancel_invites_link') }}</b-button>
        <b-button variant="primary" @click="submit" v-if="invitingViaEmail">{{ __('groups.send_invite_button') }}</b-button>
        <b-button variant="primary"v-else @click="showModal = false">{{ __('groups.done_button') }}</b-button>
      </div>
    </template>

  </b-modal>
</template>
<script>
export default {
  props: {
    idgroups: {
      type: Number,
      required: true
    },
  },
  data () {
    return {
      showModal: false,
      invitingViaEmail: true,
      emails: ""
    }
  },
  computed: {
    CSRF () {
      return this.$store.getters['auth/CSRF']
    },
    group() {
      return this.$store.getters['groups/get'](this.idgroups)
    },
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