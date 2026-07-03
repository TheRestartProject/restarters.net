<template>
  <div class="edit-panel">
    <h4>{{ __('profile.change_photo') }}</h4>
    <p>{{ __('profile.change_photo') }}</p>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <b-form @submit.prevent="save">
      <div class="form-row">
        <div class="form-group col-lg-12">
          <label for="profilePhoto">{{ __('profile.profile_picture') }}:</label>
          <input
              id="profilePhoto"
              ref="fileInput"
              type="file"
              class="form-control"
              accept="image/jpeg,image/png,image/gif,image/webp"
              data-testid="photo-file-input"
              @change="onFileChange"
          >
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-lg-4">
          <img
              v-if="previewUrl"
              width="50"
              :src="previewUrl"
              :alt="__('profile.profile_picture')"
          >
        </div>
        <div class="form-group col-lg-8">
          <div class="d-flex justify-content-end">
            <b-btn
                type="submit"
                variant="primary"
                :disabled="saving || !selectedFile"
                data-testid="photo-save"
            >
              {{ __('profile.change_photo') }}
            </b-btn>
          </div>
        </div>
      </div>
    </b-form>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ProfilePhotoTab',
  props: {
    currentPhotoUrl: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      selectedFile: null,
      previewUrl: this.currentPhotoUrl,
      saving: false,
      feedback: null,
      feedbackVariant: 'success',
    }
  },
  methods: {
    onFileChange(event) {
      const file = event.target.files && event.target.files[0]
      this.selectedFile = file || null

      if (this.selectedFile) {
        this.previewUrl = URL.createObjectURL(this.selectedFile)
      } else {
        this.previewUrl = this.currentPhotoUrl
      }
    },
    async save() {
      if (!this.selectedFile) {
        return
      }

      this.saving = true
      this.feedback = null

      const formData = new FormData()
      formData.append('profilePhoto', this.selectedFile)

      try {
        await axios.post('/api/v2/users/me/photo', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        this.feedback = this.__('profile.picture_success')
        this.feedbackVariant = 'success'
        this.selectedFile = null
        if (this.$refs.fileInput) {
          this.$refs.fileInput.value = ''
        }
      } catch (e) {
        const message = e.response && e.response.data && e.response.data.message
        this.feedback = message || this.__('profile.picture_error')
        this.feedbackVariant = 'danger'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
