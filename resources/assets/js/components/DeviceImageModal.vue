<template>
  <b-modal
      id="imagemodal"
      v-model="showModal"
      no-stacking
  >
    <template slot="default">
      <b-img-lazy class="w-100" :src="'/uploads/' + image.path" @error.native="brokenImage" />
    </template>
    <template slot="modal-footer" slot-scope="{ cancel }">
      <b-button variant="primary" @click="cancel">
        {{ __('partials.close') }}
      </b-button>
    </template>
  </b-modal>
</template>
<script>
import { PLACEHOLDER } from '../constants'

export default {
  props: {
    image: {
      type: Object,
      required: true
    },
  },
  data: function() {
    return {
      showModal: false
    }
  },
  methods: {
    show() {
      this.showModal = true
    },
    hide() {
      this.showModal = false
    },
    brokenImage(event) {
      event.target.src = PLACEHOLDER
    }
  }
}
</script>
