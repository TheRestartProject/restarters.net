<template>
  <div class="hidecell cell-number text-center">
    {{ value || '0' }}
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
}
</style>