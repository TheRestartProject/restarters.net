<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDevicesStore } from '../../stores/devices.js'
import { useUploadedImageUrl } from '../../composables/useUploadedImageUrl.js'
import TusImageUpload from '../forms/TusImageUpload.vue'

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
})

const { t } = useI18n()
const devicesStore = useDevicesStore()
const { uploadedImageUrl } = useUploadedImageUrl()

const uploadError = ref('')
const uploading = ref(false)
const deletingIdxref = ref(null)

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

async function removeImage(image) {
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
</script>

<template>
  <div data-testid="device-photos">
    <label class="fw-bold">{{ t('devices.images') }}</label>

    <div class="d-flex flex-wrap gap-2 mb-2">
      <div v-for="image in images" :key="image.idxref" class="position-relative" data-testid="device-photo">
        <img :src="uploadedImageUrl(image.path)" width="100" height="100" style="object-fit: cover" alt="">
        <button
          type="button"
          class="btn btn-sm btn-light position-absolute top-0 end-0 p-1 lh-1"
          :disabled="deletingIdxref === image.idxref || uploading"
          :aria-label="t('client.devices.remove_photo')"
          :data-testid="`device-photo-remove-${image.idxref}`"
          @click="removeImage(image)"
        >
          &times;
        </button>
      </div>
    </div>

    <TusImageUpload @uploaded="onUploaded" @upload-error="onUploadError" />

    <BAlert v-if="uploadError" :model-value="true" variant="danger" data-testid="device-photos-error">
      {{ uploadError }}
    </BAlert>
  </div>
</template>
