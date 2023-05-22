<template>
  <div>
    <label>{{ label }}</label>:
    <b-form-input v-model="currentValue" />
  </div>
</template>
<script>
export default {
  props: {
    label: {
      type: String,
      required: true,
    },
    value: {
      type: String,
      required: false,
      default: null
    },
  },
  data () {
    return {
      currentValue: null,
    }
  },
  watch: {
    value: {
      handler: function (newValue) {
        this.currentValue = newValue;
      },
      immediate: true,
    },
    currentValue: {
      handler: function (newValue) {
        this.$emit('update:value', newValue)
      },
      immediate: true,
    }
  }
}
</script>