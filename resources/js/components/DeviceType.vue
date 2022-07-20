<template>
  <div class="w-100 device-select-row">
    <vue-typeahead-bootstrap v-model="currentType" :maxMatches="5" :data="suggestions" :minMatchingChars="1" size="lg" inputClass="marg form-control-lg" :disabled="disabled" :placeholder="__('devices.item_type')" @input="input" />
    <div v-b-popover.html.left="translatedTooltip" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'" />
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else />
    </div>
    <p v-if="unknownType" class="pl-1 form-text">
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
    },
    powered: {
      // The server might return a number rather than a boolean.
      type: [ Boolean, Number ],
      required: false,
      default: false
    }
  },
  computed: {
    suggestions() {
      const ret = this.itemTypes.filter(i => Boolean(i.powered) === Boolean(this.powered)).map(i => i.item_type)
      return ret
    },
    notASuggestion() {
      if (!this.currentType) {
        return false
      }

      let ret = true

      this.itemTypes.forEach(t => {
        if (t.item_type === this.currentType) {
          ret = false
        }
      })

      return ret
    },
    unknownType() {
      return !this.suppressTypeWarning && this.notASuggestion
    },
    translatedTooltip() {
      if (this.powered) {
        return this.$lang.get('devices.tooltip_type_powered')
      } else {
        return this.$lang.get('devices.tooltip_type_unpowered')
      }
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
    unknownType(newVal) {
      this.$emit('update:unknown', newVal)
    }
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
  font-size: 15px !important;
  flex-shrink: 0;
}

/deep/ .input-group > .form-control {
  width: 100%;
}

/deep/ span {
  font-size: 16px;
}

/deep/ .vbst-item {
  div {
    line-height: 16px;
  }
}

</style>
