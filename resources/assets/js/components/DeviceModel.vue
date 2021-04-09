<template>
  <div class="w-100 device-select-row">
    <b-input v-model="value" :placeholder="translatedModel" size="lg" class="marg" :disabled="disabled" />
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
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  computed: {
    value: {
      get() {
        return this.model
      },
      set(newVal) {
        this.$emit('update:model', newVal)
      }
    },
    translatedModel() {
      return this.$lang.get('devices.model')
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