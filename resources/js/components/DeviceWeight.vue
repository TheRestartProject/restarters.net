<template>
  <div class="w-100 align-items-center device-field-row">
    <label class="text-bold">
      {{ __('devices.weight') }}
    </label>
    <b-input type="number" @change="$emit('update:weight', parseFloat($event))" size="lg" class="marg p-1 text-center"  min="0" step=".1" autocomplete="off" :value="weight" :disabled="disabled" />
    <span class="text-right mb-1">
      {{ __('devices.required_impact') }}
    </span>
  </div>
</template>
<script>

export default {
  props: {
    weight: {
      type: Number,
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
