<template>
  <b-btn
      ref="btn"
      v-bind="$attrs"
      :variant="variant"
      :disabled="disabled"
      :size="size"
      :tabindex="tabindex"
      :title="buttonTitle"
      :class="[
    flex && 'd-flex gap-1 align-items-center',
    noBorder && 'no-border',
    iconlast && 'flex-row-reverse',
  ]"
      :type="type"
      @click="onClick"
  >
    <v-icon :name="computedIconData.name" :class="computedIconData.class" v-if="!loading" />
    <span v-else class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
    <span v-if="label" class="ml-1">
      {{ label }}
    </span>
  </b-btn>
</template>
<script>
// Originally derived from https://github.com/Freegle/iznik-nuxt3/blob/master/components/SpinButton.vue
// Backported to Vue2.  Confirm and offline function removed for now.
import VIcon from 'vue-awesome/components/Icon'

const SPINNER_COLOR = {
  primary: 'text-black',
  secondary: 'text-white',
  white: 'text-black',
  link: 'text-success',
  danger: 'text-white',
}

export default {
  components: {
    VIcon
  },
  props: {
    variant: {
      type: String,
      required: true,
    },
    iconName: {
      type: String,
      required: false,
      default: null,
    },
    label: {
      type: String,
      required: false,
      default: '',
    },
    timeout: {
      type: Number,
      required: false,
      default: 5000,
    },
    spinColor: {
      type: String,
      required: false,
      default: '',
    },
    disabled: Boolean,
    size: {
      type: String,
      required: false,
      default: null,
    },
    iconlast: Boolean,
    iconClass: {
      type: String,
      default: 'fa-fw',
    },
    tabindex: {
      type: Number,
      default: 0,
    },
    doneIcon: {
      type: String,
      default: 'check',
    },
    buttonTitle: {
      type: String,
      default: '',
    },
    noBorder: Boolean,
    flex: {
      type: Boolean,
      default: true,
    },
    minimumSpinTime: {
      type: Number,
      default: 500,
    },
    type: {
      type: String,
      required: false,
      default: 'submit',
    }
  },
  data() {
    return {
      loading: false,
      done: false,
      timer: null,
    }
  },
  computed: {
    computedIconData() {
      if (this.done && this.doneIcon) {
        return {
          class: this.iconClass,
          name: this.doneIcon,
        }
      }
      return {
        class: this.iconClass,
        name: this.iconName,
      }
    },
    spinColorClass() {
      return this.spinColor || SPINNER_COLOR[this.variant] || 'text-success'
    }
  },
  methods: {
    cancelLoading() {
      return new Promise((resolve) => {
        setTimeout(() => {
          this.loading = false
          resolve()
        }, this.minimumSpinTime)
      })
    },
    finishSpinner() {
      clearTimeout(this.timer)
      this.cancelLoading().then(() => {
        if (this.doneIcon) {
          this.done = true
          setTimeout(() => {
            this.done = false
          }, this.timeout)
        }
      })
    },
    forgottenCallback() {
      this.finishSpinner()
      console.error(
          'SpinButton - callback not called, ' +
          this.variant +
          ', ' +
          this.label +
          ', ' +
          this.iconName
      )
    },
    onClick() {
      if (!this.loading) {
        // Blur so that the button doesn't stay focused and therefore e.g. black.
        this.$refs.btn.blur()
        this.done = false
        this.loading = true
        this.$emit('handle', this.finishSpinner)
        this.timer = setTimeout(forgottenCallback, 20 * 1000)
      }
    }
  },
  beforeUnmount() {
    clearTimeout(timer)
  }
}
</script>
<style scoped lang="scss">
.no-border {
  border-color: transparent !important;
}
</style>
