<template>
  <div class="w-100 align-items-center device-field-row">
    <label class="text-bold">
      {{ translatedWeight }}
    </label>
    <b-input type="number" @change="$emit('update:weight', $event)" size="lg" class="marg p-1 text-center"  min="0.01" step=".01" autocomplete="off" :value="weight" />
    <span class="text-right mb-1">
      {{ translatedRequiredImpact }}
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
    }
  },
  data () {
    return {
      value: null
    }
  },
  computed: {
    translatedWeight() {
      return this.$lang.get('devices.weight')
    },
    translatedRequiredImpact() {
      return this.$lang.get('devices.required_impact')
    }
  }
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
