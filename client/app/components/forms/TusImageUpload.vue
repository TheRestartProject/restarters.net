<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Reusable Uppy Core + tus resumable-upload widget. Mirrors the pattern
// folded in from PR #868's resources/js/components/ProfilePhotoTab.vue
// (Uppy Core + Dashboard + Tus + Compressor against the shared,
// unauthenticated `/api/tus` endpoint - see TusController/routes/api.php
// for why it's deliberately not behind auth:sanctum). This component only
// drives the upload and reports the resulting tus upload key; it does not
// know or care which "attach" endpoint the caller will subsequently call
// (POST /api/v2/users/me/photo, POST /api/v2/groups/{id}/images, ...) -
// design.md §6.2 B6 task brief: "study how PR #868's profile photo used
// @uppy/tus ... mirror that ... (reusable component)".
//
// `compact` (findings/parity-v2/group-forms.md #13, GroupForm.vue's only
// consumer) swaps the full-size Uppy Dashboard panel for a 100x100
// click-or-drag thumbnail, matching legacy GroupImage.vue's vue-dropzone
// exactly (see that file's own comment for the source markup/CSS this
// ports). It's a SEPARATE render path, not a CSS restyle of the Dashboard
// plugin, because Uppy's Dashboard chrome (drop-here text, browse-files
// link, powered-by line) doesn't have anywhere to go at that size - the
// compact path skips loading @uppy/dashboard entirely and drives Uppy Core
// directly instead. Every other consumer (devices/profile/networks/party)
// doesn't pass `compact` and renders byte-identically to before.
const props = defineProps({
  currentImageUrl: {
    type: String,
    default: null,
  },
  compact: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['uploaded', 'upload-error'])

const { t } = useI18n()

const dashboardEl = ref(null)
const fileInputEl = ref(null)
// compact mode only: an instant local preview of the just-picked file
// (createObjectURL), shown in place of currentImageUrl the moment a file is
// chosen - legacy's vue-dropzone shows its own createImageThumbnails
// preview the same way, rather than waiting for the upload to finish.
const localPreviewUrl = ref(null)
const uploading = ref(false)
let uppy = null

onMounted(async () => {
  const runtimeConfig = useRuntimeConfig()
  const tusEndpoint = `${runtimeConfig.public.apiBase}/api/tus`

  const [{ default: Uppy }, { default: Tus }, { default: Compressor }] = await Promise.all([
    import('@uppy/core'),
    import('@uppy/tus'),
    import('@uppy/compressor'),
  ])

  uppy = new Uppy({
    autoProceed: true,
    restrictions: {
      maxNumberOfFiles: 1,
      allowedFileTypes: ['image/*', '.jpg', '.jpeg', '.png', '.gif'],
    },
  })
    .use(Tus, { endpoint: tusEndpoint })
    .use(Compressor)

  if (props.compact) {
    uppy.on('upload', () => {
      uploading.value = true
    })
  } else {
    const { default: Dashboard } = await import('@uppy/dashboard')
    uppy.use(Dashboard, {
      target: dashboardEl.value,
      inline: true,
      proudlyDisplayPoweredByUppy: false,
      showProgressDetails: true,
      height: 200,
    })
  }

  uppy.on('complete', onComplete)
  uppy.on('upload-error', onUploadError)
})

onBeforeUnmount(() => {
  uppy?.destroy()
  uppy = null
})

function onComplete(result) {
  uploading.value = false

  const successful = result.successful && result.successful[0]
  if (!successful) return

  // Same convention as ProfilePhotoTab.vue / Freegle's OurUploader.vue: the
  // tus upload key is the last path segment of the tus upload URL.
  const uploadUrl = successful.tus && successful.tus.uploadUrl
  const uploadKey = uploadUrl ? uploadUrl.substring(uploadUrl.lastIndexOf('/') + 1) : null

  if (!uploadKey) {
    emit('upload-error', t('client.groups.image_upload_error'))
    return
  }

  emit('uploaded', { uploadKey, file: successful })
  uppy?.clear()
}

function onUploadError(file, error) {
  uploading.value = false
  emit('upload-error', error?.message || t('client.groups.image_upload_error'))
}

function openFilePicker() {
  fileInputEl.value?.click()
}

function addCompactFile(file) {
  if (!file) return

  localPreviewUrl.value = URL.createObjectURL(file)

  try {
    uppy.addFile({ name: file.name, type: file.type, data: file })
  } catch (err) {
    emit('upload-error', err?.message || t('client.groups.image_upload_error'))
  }
}

function onFileInputChange(event) {
  addCompactFile(event.target.files && event.target.files[0])
  event.target.value = ''
}

function onDrop(event) {
  addCompactFile(event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0])
}
</script>

<template>
  <div v-if="compact" class="tus-image-upload tus-image-upload--compact" data-testid="tus-image-upload">
    <div
      class="tus-image-upload__thumb"
      role="button"
      tabindex="0"
      data-testid="tus-image-upload-dropzone"
      @click="openFilePicker"
      @keydown.enter="openFilePicker"
      @dragover.prevent
      @drop.prevent="onDrop"
    >
      <img
        :src="localPreviewUrl || currentImageUrl || '/images/upload_ico_grey.svg'"
        alt=""
        class="tus-image-upload__thumb-image"
        data-testid="tus-image-upload-preview"
      >
      <span v-if="uploading" class="spinner-border spinner-border-sm tus-image-upload__thumb-spinner" role="status" aria-hidden="true" />
    </div>
    <input
      ref="fileInputEl"
      type="file"
      accept="image/*"
      class="visually-hidden"
      data-testid="tus-image-upload-file-input"
      @change="onFileInputChange"
    >
  </div>
  <div v-else class="tus-image-upload" data-testid="tus-image-upload">
    <img v-if="currentImageUrl" :src="currentImageUrl" alt="" class="tus-image-upload__preview" data-testid="tus-image-upload-preview">
    <div ref="dashboardEl" data-testid="tus-image-upload-dashboard" />
  </div>
</template>

<style scoped lang="scss">
.tus-image-upload__preview {
  width: 100px;
  height: 100px;
  object-fit: cover;
  margin-bottom: 0.5rem;
}

.tus-image-upload__thumb {
  position: relative;
  width: 100px;
  height: 100px;
  cursor: pointer;
}

.tus-image-upload__thumb-image {
  width: 100px;
  height: 100px;
  object-fit: cover;
}

.tus-image-upload__thumb-spinner {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
</style>
