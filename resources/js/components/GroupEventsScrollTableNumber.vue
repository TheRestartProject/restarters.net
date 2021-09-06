<template>
  <div class="hidecell cell-number w-100 d-flex justify-content-center pr-3">
    {{ value || '0' }}&nbsp;
    <span v-if="units">
      {{ units}}
    </span>
  </div>
</template>
<script>
export default {
  props: {
    value: {
      type: Number,
      required: true
    },
    units: {
      type: String,
      required: false,
      default: null
    }
  }
}
</script>
<style scoped lang="scss">
.cell-number {
  width: 60px;
  height: unset !important;
}
</style>