<template>
  <div class="w-100 device-select-row">
    <multiselect
        :value="brandValue"
        :placeholder="translatedBrand"
        :options="brandsPlusCustom"
        track-by="id"
        label="brand_name"
        :multiple="false"
        :allow-empty="allowEmpty"
        deselect-label=""
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="selected"
        @search-change="input"
        :showNoResults="false"
        :selectedLabel="allowEmpty ? translatedRemove : null"
    />
    <div />
  </div>
</template>
<script>

export default {
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
    }
  },
  computed: {
    translatedRemove() {
      return this.$lang.get('partials.remove')
    },
    brandValue: {
      get() {
        let ret = null
        if (this.brand) {
          ret = this.brandsPlusCustom.find(o => {
            return o.brand_name === this.brand
          })
        }

        return ret
      },
      set(newval) {
        this.$emit('update:category', newval ? newval.brand_name : null)
      }
    },
    brandsPlusCustom() {
      // We might have been given a brand string which is not one of the standard brands.  In order to display this
      // in the options list, we need to add it.
      let ret = JSON.parse(JSON.stringify(this.brands))

      if (this.brand) {
        let exists = this.brands.find(b => {
          return b.brand_name === this.brand
        })

        if (!exists) {
          ret.unshift({
            id: -1,
            brand_name: this.brand
          })
        }
      }

      ret.sort((a, b) => {
        return a.brand_name.localeCompare(b.brand_name)
      })

      return ret
    },
    translatedBrand() {
      return this.$lang.get('devices.brand')
    },
  },
  methods: {
    selected(selectedOption, id) {
      this.$emit('update:brand', selectedOption.brand_name)
    },
    input(value, id) {
      // multiselect isn't really intended to allow you to choose a value not in the list, but we can force that to
      // happen by emitting an update at this point.
      if (value) {
        this.$emit('update:brand', value)
      }
    }
  }
}
</script>