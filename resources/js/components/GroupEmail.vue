<template>
  <b-form-group>
    <label for="group_email">{{ __('groups.groups_email') }}:</label>
    <b-input type="url" id="group_email" name="email" v-model="currentemail" :class="{ hasError: hasError }"/>
    <small>{{ __('groups.groups_email_small') }}</small>
  </b-form-group>
</template>
<script>
export default {
  props: {
    email: {
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
      currentemail: null,
    }
  },
  mounted () {
    this.currentemail = this.email
  },
  watch: {
    currentemail(newVal) {
      this.$emit('update:email', newVal)
    },
  }
}
</script>