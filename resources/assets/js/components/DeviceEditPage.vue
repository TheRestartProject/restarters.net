<template>
  <div>
    <h1>
      {{ translatedEditDevice }} #{{ initialDevice.iddevices }}
    </h1>
    <div class="border-shadow pt-4">
      <EventDevice
          :device=initialDevice
          :edit="isAdmin"
          :delete-button="isAdmin"
          :powered="initialDevice.category.powered"
          :clusters="clusters"
          :idevents="initialDevice.event"
          :brands="brands"
          :barrier-list=barrierList
          :cancel-button="false"
          @close="reload"
      />
    </div>
  </div>
</template>
<script>
// TODO Edit and View too?
import EventDevice from './EventDevice'
import auth from '../mixins/auth'

export default {
  components: {EventDevice},
  mixins: [auth],
  props: {
    initialDevice: {
      type: Object,
      required: true
    },
    clusters: {
      type: Array,
      required: false,
      default: null
    },
    brands: {
      type: Array,
      required: false,
      default: null
    },
    barrierList: {
      type: Array,
      required: false,
      default: null
    },
    isAdmin: {
      type: Boolean,
      required: true
    }
  },
  computed: {
    translatedEditDevice () {
      return this.$lang.get('devices.edit_devices')
    }
  },
  mounted () {
    if (!this.initialDevice) {
      // Invalid device id.  Just go back to the Fixometer page.  This shouldn't happen so we don't need a pretty
      // error.
      window.location = '/fixometer'
    } else {
      // Data is passed from the blade template to us via props.  We put it in the store for all components to use,
      // and so that as/when it changes then reactivity updates all the views.
      //
      // Further down the line this may change so that the data is obtained via an AJAX call and perhaps SSR.
      this.$store.dispatch('devices/set', {
        idevents: this.initialDevice.event,
        devices: [this.initialDevice]
      })
    }
  },
  methods: {
    reload() {
      console.log("Reload")
      window.location.reload()
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.border-shadow {
  background-color: $white;
  border: 1px solid $black;

  @include media-breakpoint-up(md) {
    box-shadow: 5px 5px $black;
  }
}
</style>