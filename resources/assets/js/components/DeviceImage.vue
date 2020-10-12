<template>
  <div class="position-relative">
    <b-img :src="'/uploads/' + image.path" class="align-self-start" />
    <b-btn variant="none" class="remove" @click="confirm">
      ╳
    </b-btn>
    <ConfirmModal @confirm="remove" ref="confirm" />
  </div>
</template>
<script>
import ConfirmModal from './ConfirmModal'
export default {
  components: {ConfirmModal},
  props: {
    image: {
      type: Object,
      required: true
    }
  },
  methods: {
    remove() {
      this.$emit('remove')
    },
    confirm() {
      this.$refs.confirm.show()
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
  font-size: 16px;
  min-width: unset;
  padding: 5px;
  font-weight: bold;
  border: 1px solid grey;
}
</style>