<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'
import CollapsibleSection from '../CollapsibleSection.vue'

// Ports EventImages.vue/EventImage.vue/EventImageModal.vue (event-photos
// gallery, gap 5/api-gaps.md - unblocked now GET /api/v2/events/{id}
// returns `images`, App\Http\Resources\Image: {id, idxref, path}) as one
// component rather than three, same convention as this file's siblings
// (e.g. EventShareStatsModal.vue). Read-only: develop's view page has no
// delete affordance for event photos at all - that lives on the *edit*
// page's separate upload dropzone (resources/views/events/edit.blade.php's
// "Remove file" links), which is a different page/feature, not this one.
defineProps({
  images: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()
const { uploadedImageUrl } = useUploadedImageUrl()

// EventImage.vue's thumbnail vs EventImageModal.vue's full-size src -
// Image.path is a bare filename (App\Http\Resources\Image), same shape
// useUploadedImageUrl.js already documents handling.
function thumbnailUrl(image) {
  return uploadedImageUrl(`thumbnail_${image.path}`)
}

function fullUrl(image) {
  return uploadedImageUrl(image.path)
}

const zoomedImage = ref(null)

function zoom(image) {
  zoomedImage.value = image
}

function closeZoom() {
  zoomedImage.value = null
}
</script>

<template>
  <CollapsibleSection v-if="images.length" collapsed-on-mobile data-testid="event-images-gallery">
    <template #title>
      <h2 class="mb-0">
        {{ t('events.event_photos') }}
        <span class="fw-normal">({{ images.length }})</span>
      </h2>
    </template>

    <div class="d-flex flex-wrap">
      <img
        v-for="image in images"
        :key="image.id"
        :src="thumbnailUrl(image)"
        alt=""
        class="event-image-thumb me-2 mb-2"
        data-testid="event-image-thumb"
        role="button"
        tabindex="0"
        @click="zoom(image)"
        @keydown.enter.prevent="zoom(image)"
        @keydown.space.prevent="zoom(image)"
      >
    </div>

    <BModal
      :model-value="!!zoomedImage"
      data-testid="event-image-modal"
      @hide="closeZoom"
    >
      <img v-if="zoomedImage" :src="fullUrl(zoomedImage)" alt="" class="w-100">
      <template #footer>
        <BButton variant="primary" data-testid="event-image-modal-close" @click="closeZoom">
          {{ t('partials.close') }}
        </BButton>
      </template>
    </BModal>
  </CollapsibleSection>
</template>

<style scoped>
.event-image-thumb {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border: 1px solid #222;
  cursor: pointer;
}
</style>
