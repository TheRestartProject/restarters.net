<template>
  <div class="align-items-center device-field-row d-flex">
    <label class="text-bold mr-4">
      {{ __('devices.reference') }}
    </label>
    <b-input type="string" size="lg" class="marg p-1 device-reference-edit" maxlength="255" autocomplete="off" v-model="value" :disabled="disabled" />
    <div v-b-popover.html.left="__('devices.tooltip_reference')" class="ml-3 mt-2 mb-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else="iconVariant === 'brand'" />
    </div>
  </div>
</template>
<script>

export default {
  props: {
    reference: {
      type: [String],
      required: false,
      default: 0
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
  data () {
    return {
      value: null
    }
  },
  watch: {
    reference: {
      handler: function (val) {
        this.value = val
      },
      immediate: true
    },
    value(newVal) {
      this.$emit('update:reference', newVal)
    }
  },
}
</script>
<style scoped lang="scss">
.marg {
  // Some card styles are getting in the way.
  margin: 2px !important;
}

input {
  width: 81px;
}
</style>
