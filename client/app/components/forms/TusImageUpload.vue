<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
// Uppy's Dashboard needs its own stylesheet: without it the drag-over hint
// ("Drop your files here"), which should only appear while dragging, is always
// on screen above the real "Drop files here or browse files". The rules are
// all under .uppy-* classes, so loading them for the compact path is harmless.
import '@uppy/core/dist/style.min.css'
import '@uppy/dashboard/dist/style.min.css'

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
  // `dropzone`: the event edit page's Photos tab. Legacy events/edit.blade.php
  // used a jQuery Dropzone there (app.js loadDropzones, styled by
  // resources/sass/_dropzone.scss): a dashed grey box with a camera icon and
  // two lines of text, click or drop to add, several files at once. Like
  // `compact` it drives Uppy Core directly rather than the Dashboard panel.
  dropzone: {
    type: Boolean,
    default: false,
  },
  // dropzone mode's two lines of text (data-field1/data-field2 in legacy).
  message: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  // dropzone mode: id for the file input, so the caller's <label for> works.
  inputId: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['uploaded', 'upload-error', 'remove'])

const { t } = useI18n()

// PR previews read photos from the live bucket and can't write to it, so the
// API turns every upload away there. Say so, rather than let someone pick a
// photo and get "Sorry, we couldn't upload that photo".
const uploadsOn = useRuntimeConfig().public.imageUpload !== false

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
  if (!uploadsOn) return

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
      // Legacy's event dropzone took many files at once (uploadMultiple).
      maxNumberOfFiles: props.dropzone ? null : 1,
      allowedFileTypes: ['image/*', '.jpg', '.jpeg', '.png', '.gif'],
    },
  })
    .use(Tus, { endpoint: tusEndpoint })
    .use(Compressor)

  if (props.compact || props.dropzone) {
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
      // Uppy's Dashboard defaults to a fixed 750px. In a narrower column -
      // the profile page's "Change my photo" card is one - that pushed the
      // panel past the right edge of the viewport and gave the whole page a
      // horizontal scrollbar (profile/edit measured 1657px wide at a 1440
      // viewport). Track the container instead.
      width: '100%',
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

  // Single-file modes only ever report the first; dropzone mode reports each.
  const files = (result.successful || []).slice(0, props.dropzone ? undefined : 1)
  if (!files.length) return

  for (const successful of files) {
    // Same convention as ProfilePhotoTab.vue / Freegle's OurUploader.vue: the
    // tus upload key is the last path segment of the tus upload URL.
    const uploadUrl = successful.tus && successful.tus.uploadUrl
    const uploadKey = uploadUrl ? uploadUrl.substring(uploadUrl.lastIndexOf('/') + 1) : null

    if (!uploadKey) {
      emit('upload-error', t('client.groups.image_upload_error'))
      continue
    }

    emit('uploaded', { uploadKey, file: successful })
  }

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

function addDropzoneFiles(fileList) {
  for (const file of Array.from(fileList || [])) {
    try {
      uppy.addFile({ name: file.name, type: file.type, data: file })
    } catch (err) {
      emit('upload-error', err?.message || t('client.groups.image_upload_error'))
    }
  }
}

function onDropzoneInputChange(event) {
  addDropzoneFiles(event.target.files)
  event.target.value = ''
}

function onDropzoneDrop(event) {
  addDropzoneFiles(event.dataTransfer && event.dataTransfer.files)
}

function onDrop(event) {
  addCompactFile(event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0])
}

// Legacy GroupImage.vue's deleteMe(): clears the just-picked file
// (dropzone.removeAllFiles() + currentimage = null there) and only ever
// shows once a file has actually been picked - v-if="currentimage" there,
// v-if="localPreviewUrl" here. Emits so the caller (GroupForm.vue) can
// reset its own state back to the placeholder image.
function removeCompactFile(event) {
  event.stopPropagation()

  if (localPreviewUrl.value) {
    URL.revokeObjectURL(localPreviewUrl.value)
    localPreviewUrl.value = null
  }

  uppy?.getFiles().forEach((file) => uppy.removeFile(file.id))

  if (fileInputEl.value) {
    fileInputEl.value.value = ''
  }

  emit('remove')
}
</script>

