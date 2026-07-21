<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDevicesStore } from '../../stores/devices.js'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'
import TusImageUpload from '../forms/TusImageUpload.vue'

// develop's DeviceImages.vue: maxFiles = 5, the upload widget is hidden
// once that many images already exist for this device.
const MAX_IMAGES = 5

// tus photo upload + display for one device (api-contracts-phase-c.md C1f/
// C5). Functional spec: resources/js/components/DeviceImages.vue +
// DeviceImage.vue. Edit-only (requires an existing device id - see
// DeviceForm.vue's doc comment and docs/nuxt-migration/api-gaps.md Phase C
// for why photo upload isn't available while adding a new device, mirroring
// C4's event photo tab being edit-only for the same reason).
const props = defineProps({
  eventId: {
    type: Number,
    required: true,
  },
  deviceId: {
    type: Number,
    required: true,
  },
  images: {
    type: Array,
    default: () => [],
  },
  // EventDevice.vue passes its own `disabled` straight through to
  // DeviceImages' `disabled` prop - view-only rendering hides the remove
  // button and the upload widget, leaving just the existing photos.
  readonly: {
    type: Boolean,
    default: false,
  },
})

const { t } = useI18n()
const devicesStore = useDevicesStore()
const { uploadedImageUrl } = useUploadedImageUrl()

const uploadError = ref('')
const uploading = ref(false)
const deletingIdxref = ref(null)

const atLimit = computed(() => props.images.length >= MAX_IMAGES)

async function onUploaded({ uploadKey }) {
  uploadError.value = ''
  uploading.value = true

  try {
    await devicesStore.uploadDeviceImage(props.eventId, props.deviceId, uploadKey)
  } catch {
    uploadError.value = t('client.devices.image_upload_error')
  } finally {
    uploading.value = false
  }
}

function onUploadError(message) {
  uploadError.value = message || t('client.devices.image_upload_error')
}

// Gap fix (dropped-behaviour finding): DeviceImage.vue gates its remove
// button behind a bare `<ConfirmModal @confirm="remove" ref="confirm"/>`
// (no title/message passed, so it falls back to ConfirmModal's own
// defaults, `partials.are_you_sure`/`partials.please_confirm`) rather than
// deleting on click. `confirmingImage` holds the image pending confirmation
// (null when the modal is closed).
const confirmingImage = ref(null)

function askRemoveImage(image) {
  confirmingImage.value = image
}

function cancelRemoveImage() {
  confirmingImage.value = null
}

async function confirmRemoveImage() {
  const image = confirmingImage.value
  confirmingImage.value = null
  if (!image) return

  deletingIdxref.value = image.idxref
  uploadError.value = ''

  try {
    await devicesStore.deleteDeviceImage(props.eventId, props.deviceId, image.idxref)
  } catch {
    uploadError.value = t('client.devices.image_delete_error')
  } finally {
    deletingIdxref.value = null
  }
}

// Gap fix (dropped-behaviour finding): DeviceImage.vue's thumbnail opens
// DeviceImageModal (full-size, same '/uploads/' + path src as the
// thumbnail - there's no separate thumbnail-sized asset for device photos)
// on click - mirrors EventImagesGallery.vue's own zoom/BModal pattern.
const zoomedImage = ref(null)

function zoom(image) {
  zoomedImage.value = image
}

function closeZoom() {
  zoomedImage.value = null
}
</script>

<template>
  <div data-testid="device-photos">
    <label class="fw-bold">{{ t('devices.images') }}</label>

    <div class="d-flex flex-wrap gap-2 mb-2">
      <div v-for="image in images" :key="image.idxref" class="position-relative" data-testid="device-photo">
        <img
          :src="uploadedImageUrl(image.path)"
          width="100"
          height="100"
          style="object-fit: cover; cursor: pointer"
          alt=""
          role="button"
          data-testid="device-photo-thumb"
          @click="zoom(image)"
        >
        <button
          v-if="!readonly"
          type="button"
          class="btn btn-sm btn-light position-absolute top-0 end-0 p-1 lh-1"
          :disabled="deletingIdxref === image.idxref || uploading"
          :aria-label="t('client.devices.remove_photo')"
          :data-testid="`device-photo-remove-${image.idxref}`"
          @click="askRemoveImage(image)"
        >
          &times;
        </button>
      </div>
    </div>

    <TusImageUpload v-if="!readonly && !atLimit" @uploaded="onUploaded" @upload-error="onUploadError" />

    <BAlert v-if="uploadError" :model-value="true" variant="danger" data-testid="device-photos-error">
      {{ uploadError }}
    </BAlert>

    <BModal
      :model-value="!!confirmingImage"
      :title="t('partials.are_you_sure')"
      no-footer
      data-testid="device-photo-delete-modal"
      @hide="cancelRemoveImage"
    >
      <p>{{ t('partials.please_confirm') }}</p>
      <div class="d-flex justify-content-end gap-2">
        <BButton variant="white" data-testid="device-photo-delete-cancel" @click="cancelRemoveImage">
          {{ t('partials.cancel') }}
        </BButton>
        <BButton variant="primary" data-testid="device-photo-delete-confirm" @click="confirmRemoveImage">
          {{ t('partials.confirm') }}
        </BButton>
      </div>
    </BModal>

    <BModal
      :model-value="!!zoomedImage"
      data-testid="device-photo-zoom-modal"
      @hide="closeZoom"
    >
      <img v-if="zoomedImage" :src="uploadedImageUrl(zoomedImage.path)" alt="" class="w-100">
      <template #footer>
        <BButton variant="primary" data-testid="device-photo-zoom-close" @click="closeZoom">
          {{ t('partials.close') }}
        </BButton>
      </template>
    </BModal>
  </div>
</template>
