<template>
  <div class="form-group emailtest">
    <label :for="inputId">{{ label }}:<sup v-if="required">*</sup></label>
    <input
      type="email"
      class="form-control field"
      :class="{ 'is-invalid': hasError || validationError }"
      :id="inputId"
      :name="name"
      v-model="currentEmail"
      :required="required"
      :disabled="disabled"
      :aria-required="required"
      @blur="checkEmail"
    >
    <div class="invalid-feedback" :style="{ display: validationError ? 'block' : 'none' }">
      {{ validationError }}
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  props: {
    value: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: 'Email address'
    },
    inputId: {
      type: String,
      default: 'registeremail'
    },
    name: {
      type: String,
      default: 'email'
    },
    required: {
      type: Boolean,
      default: true
    },
    disabled: {
      type: Boolean,
      default: false
    },
    hasError: {
      type: Boolean,
      default: false
    },
    checkUrl: {
      type: String,
      default: '/user/register/check-valid-email'
    }
  },
  data() {
    return {
      currentEmail: this.value,
      validationError: ''
    }
  },
  watch: {
    currentEmail(newVal) {
      this.$emit('input', newVal)
      // Clear error when user starts typing again
      if (this.validationError) {
        this.validationError = ''
      }
    },
    value(newVal) {
      this.currentEmail = newVal
    }
  },
  methods: {
    async checkEmail() {
      if (!this.currentEmail || this.currentEmail.length === 0 || this.disabled) {
        return
      }

      try {
        const response = await axios.post(this.checkUrl, {
          email: this.currentEmail
        })

        if (response.data.message && response.data.message !== 'Email is available') {
          this.validationError = response.data.message
        } else {
          this.validationError = ''
        }
      } catch (error) {
        // On error, clear validation state
        this.validationError = ''
      }
    }
  }
}
</script>
