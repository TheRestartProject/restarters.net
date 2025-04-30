<template>
  <div class="position-relative">
    <b-img-lazy :src="'/uploads/' + image.path" class="align-self-start clickme" @click.native="zoom" />
    <b-btn variant="none" class="remove align-content-center" @click="confirm" v-if="!disabled">
      ╳
    </b-btn>
    <ConfirmModal @confirm="remove" ref="confirm" />
    <DeviceImageModal :image="image" ref="modal" />
  </div>
</template>
<script>
import ConfirmModal from './ConfirmModal'
import DeviceImageModal from './DeviceImageModal'
export default {
  components: {DeviceImageModal, ConfirmModal},
  props: {
    image: {
      type: Object,
      required: true
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  methods: {
    remove() {
      this.$emit('remove')
    },
    confirm() {
      this.$refs.confirm.show()
    },
    zoom() {
      this.$refs.modal.show()
    }
  }
}
</script>
<style scoped lang="scss">
.remove {
  position: absolute;
  right: 6px;
  top: 1px;
  border-radius: 50%;
  background-color: white;
  font-size: 16px !important;
  min-width: unset !important;
  padding: 5px;
  font-weight: bolder;
  border: 2px solid grey;
  width: 30px;
  height: 30px;
}
</style>