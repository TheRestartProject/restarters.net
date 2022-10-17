<template>
  <b-form-group>
    <label for="group_phone">{{ __('groups.field_phone') }}:</label>
    <b-input type="url" id="group_phone" name="name" v-model="currentphone" :class="{ hasError: hasError }"/>
    <small>{{ __('groups.phone_small') }}</small>
  </b-form-group>
</template>
<script>
export default {
  props: {
    phone: {
      type: String,
      required: false,
      default: null
    },
    hasError: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data () {
    return {
      currentphone: null,
    }
  },
  mounted () {
    this.currentphone = this.phone
  },
  watch: {
    currentphone(newVal) {
      this.$emit('update:phone', newVal)
    },
  }
}
</script>