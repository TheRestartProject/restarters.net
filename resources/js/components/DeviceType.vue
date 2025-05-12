<template>
  <div class="w-100 device-select-row">
    <vue-typeahead-bootstrap ref="typeahead" v-model="currentType" :maxMatches="5" :data="suggestions"
                             :minMatchingChars="1" size="lg" :inputClass="'marg form-control-lg theinput-' + uid" :disabled="disabled"
                             :placeholder="__('devices.item_type')" @hit="emit"/>
    <div v-b-popover.html.left="translatedTooltip" class="ml-3 mt-2">
      <b-img class="icon clickable" src="/icons/info_ico_black.svg" v-if="iconVariant === 'black'"/>
      <b-img class="icon clickable" src="/icons/info_ico_green.svg" v-else/>
    </div>
    <p v-if="!suppressTypeWarning && notASuggestion" class="pl-1 form-text">
      {{ __('devices.unknown_item_type') }}
    </p>
  </div>
</template>
<script>
import Vue from 'vue'
import VueTypeaheadBootstrap from 'vue-typeahead-bootstrap'
import UniqueId from 'vue-unique-id'

Vue.use(UniqueId)

export default {
  components: {VueTypeaheadBootstrap},
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
    suppressTypeWarning: {
      type: Boolean,
      required: false,
      default: false
    },
    powered: {
      // The server might return a number rather than a boolean.
      type: [Boolean, Number],
      required: false,
      default: false
    },
    autoFocus: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  computed: {
    itemTypes() {
      return this.$store.getters['items/list'];
    },
    suggestions() {
      const ret = []

      this.itemTypes.forEach(i => {
        if (i.type && i.type.length) {
          if (this.powered === i.powered) {
            ret.push(i.type)
          }
        }
      })

      return ret
    },
    notASuggestion() {
      if (!this.currentType || !this.itemTypes.length) {
        return false
      }

      let ret = true

      this.itemTypes.forEach(t => {
        if (t.type === this.currentType) {
          ret = false
        }
      })

      return ret
    },
    translatedTooltip() {
      if (this.powered) {
        return this.$lang.get('devices.tooltip_type_powered')
      } else {
        return this.$lang.get('devices.tooltip_type_unpowered')
      }
    }
  },
  data() {
    return {
      currentType: null,
    }
  },
  mounted() {
    this.currentType = this.type

    if (this.autoFocus) {
      // Focus on the input.  This is a bit hacky as the typeahead component doesn't expose the input element.  So we
      // add our own class and then find it.
      try {
        document.getElementsByClassName('theinput-' + this.uid)[0].focus()
      } catch (e){
        console.error('Input focus failed', e)
      }
    }

    // Fetch the item types.  We do this here because it's slow, and we don't want to block the page load.
    this.$store.dispatch('items/fetch')
  },
  watch: {
    type(newVal) {
      this.currentType = newVal
    },
    currentType(newVal) {
      if (!newVal.length) {
        this.$emit('update:type', null)
      } else {
        this.$emit('update:type', newVal)
      }
    },
    unknownType(newVal) {
      this.$emit('update:unknown', newVal)
    }
  },
  methods: {
    emit() {
      this.$emit('update:type', this.currentType)
    }
  }
}
</script>
<style scoped lang="scss">
// Some card styles are getting in the way.
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
