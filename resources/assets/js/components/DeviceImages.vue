<template>
  <div class="device-photo-layout">
    <label>
      {{ translatedImages }}
    </label>
    <div class="d-flex flex-wrap device-photos dropzone-previews">
      <FileUploader :url="'/device/image-upload/' + device.iddevices" v-if="edit" previews-container=".device-photos" @uploaded="uploaded($event)" />
      <DeviceImage v-for="image in images" :key="'img-' + image.path" :image="image" @remove="$emit('remove', image)" />
    </div>
  </div>
</template>
<script>
import FileUploader from './FileUploader'
import DeviceImage from './DeviceImage'

export default {
  components: {DeviceImage, FileUploader},
  props: {
    device: {
      type: Object,
      required: true
    },
    idevents: {
      type: Number,
      required: true
    },
    images: {
      type: Array,
      required: true
    },
    edit: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    translatedImages() {
      return this.$lang.get('devices.images')
    }
  },
  methods: {
    uploaded(images) {
      // We have uploaded some images.  Add them to the store.
      this.$store.dispatch('devices/setImages', {
        idevents: this.idevents,
        iddevices: this.device.iddevices,
        images: images
      })

      this.$emit('update:images')
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
    margin-bottom: 0.25rem !important;
    margin-right: 0.25rem !important;
  }
}
</style>