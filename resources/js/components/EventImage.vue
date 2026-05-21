<template>
  <div>
    <b-img-lazy :src="'/uploads/thumbnail_' + image.path" thumbnail class="mr-2 mb-2 size d-inline clickme" @error.native="brokenImage" @click.native="zoom" />
    <EventImageModal :image="image" ref="modal" />
  </div>
</template>
<script>
import CollapsibleSection from './CollapsibleSection.vue'
import { PLACEHOLDER } from '../constants'
import EventImageModal from './EventImageModal.vue'

export default {
  components: {EventImageModal},
  props: {
    image: {
      type: Object,
      required: true
    }
  },
  methods: {
    brokenImage(event) {
      event.target.src = PLACEHOLDER
    },
    zoom() {
      this.$refs.modal.show()
    }
  }
}
</script>
<style scoped>
.size {
  width: 100px;
  height: 100px;
  object-fit: cover;
}
</style>