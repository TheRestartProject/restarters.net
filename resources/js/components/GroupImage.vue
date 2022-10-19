<template>
  <b-form-group>
    <label for="group_phone">{{ __('groups.group_image') }}:</label>
    <div class="layout">
      <vue-dropzone ref="dropzone" id="dropzone" :options="dropzoneOptions"
                    class="ourdropzone" useCustomSlot
                    @vdropzone-file-added="fileAdded">
        <b-img src="/images/upload_ico_grey.svg"/>
        <div class="dz-message d-none"/>
      </vue-dropzone>
      <b-btn variant="none" @click="deleteMe" class="deleteme" v-if="currentimage">
        <b-img src="/icons/cross_ico.svg" class="icon"/>
      </b-btn>
    </div>
  </b-form-group>
</template>
<script>
import vue2Dropzone from 'vue2-dropzone'

export default {
  data () {
    return {
      currentimage: null,
    }
  },
  components: {
    vueDropzone: vue2Dropzone
  },
  computed: {
    dropzoneOptions () {
      return {
        url: 'thisisrequired',
        paramName: 'file',
        uploadMultiple: false,
        maxFiles: 1,
        createImageThumbnails: true,
        resizeWidth: 800,
        resizeHeight: 800,
        thumbnailMethod: 'contain',
        previewsContainer: this.previewsContainer,
        dictRemoveFile: null,
        acceptedFiles: '.jpeg,.jpg,.png,.gif',
        manuallyAddFile: true,
        autoProcessQueue: false,
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
    fileAdded (file) {
      console.log("Got file", file)
      this.currentimage = file
      this.$emit('update:image', this.currentimage)
    },
    deleteMe () {
      this.$refs.dropzone.removeAllFiles()
      this.currentimage = null
    }
  }
}
</script>
<style scoped lang="scss">
/deep/ .dz-progress {
  display: none !important;
}

/deep/ .dz-image {
  border-radius: unset !important;
}

/deep/ .dz-image-preview {
  display: flex;
  justify-content: center;
}

.layout {
  display: grid;
}

.ourdropzone {
  grid-row: 1 / 2;
  grid-column: 1 / 2;
}

.deleteme {
  grid-row: 1 / 2;
  grid-column: 1 / 2;
  z-index: 10000;
  border-radius: 20px;
  width: 40px;
  min-width: 40px;
  min-height: 40px;
  margin-top: 3px;
  margin-right: 10px;
  max-height: 40px;
  align-self: start;
  justify-self: end;
}

</style>