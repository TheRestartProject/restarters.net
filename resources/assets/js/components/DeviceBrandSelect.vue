<template>
  <div class="w-100 device-select-row">
    <multiselect
        v-model="value"
        :placeholder="translatedBrand"
        :options="brands"
        :multiple="false"
        :allow-empty="false"
        deselect-label=""
        track-by="id"
        label="brand_name"
        :taggable="false"
        selectLabel=""
        ref="multiselect"
        @select="$emit('update:brand', $event.value)"
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
  data () {
    return {
      value: null,
    }
  },
  computed: {
    translatedBrand() {
      return this.$lang.get('devices.brand')
    }
  },
  mounted() {
    this.value = this.brand
  }
}
</script>
<style scoped lang="scss">
.device-select-row {
  display: grid;
  grid-template-columns: auto 50px;
}
</style>