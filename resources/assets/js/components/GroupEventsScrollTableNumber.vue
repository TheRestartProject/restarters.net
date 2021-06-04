<template>
  <div :class="{
    hidecell: true,
    'cell-number': true,
    'text-center': true,
    'cell-danger': !value && dangerIfZero,
    }">
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
    dangerIfZero: {
      type: Boolean,
      required: false,
      default: false
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