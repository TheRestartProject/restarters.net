<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'
import { useSessionStore } from '~/stores/session.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'

// POST /api/v2/users/me/photo {upload_key}. Functional spec:
// resources/js/components/ProfilePhotoTab.vue +
// resources/views/user/profile/profile.blade.php. Always operates on
// Auth::user() - see stores/profile.js's class doc comment for why this
// tab is only ever shown while editing one's own profile. Reuses the
// shared TusImageUpload.vue (Uppy Dashboard + tus, folded in during B6)
// rather than re-implementing Uppy wiring here.
const { t } = useI18n()
const profileStore = useProfileStore()
const sessionStore = useSessionStore()
const { uploadedImageUrl } = useUploadedImageUrl()

const feedback = ref('')
const feedbackVariant = ref('success')

const photoUrl = computed(() => uploadedImageUrl(sessionStore.user?.avatar_url))

// user/profile/profile.blade.php:138-145 - a file input and an explicit
// CHANGE MY PHOTO submit, rather than uploading the moment a file is chosen.
// POST /api/v2/users/me/photo accepts multipart `photo` as well as a tus
// upload_key, and shares every validation between the two.
const selectedFile = ref(null)
const submitting = ref(false)

function onFileChange(event) {
  selectedFile.value = event.target.files?.[0] || null
}

async function submitPhoto() {
  if (!selectedFile.value) return

  submitting.value = true
  feedback.value = ''

  try {
    await profileStore.uploadPhotoFile(selectedFile.value)
    feedbackVariant.value = 'success'
    feedback.value = t('profile.picture_success')
    selectedFile.value = null
  } catch (err) {
    feedbackVariant.value = 'danger'
    feedback.value = err?.data?.message || t('profile.picture_error')
  } finally {
    submitting.value = false
  }
}

</script>

<template>
  <div class="edit-panel" data-testid="profile-photo-tab">
    <h4>{{ t('profile.change_photo') }}</h4>
    <!-- Legacy repeats the same lang string as a description paragraph
         underneath the heading (profile.blade.php: both the <h4> and the
         <p> use @lang('profile.change_photo')) - matched verbatim rather
         than "fixed" so the wording stays in sync if that key changes. -->
    <p>{{ t('profile.change_photo') }}</p>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="profile-photo-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <!-- profile.blade.php:145 labels the field "Profile picture:". The
         control itself differs and deliberately stays as-is for now: develop
         uses a plain multipart <input type="file"> plus a CHANGE MY PHOTO
         submit, whereas this is the shared TusImageUpload (resumable tus)
         used for every other image in the client - and develop itself uses a
         dropzone for group images, so the pattern is not foreign to it.
         Swapping transport would mean a different endpoint contract, so it is
         recorded in findings/parity-visual-findings.md rather than changed
         blind. -->
    <img v-if="photoUrl" :src="photoUrl" alt="" class="mb-3 profile-photo-preview">

    <form data-testid="profile-photo-form" @submit.prevent="submitPhoto">
      <label class="form-label fw-bold" for="profile-photo-input">{{ t('profile.profile_picture') }}:</label>
      <input
        id="profile-photo-input"
        type="file"
        class="form-control mb-3"
        accept="image/jpeg,image/png,image/gif"
        data-testid="profile-photo-input"
        @change="onFileChange"
      >
      <BButton
        type="submit"
        variant="primary"
        :disabled="!selectedFile || submitting"
        data-testid="profile-photo-submit"
      >
        {{ t('profile.change_photo') }}
      </BButton>
    </form>
  </div>
</template>
