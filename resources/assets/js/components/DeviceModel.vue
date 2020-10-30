<template>
  <div class="w-100 device-select-row">
    <b-input @change="$emit('update:model', $event)" :placeholder="translatedModel" size="lg" class="marg" :value="model" />
    <div v-b-popover.html.left="translatedTooltipModel" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>

export default {
  props: {
    model: {
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
    translatedModel() {
      return this.$lang.get('devices.model_or_type')
    },
    translatedTooltipModel() {
      return this.$lang.get('devices.tooltip_model')
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