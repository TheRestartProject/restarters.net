<template>
  <b-modal
      id="calendar"
      v-model="showModal"
      no-stacking
      no-body
      size="lg"
  >
    <template slot="modal-title">
      <slot name="title" />
    </template>
    <slot name="description" />
    <b-input-group class="mt-2">
      <b-input readonly size="lg" :value="copyUrl" ref="container" />
      <b-input-group-append>
        <b-button variant="white" class="butt" v-clipboard:copy="copyUrl" v-clipboard:success="copySucceed" v-clipboard:error="copyFail">
          <b-img :src="imageUrl('/images/copy.svg')" />
        </b-button>
      </b-input-group-append>
    </b-input-group>
    <b-alert v-if="failed" show variant="danger" class="mt-2 mb-2">
      <p class="m-0">
        {{ __('partials.something_wrong') }}
      </p>
    </b-alert>
    <b-alert v-if="copied" show variant="info" class="mt-2 mb-2">
      <p class="m-0">
        {{ __('partials.copied_to_clipboard') }}
      </p>
    </b-alert>
    <div class="d-flex justify-content-between flex-wrap mt-4 mb-4">
      <b-btn variant="link" href="https://talk.restarters.net/t/fixometer-how-to-add-repair-events-to-your-calendar-application/1770">
        {{ __('calendars.find_out_more')}}
      </b-btn>
      <b-btn variant="link" :href="editUrl">
        {{ __('calendars.see_all_calendars') }}
      </b-btn>
    </div>
    <template slot="modal-footer" slot-scope="{ cancel }">
      <b-button variant="primary" @click="cancel">
        {{ __('partials.close') }}
      </b-button>
    </template>
  </b-modal>
</template>
<script>
import Vue from 'vue'
import VueClipboard from 'vue-clipboard2'
import images from '../mixins/images'

VueClipboard.config.autoSetContainer = true
Vue.use(VueClipboard)

export default {
  mixins: [images],
  props: {
    copyUrl: {
      type: String,
      required: true
    },
    editUrl: {
      type: String,
      required: true
    },
  },
  data: function() {
    return {
      showModal: false,
      failed: false,
      copied: false
    }
  },
  methods: {
    show() {
      this.showModal = true
    },
    hide() {
      this.showModal = false
    },
    copySucceed() {
      this.copied = true
    },
    copyFail() {
      this.failed = true
    }
  }
}
</script>
<style scoped lang="scss">
.butt {
  border-radius: 0;
  border: 1px solid black;
  height: 45px;
}
</style>