<template>
  <div>
    <div class="device-photo-layout" v-if="!add">
      <label v-if="!add">
        {{ __('devices.images') }}
      </label>
      <div class="d-flex flex-wrap device-photos dropzone-previews">
        <FileUploader :url="'/device/image-upload/' + device.iddevices" v-if="edit && !disabled" previews-container=".device-photos" @uploaded="uploaded($event)" />
        <DeviceImage v-for="image in images" :key="'img-' + image.path" :image="image" @remove="$emit('remove', image)" :disabled="disabled" />
      </div>
    </div>
    <p class="p-1 form-text" v-else>
      {{ __('devices.images_on_edit') }}
    </p>
  </div>
</template>
<script>
import FileUploader from './FileUploader'
import DeviceImage from './DeviceImage'

export default {
  components: {DeviceImage, FileUploader},
  props: {
    // This is deliberately not retrieved from the store as it can be the current, unsaved device.
    device: {
      type: Object,
      required: true
    },
    idevents: {
      type: Number,
      required: true
    },
    add: {
      type: Boolean,
      required: false,
      default: false
    },
    edit: {
      type: Boolean,
      required: false,
      default: false
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  computed: {
    images() {
      // TODO LATER The images are currently added/removed/deleted immediately, and so we get them from the store.
      // This should be deferred until the save.
      if (this.device) {
        return this.$store.getters['devices/imagesByDevice'](this.device.iddevices)
      } else {
        return []
      }
    },
  },
  methods: {
    uploaded(images) {
      // We have uploaded some images.  Add them to the store.
      this.$store.dispatch('devices/setImages', {
        iddevices: this.device.iddevices,
        images: images
      })
    }
  }
}
</script>
<style lang="scss">
// Note that this style is explicitly not scoped so that it can override dropzone styles.
@import 'resources/global/css/_variables';

.device-photo-layout {
  display: grid;
  grid-template-columns: 80px auto auto;
}

.device-photos {
  .dz-message {
    img {
      border: 2px dashed grey !important;
    }
  }

  img {
    width: 100px !important;
    height: 100px !important;
    object-fit: cover;
    margin-bottom: 0.25rem !important;
    margin-right: 0.25rem !important;
  }
}
</style>
