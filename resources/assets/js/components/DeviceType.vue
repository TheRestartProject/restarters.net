<template>
  <div class="w-100 device-select-row">
    <vue-typeahead-bootstrap v-model="currentType" :data="suggestions" :minMatchingChars="1" size="lg" inputClass="marg form-control-lg" :disabled="disabled" :placeholder="translatedType" @input="input" />
    <div v-b-popover.html.left="translatedTooltipType" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else />
    </div>
    <p v-if="!suppressTypeWarning && notASuggestion" class="pl-0 text-danger">
      {{ __('devices.unknown_item_type' )}}
    </p>
  </div>
</template>
<script>
import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap';

export default {
  components: { VueTypeaheadBootstrap },
  props: {
    type: {
      type: String,
      required: false,
      default: null
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
    itemTypes: {
      type: Array,
      required: false,
      default: null
    },
    suppressTypeWarning: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    translatedType() {
      return this.$lang.get('devices.model_or_type')
    },
    translatedTooltipType() {
      return this.$lang.get('devices.tooltip_type')
    },
    suggestions() {
      return this.itemTypes.map(i => i.item_type)
    },
    notASuggestion() {
      if (!this.currentType) {
        return false
      }

      let ret = true

      this.itemTypes.forEach(t => {
        if (t.item_type.toLowerCase().indexOf(this.currentType.toLowerCase()) !== -1) {
          ret = false
        }
      })

      return ret
    }
  },
  data () {
    return {
      currentType: null,
    }
  },
  mounted() {
    this.currentType = this.type
  },
  watch: {
    type(newVal) {
      this.currentType = newVal
    },
  },
  methods: {
    input(e) {
      this.$emit('update:type', e)
    }
  }
}
</script>
<style scoped lang="scss">
// Some card styles are getting in the way.
/deep/ .marg {
  margin: 2px !important;
  font-size: 15px;
  flex-shrink: 0;
}

/deep/ .input-group > .form-control {
  width: 100%;
}
</style>