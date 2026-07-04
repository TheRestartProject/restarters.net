<template>
  <div class="edit-panel">
    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <div class="alert alert-danger" role="alert">
      <div class="row">
        <div class="col-md-8 d-flex flex-column align-content-center">
          {{ __('auth.delete_account_text') }}
        </div>
        <div class="col-md-4 d-flex flex-column align-content-center">
          <b-btn
              variant="danger"
              :disabled="deleting"
              data-testid="delete-account-button"
              @click="showModal = true"
          >
            {{ __('auth.delete_account') }}
          </b-btn>
        </div>
      </div>
    </div>

    <b-modal
        id="deleteaccountmodal"
        v-model="showModal"
        :title="__('partials.are_you_sure')"
        no-stacking
    >
      <template slot="default">
        <p>{{ __('auth.delete_account_text') }}</p>
      </template>
      <template slot="modal-footer" slot-scope="{ cancel }">
        <b-button variant="white" @click="cancel">
          {{ __('partials.cancel') }}
        </b-button>
        <b-button
            variant="danger"
            :disabled="deleting"
            data-testid="delete-account-confirm"
            @click="deleteAccount"
        >
          {{ __('auth.delete_account') }}
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'DeleteAccountTab',
  data() {
    return {
      showModal: false,
      deleting: false,
      feedback: null,
      feedbackVariant: 'danger',
    }
  },
  methods: {
    async deleteAccount() {
      this.deleting = true
      this.feedback = null

      try {
        await axios.delete('/api/v2/users/me')
        window.location = '/login'
      } catch (e) {
        const message = e.response && e.response.data && e.response.data.message
        this.feedback = message || this.__('general.error_occurred')
        this.feedbackVariant = 'danger'
        this.deleting = false
        this.showModal = false
      }
    },
  },
}
</script>
