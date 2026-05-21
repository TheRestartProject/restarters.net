<template>
  <div class="w-100 align-items-center device-field-row">
    <label class="text-bold">
      {{ __('devices.age') }}
    </label>
    <b-input type="number" size="lg" class="marg p-1 text-center device-age-edit"  min="0" step="0.5" max="500" autocomplete="off" v-model="value" :disabled="disabled" />
    <span class="text-right mb-1">
      {{ __('devices.age_approx') }}
    </span>
  </div>
</template>
<script>

export default {
  props: {
    age: {
      type: [Number],
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
    age: {
      handler: function (val) {
        // Ensure we don't show 0.
        this.value = val ? val : null
      },
      immediate: true
    },
    value(newVal) {
      // ...even if we do store it as that.
      this.$emit('update:age', newVal ? parseFloat(newVal) : 0)
    }
  },
  methods: {
    changeAge() {
      this.$nextTick(() => {
      })
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
