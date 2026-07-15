<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Reusable Uppy Dashboard + tus resumable-upload widget. Mirrors the
// pattern folded in from PR #868's resources/js/components/
// ProfilePhotoTab.vue (Uppy Core + Dashboard + Tus + Compressor against the
// shared, unauthenticated `/api/tus` endpoint - see TusController/routes/
// api.php for why it's deliberately not behind auth:sanctum). This
// component only drives the upload and reports the resulting tus upload
// key; it does not know or care which "attach" endpoint the caller will
// subsequently call (POST /api/v2/users/me/photo, POST
// /api/v2/groups/{id}/images, ...) - design.md §6.2 B6 task brief: "study
// how PR #868's profile photo used @uppy/tus ... mirror that ... (reusable
// component)".
defineProps({
  currentImageUrl: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['uploaded', 'upload-error'])

const { t } = useI18n()

const dashboardEl = ref(null)
let uppy = null

onMounted(async () => {
  const runtimeConfig = useRuntimeConfig()
  const tusEndpoint = `${runtimeConfig.public.apiBase}/api/tus`

  const [{ default: Uppy }, { default: Dashboard }, { default: Tus }, { default: Compressor }] = await Promise.all([
    import('@uppy/core'),
    import('@uppy/dashboard'),
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
    .use(Dashboard, {
      target: dashboardEl.value,
      inline: true,
      proudlyDisplayPoweredByUppy: false,
      showProgressDetails: true,
      height: 200,
    })
    .use(Tus, { endpoint: tusEndpoint })
    .use(Compressor)

  uppy.on('complete', onComplete)
  uppy.on('upload-error', onUploadError)
})

onBeforeUnmount(() => {
  uppy?.destroy()
  uppy = null
})

function onComplete(result) {
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
  emit('upload-error', error?.message || t('client.groups.image_upload_error'))
}
</script>

<template>
  <div class="tus-image-upload" data-testid="tus-image-upload">
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
</style>
