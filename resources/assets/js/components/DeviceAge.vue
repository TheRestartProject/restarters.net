<template>
  <div class="w-100 align-items-center device-field-row">
    <label class="text-bold">
      {{ translatedAge }}
    </label>
    <b-input type="number" @change="$emit('update:age', parseInt($event))" size="lg" class="marg"  min="0" step="0.5" autocomplete="off" />
    <span class="text-right mb-1">
      {{ translatedAgeApprox }}
    </span>
  </div>
</template>
<script>

export default {
  props: {
    age: {
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
    translatedAge() {
      return this.$lang.get('devices.age')
    },
    translatedAgeApprox() {
      return this.$lang.get('devices.age_approx')
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
