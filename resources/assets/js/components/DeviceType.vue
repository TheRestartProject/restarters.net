<template>
  <div class="w-100 device-select-row">
    <b-input @change="$emit('update:type', $event)" :placeholder="translatedType" size="lg" class="marg" :value="type" />
    <div v-b-popover.html.left="translatedTooltipType" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else />
    </div>
  </div>
</template>
<script>

export default {
  props: {
    type: {
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
      return this.$lang.get('devices.model_or_type')
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