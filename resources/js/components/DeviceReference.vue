<template>
  <div class="w-100 align-items-center device-field-row d-flex">
    <label class="text-bold mr-4">
      {{ __('devices.reference') }}
    </label>
    <b-input type="string" size="lg" class="marg p-1 device-reference-edit flex-grow-1" maxlength="255" autocomplete="off" v-model="value" :disabled="disabled" />
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
