<template>
  <div class="w-100 align-items-center device-field-row">
    <label class="text-bold">
      {{ __('devices.weight') }}
    </label>
    <b-input type="number" @change="$emit('update:weight', parseFloat($event))" size="lg"
             class="marg p-1 text-center" min="0" step=".1" max="500"
             autocomplete="off" :value="weight" :disabled="disabled"/>
    <span class="text-right mb-1">
      {{ info }}
    </span>
  </div>
</template>
<script>

export default {
  props: {
    weight: {
      type: Number,
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
    required: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      value: null
    }
  },
  computed: {
    info() {
      if (this.required) {
        return this.$lang.get('devices.required_impact')
      } else {
        return this.$lang.get('devices.optional_impact')
      }
    }
  }
}
</script>
<style scoped lang="scss">
@import 'resources/global/css/_variables';
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import '~bootstrap/scss/mixins/_breakpoints';

.marg {
  // Some card styles are getting in the way.
  margin: 2px !important;
}

input {
  width: 81px;
}

::v-deep(input.border-danger) {
  border: 2px solid $brand-danger !important;
}
</style>
