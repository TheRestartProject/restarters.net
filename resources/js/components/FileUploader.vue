<template>
  <div>
    <vue-dropzone ref="dropzone" id="dropzone" :options="dropzoneOptions" @vdropzone-sending="sendingEvent" class="ourdropzone" useCustomSlot @vdropzone-success-multiple="success">
      <b-img src="/images/upload_ico_grey.svg" />
      <div class="dz-message d-none" />
    </vue-dropzone>
  </div>
</template>
<script>
import vue2Dropzone from 'vue2-dropzone'
// import 'vue2-dropzone/dist/vue2Dropzone.min.css'

export default {
  props: {
    url: {
      type: String,
      required: true
    },
    previewsContainer: {
      type: String,
      required: true
    },
    maxFiles: {
      type: Number,
      required: false,
      default: 1
    },
  },
  components: {
    vueDropzone: vue2Dropzone
  },
  computed: {
    dropzoneOptions () {
      return {
        url: this.url,
        paramName: 'file',
        uploadMultiple: true,
        createImageThumbnails: true,
        parallelUploads: 100,
        addRemoveLinks: false,
        thumbnailWidth: 120,
        thumbnailHeight: 120,
        maxFiles: this.maxFiles,
        resizeWidth: 800,
        resizeHeight: 800,
        thumbnailMethod: 'contain',
        previewsContainer: this.previewsContainer,
        dictRemoveFile: null,
        acceptedFiles: ".jpeg,.jpg,.png,.gif",
        previewTemplate:
            '<div>' +
            ' <div class="dz-preview dz-file-preview">' +
            '   <div class="dz-image"><img data-dz-thumbnail /></div>' +
            '   <div class="dz-progress">' +
            '     <span data-dz-uploadprogress="" class="dz-upload"></span>' +
            '   </div> ' +
            '   <div class="dz-error-message">' +
            '   <span data-dz-errormessage=""></span>' +
            ' </div> ' +
            '</div>'
      }
    }
  },
  methods: {
    sendingEvent (file, xhr, formData) {
      // Add the CSRF.
      formData.append('_token', this.$store.getters['auth/CSRF']);
    },
    success (files, response) {
      // We have uploaded some files.  Tell the parent; they will add them to the relevant place, and re-render
      // views.
      console.log("Uploaded", files, response)
      this.$emit('uploaded', response.images)

      // Remove the preview - the parent is now responsible for that.
      files.forEach(f => {
        this.$refs.dropzone.removeFile(f);
      })
    }
  }
}
</script>
<style lang="scss">
// Note that this style is explicitly not scoped so that it can override dropzone styles.
@import 'resources/global/css/_variables';

.ourdropzone {
  padding: 0;
  align-content: start;
  background-color: transparent !important;
  border: 0 !important;
  padding-left: 2px;
  min-height: unset;

  .dz-message {
    margin: 0 !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
  }

  img {
    width: 100px !important;
  }
}

.dz-preview {
  position: relative;
  margin-right: 0.5rem;
}

.dz-remove {
  position: absolute;
  top: -10px;
  right: -8px;
  font-size: 13px;
  z-index: 2;
  font-weight: 600;
  color: $brand;
  text-decoration: underline;
  cursor: pointer;

  &:hover {
    text-decoration: none;
  }

  &:before {
    content:"╳";
    position: relative;
    background-color: white;
    border-radius: 50%;
    padding: 3px;
  }
}
</style>