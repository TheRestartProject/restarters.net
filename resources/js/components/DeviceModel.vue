<template>
  <div class="w-100 device-select-row">
    <b-input v-model="value" :placeholder="__('devices.model_if_known')" size="lg" class="marg" :disabled="disabled" />
    <div v-b-popover.html.left="__('devices.tooltip_model')" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>

import {UNKNOWN_STRINGS} from "../constants";

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
        if (newVal && UNKNOWN_STRINGS.includes(newVal)) {
          newVal = null
        }

        this.$emit('update:model', newVal)
      }
    },
  },
}
</script>
<style scoped lang="scss">
.marg {
  // Some card styles are getting in the way.
  margin: 2px !important;
}
</style>