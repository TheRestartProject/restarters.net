<template>
  <div class="w-100 device-select-row">
    <b-input @change="$emit('update:type', $event)" :placeholder="__('devices.model_or_type')" size="lg" class="marg" :value="type" :disabled="disabled" />
    <div v-b-popover.html.left="__('devices.tooltip_type')" class="ml-3 mt-2">
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
    suggestions() {
      return this.itemTypes.map(i => i.item_type)
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