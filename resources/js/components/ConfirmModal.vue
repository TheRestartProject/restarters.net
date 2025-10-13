<template>
  <b-modal
      id="confirmmodal"
      v-model="showModal"
      :title="translatedAreYouSure"
      no-stacking
  >
    <template slot="default">
      <p>
        <!-- eslint-disable-next-line -->
        <span v-html="translatedPleaseConfirm" />
      </p>
    </template>
    <template slot="modal-footer" slot-scope="{ ok, cancel }">
      <!-- eslint-disable-next-line -->
      <b-button variant="white" @click="cancel" v-html="translatedCancel" />
      <!-- eslint-disable-next-line -->
      <b-button variant="primary" @click="confirm" v-html="translatedConfirm" />
    </template>
  </b-modal>
</template>
<script>
export default {
  props: {
    title: {
      type: String,
      required: false,
      default: null
    },
    message: {
      type: String,
      required: false,
      default: null
    }
  },
  data: function() {
    return {
      showModal: false
    }
  },
  computed: {
    translatedCancel() {
      return this.__('partials.cancel')
    },
    translatedConfirm() {
      return this.__('partials.confirm')
    },
    translatedAreYouSure() {
      return this.title ? this.title : this.__('partials.are_you_sure')
    },
    translatedPleaseConfirm() {
      return this.message ? this.message : this.__('partials.please_confirm')
    }
  },
  methods: {
    show() {
      this.showModal = true
    },

    hide() {
      this.showModal = false
    },

    confirm() {
      this.$emit('confirm')
      this.hide()
    }
  }
}
</script>
