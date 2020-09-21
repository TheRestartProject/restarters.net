<template>
  <CollapsibleSection :count="images.length" collapsed>
    <template slot="title">
      {{ translatedEventPhotos }}
    </template>
    <template slot="content">
      <div class="d-flex flex-wrap">
        <b-img-lazy v-for="image in images" :key="'image-' + image.idimages" :src="'/uploads/thumbnail_' + image.path" thumbnail class="mr-2 mb-2 size d-inline" @error.native="brokenImage"/>
      </div>
    </template>
    TODO click on photo
  </CollapsibleSection>
</template>
<script>
import CollapsibleSection from './CollapsibleSection'
import { DEFAULT_PROFILE, PLACEHOLDER } from '../constants'
export default {
  components: {CollapsibleSection},
  props: {
    images: {
      type: Array,
      required: true
    }
  },
  computed: {
    translatedEventPhotos() {
      return this.$lang.get('events.event_photos')
    },
  },
  methods: {
    brokenImage(event) {
      event.target.src = PLACEHOLDER
      event.target.src = "https://restarters.dev/uploads/1539872775b72dc8a9a88008b9f92b152073c88ea320f0d1185575.png"
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