<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'
import { useSessionStore } from '~/stores/session.js'
import { useUploadedImageUrl } from '~/composables/useUploadedImageUrl.js'
import TusImageUpload from '~/components/forms/TusImageUpload.vue'

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

async function onUploaded({ uploadKey }) {
  feedback.value = ''

  try {
    await profileStore.uploadPhoto(uploadKey)
    feedback.value = t('profile.picture_success')
    feedbackVariant.value = 'success'
  } catch (err) {
    feedback.value = err?.data?.message || t('profile.picture_error')
    feedbackVariant.value = 'danger'
  }
}

function onUploadError(message) {
  feedback.value = message || t('profile.picture_error')
  feedbackVariant.value = 'danger'
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

    <TusImageUpload :current-image-url="photoUrl" @uploaded="onUploaded" @upload-error="onUploadError" />
  </div>
</template>
