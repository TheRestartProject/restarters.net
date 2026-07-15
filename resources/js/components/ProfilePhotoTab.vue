<template>
  <div class="edit-panel">
    <h4>{{ __('profile.change_photo') }}</h4>
    <p>{{ __('profile.change_photo') }}</p>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <div class="form-row">
      <div class="form-group col-lg-4">
        <img
            v-if="photoUrl"
            width="50"
            :src="photoUrl"
            :alt="__('profile.profile_picture')"
        >
      </div>
    </div>

    <div class="form-row">
      <div class="form-group col-lg-12">
        <div ref="uppyDashboard" data-testid="photo-uppy-dashboard" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Uppy from '@uppy/core'
import Dashboard from '@uppy/dashboard'
import Tus from '@uppy/tus'
import Compressor from '@uppy/compressor'

import '@uppy/core/dist/style.css'
import '@uppy/dashboard/dist/style.css'

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
      photoUrl: this.currentPhotoUrl,
      saving: false,
      feedback: null,
      feedbackVariant: 'success',
    }
  },
  mounted() {
    const tusEndpoint = (window.restarters && window.restarters.tusEndpoint) || '/api/tus'

    this.uppy = new Uppy({
      autoProceed: true,
      restrictions: {
        maxNumberOfFiles: 1,
        allowedFileTypes: ['image/*', '.jpg', '.jpeg', '.png', '.gif'],
      },
    })
      .use(Dashboard, {
        target: this.$refs.uppyDashboard,
        inline: true,
        proudlyDisplayPoweredByUppy: false,
        showProgressDetails: true,
        height: 200,
      })
      .use(Tus, { endpoint: tusEndpoint })
      .use(Compressor)

    this.uppy.on('complete', this.onUploadComplete)
    this.uppy.on('upload-error', this.onUploadError)
  },
  beforeDestroy() {
    if (this.uppy) {
      this.uppy.destroy()
    }
  },
  methods: {
    async onUploadComplete(result) {
      const successful = result.successful && result.successful[0]

      if (!successful) {
        return
      }

      // Derive the tus upload key from the last path segment of the upload URL,
      // the same convention used by Freegle's OurUploader.vue (r.tus.uploadUrl).
      const uploadUrl = successful.tus && successful.tus.uploadUrl
      const uploadKey = uploadUrl ? uploadUrl.substring(uploadUrl.lastIndexOf('/') + 1) : null

      if (!uploadKey) {
        this.feedback = this.__('profile.picture_error')
        this.feedbackVariant = 'danger'
        return
      }

      this.saving = true
      this.feedback = null

      try {
        const response = await axios.post('/api/v2/users/me/photo', { upload_key: uploadKey })
        this.photoUrl = response.data && response.data.data && response.data.data.path
          ? '/uploads/thumbnail_' + response.data.data.path
          : this.photoUrl
        this.feedback = this.__('profile.picture_success')
        this.feedbackVariant = 'success'
      } catch (e) {
        const message = e.response && e.response.data && e.response.data.message
        this.feedback = message || this.__('profile.picture_error')
        this.feedbackVariant = 'danger'
      } finally {
        this.saving = false
        this.uppy.clear()
      }
    },
    onUploadError(file, error) {
      this.feedback = (error && error.message) || this.__('profile.picture_error')
      this.feedbackVariant = 'danger'
    },
  },
}
</script>
