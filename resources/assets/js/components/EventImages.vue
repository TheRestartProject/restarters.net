<template>
  <CollapsibleSection :count="images.length" collapsed>
    <template slot="title">
      {{ __('events.event_photos') }}
    </template>
    <template slot="content">
      <div class="d-flex flex-wrap">
        <EventImage v-for="image in images" :key="'image-' + image.idimages" :image="image"/>
      </div>
    </template>
  </CollapsibleSection>
</template>
<script>
import CollapsibleSection from './CollapsibleSection'
import { DEFAULT_PROFILE, PLACEHOLDER } from '../constants'
import EventImage from './EventImage'
export default {
  components: {EventImage, CollapsibleSection},
  props: {
    images: {
      type: Array,
      required: true
    }
  },
}
</script>
