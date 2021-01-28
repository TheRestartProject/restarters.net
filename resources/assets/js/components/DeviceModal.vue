<template>
  <b-modal v-model="showModal" no-stacking ok-only modal-class="modal-fullscreen" title-class="w-100">
    <template slot="modal-title">
      <div class="d-flex justify-content-between" v-if="device">
        <div>
          {{ device.device_category.name }}
        </div>
        <div>
          {{ date }}
        </div>
      </div>
    </template>
    <EventDevice :device="device" :powered="powered" :add="false" :edit="false" :idevents="device.event"
                 :clusters="clusters" :brands="brands" :barrier-list="barrierList" v-if="device" />
    <template slot="modal-footer" slot-scope="{ cancel }">
      <b-button variant="primary" @click="cancel">
        {{ translatedClose }}
      </b-button>
    </template>
  </b-modal>
</template>
<script>
import EventDevice from './EventDevice'
import moment from 'moment'
export default {
  components: {EventDevice},
  props: {
    device: {
      type: Object,
      required: true
    }
  },
  data () {
    return {
      showModal: false
    }
  },
  computed: {
    date() {
      return new moment(this.device.device_event.event_date).format('DD/MM/YYYY')
    },
    translatedClose() {
      return this.$lang.get('partials.close')
    },
  },
  methods: {
    show () {
      this.showModal = true
    },
    hide () {
      this.showModal = false
    },
  }
}
</script>
<style scoped lang="scss">
/deep/ .modal-fullscreen .modal-dialog {
  max-width: 100%;
  margin: 0;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100vh;
  display: flex;
  position: fixed;
  z-index: 100000;
}
</style>