<template>
  <p v-if="!uploadsOn" class="tus-image-upload tus-image-upload--off text-muted" data-testid="tus-image-upload-off">
    {{ t('client.uploads.off') }}
  </p>
  <div v-else-if="compact" class="tus-image-upload tus-image-upload--compact" data-testid="tus-image-upload">
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
      <button
        v-if="localPreviewUrl"
        type="button"
        class="tus-image-upload__remove"
        :aria-label="t('partials.remove')"
        data-testid="tus-image-upload-remove"
        @click="removeCompactFile"
      >
        <img src="/icons/cross_ico.svg" alt="">
      </button>
    </div>
    <input
      ref="fileInputEl"
      type="file"
      :aria-label="t('groups.group_image')"
      accept="image/*"
      class="visually-hidden"
      data-testid="tus-image-upload-file-input"
      @change="onFileInputChange"
    >
  </div>
  <div v-else-if="dropzone" class="tus-image-upload tus-image-upload--dropzone" data-testid="tus-image-upload">
    <div
      class="dropzone dz-clickable"
      role="button"
      tabindex="0"
      data-testid="tus-image-upload-dropzone"
      @click="openFilePicker"
      @keydown.enter="openFilePicker"
      @dragover.prevent
      @drop.prevent="onDropzoneDrop"
    >
      <div class="dz-message">
        <span>{{ message }}</span><small>{{ hint }}</small>
      </div>
      <span v-if="uploading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
    </div>
    <input
      :id="inputId"
      ref="fileInputEl"
      type="file"
      multiple
      :aria-label="message"
      accept="image/*"
      class="visually-hidden"
      data-testid="tus-image-upload-file-input"
      @change="onDropzoneInputChange"
    >
  </div>
  <div v-else class="tus-image-upload" data-testid="tus-image-upload">
    <img v-if="currentImageUrl" :src="currentImageUrl" alt="" class="tus-image-upload__preview" data-testid="tus-image-upload-preview">
    <div ref="dashboardEl" data-testid="tus-image-upload-dashboard" />
  </div>
</template>

<style scoped lang="scss">
// Dropzone mode: what legacy actually rendered. resources/sass/_dropzone.scss
// wraps its dashed-border/camera-icon rules in `:not .leave-dropzone-alone`,
// which compiles to an invalid selector, so browsers drop them and live shows
// dropzone.css's own defaults: white box, 2px solid grey border, centred text.
.tus-image-upload--dropzone .dropzone {
  position: relative;
  box-sizing: border-box;
  min-height: 150px;
  padding: 20px;
  border: 2px solid rgba(0, 0, 0, 0.3);
  background: #fff;
  cursor: pointer;

  .dz-message {
    margin: 2em 0;
    text-align: center;

    // The hint on its own line under the message. Live's _dropzone.scss means
    // to do this, but its rules never apply (see above), so live runs the two
    // together: "Add event images hereChoose an image for your event".
    small {
      display: block;
      padding-top: 15px;
      font-size: 13px;
      color: #5a5a5a;
    }
  }

  .spinner-border {
    position: absolute;
    top: 10px;
    right: 10px;
  }
}

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

// GroupImage.vue's .deleteme: same size/position/z-index, translated from
// its CSS-grid overlay to position:absolute since this thumb isn't a grid.
.tus-image-upload__remove {
  position: absolute;
  top: 3px;
  right: 10px;
  z-index: 10000;
  width: 40px;
  min-width: 40px;
  height: 40px;
  min-height: 40px;
  max-height: 40px;
  border: 0;
  border-radius: 20px;
  background: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
