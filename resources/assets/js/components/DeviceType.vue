<template>
  <div class="w-100 device-select-row">
    <b-input @change="$emit('update:type', $event)" :placeholder="translatedType" size="lg" class="marg" />
    <div v-b-popover.html.left :title="translatedTooltipType" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>

export default {
  props: {
    value: {
      type: String,
      required: false,
      default: null
    },
    iconVariant: {
      type: String,
      required: false,
      default: 'black'
    }
  },
  computed: {
    translatedType() {
      return this.$lang.get('devices.type')
    },
    translatedTooltipType() {
      return this.$lang.get('devices.tooltip_type')
    }
  }
}
</script>
<style scoped lang="scss">
.marg {
  // Some card styles are getting in the way.
  margin: 2px !important;
}
</style>