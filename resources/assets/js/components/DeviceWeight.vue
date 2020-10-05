<template>
  <div class="w-100 align-items-center device-field-row">
    <label class="text-bold text-white">
      {{ translatedWeight }}
    </label>
    <b-input type="number" @change="$emit('update:weight', $event)" size="lg" class="marg"  min="0.01" step=".01" autocomplete="off" />
    <span class="text-right mb-1 text-white">
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

.device-field-row {
  display: grid;
  grid-template-columns: 80px 81px auto auto;
  line-height: 1rem;
}
</style>
