<template>
  <div class="w-100 device-select-row">
    <multiselect
        v-model="brandValue"
        :placeholder="translatedBrand"
        :options="brands"
        track-by="id"
        label="brand_name"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="$emit('update:brand', $event.id)"
    />
    <div />
  </div>
</template>
<script>

export default {
  props: {
    brand: {
      type: Number,
      required: false,
      default: null
    },
    brands: {
      type: Array,
      required: true
    }
  },
  computed: {
    brandValue: {
      get() {
        let ret = null
        if (this.brand) {
          ret = this.brands.find(o => {
            return o.id === this.brand
          })
        }

        return ret
      },
      set(newval) {
        this.$emit('update:category', newval.value)
      }
    },
    translatedBrand() {
      return this.$lang.get('devices.brand')
    }
  }
}
</script>