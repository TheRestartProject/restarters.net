<template>
  <div>
    <div class="w-100 device-select-row">
      <vue-typeahead-bootstrap
          v-model="brandValue"
          :maxMatches="5"
          :data="brandsPlusCustom"
          :minMatchingChars="1"
          size="lg"
          inputClass="marg form-control-lg fontsize"
          :disabled="disabled"
          :placeholder="__('devices.brand_if_known')"
          @input="input"
      />
      <div />
    </div>
    <p v-if="!suppressBrandWarning && notASuggestion" class="pl-1 form-text">
      {{ __('devices.unknown_brand' )}}
    </p>
  </div>
</template>
<script>
import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';
import {UNKNOWN_STRINGS} from "../constants";

export default {
  components: { VueTypeaheadBootstrap },
  props: {
    brand: {
      type: String,
      required: false,
      default: null
    },
    brands: {
      type: Array,
      required: true
    },
    allowEmpty: {
      type: Boolean,
      required: false,
      default: false
    },
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
    suppressBrandWarning: {
      type: Boolean,
      required: false,
      default: false
    },
  },
  data () {
    return {
      brandValue: null
    }
  },
  computed: {
    brandsPlusCustom() {
      // We might have been given a brand string which is not one of the standard brands.  In order to display this
      // in the options list, we need to add it.
      let ret = this.brands.map(i => i.brand_name)

      if (this.brand) {
        let exists = this.brands.find(b => {
          return b.brand_name === this.brand
        })

        if (!exists) {
          ret.unshift(this.brand)
        }
      }

      ret.sort((a, b) => {
        return a.localeCompare(b)
      })

      return ret
    },
    notASuggestion() {
      if (!this.brandValue) {
        return false
      }

      let ret = true

      this.brands.forEach(t => {
        if (t.brand_name === this.brandValue) {
          ret = false
        }
      })

      return ret
    },
  },
  mounted() {
    if (this.brand) {
      this.brandValue = this.brand
    }
  },
  watch: {
    brand(newVal) {
      this.brandValue = newVal
    },
    brandValue(newVal) {
      if (newVal && UNKNOWN_STRINGS.includes(newVal.toLowerCase())) {
        newVal = null
      }

      this.$nextTick(() => {
        this.$emit('update:brand', newVal)
      })
    }
  },
  methods: {
    input(value) {
      this.$emit('update:brand', value)
    }
  }
}
</script>
<style scoped lang="scss">
::v-deep .marg {
  margin: 2px !important;
  font-size: 15px !important;
  flex-shrink: 0;
}

::v-deep .input-group > .form-control {
  width: 100%;
}

::v-deep span {
  font-size: 16px;
}

::v-deep .vbst-item {
  div {
    line-height: 16px;
  }
}
</style>